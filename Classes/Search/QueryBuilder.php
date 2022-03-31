<?php

namespace CodeQ\AssetSearch\Search;

use Behat\Transliterator\Transliterator;
use CodeQ\AssetSearch\Driver\QueryInterface;
use CodeQ\AssetSearch\Dto\SearchResult;
use CodeQ\AssetSearch\ElasticSearchClient;
use CodeQ\AssetSearch\Exception;
use CodeQ\AssetSearch\Exception\QueryBuildingException;
use Flowpack\ElasticSearch\Transfer\Exception\ApiException;
use JsonException;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Utility\Now;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Utility\Arrays;
use Psr\Log\LoggerInterface;

class QueryBuilder implements QueryBuilderInterface, ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var ElasticSearchClient
     */
    protected $elasticSearchClient;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ThrowableStorageInterface
     */
    protected $throwableStorage;

    /**
     * @var boolean
     */
    protected $logThisQuery = false;

    /**
     * @var string
     */
    protected $logMessage;

    /**
     * @var integer
     */
    protected $limit;

    /**
     * @var integer
     */
    protected $from;

    /**
     * @Flow\Inject(lazy=false)
     * @var Now
     */
    protected $now;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * This (internal) array stores, for the last search request, a mapping from Node Identifiers
     * to the full Elasticsearch Hit which was returned.
     *
     * This is needed to e.g. use result highlighting.
     *
     * @var array
     */
    protected array $elasticSearchHitsIndexedByAssetFromLastRequest;

    /**
     * The Elasticsearch request, as it is being built up.
     *
     * @var QueryInterface
     * @Flow\Inject
     */
    protected $request;

    /**
     * @var array
     */
    protected $result = [];

    /**
     * HIGH-LEVEL API
     */

    /**
     * Filter by node type, taking inheritance into account.
     *
     * @param string $assetType the asset type to filter for
     * @return QueryBuilder
     * @throws QueryBuildingException
     * @api
     */
    public function assetType(string $assetType): QueryBuilderInterface
    {
        // on indexing, neos_type_and_supertypes contains the typename itself and all supertypes, so that's why we can
        // use a simple term filter here.

        // http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-term-filter.html
        return $this->queryFilter('term', ['asset_type' => $assetType]);
    }

    /**
     * Sort descending by $propertyName
     *
     * @param string $propertyName the property name to sort by
     * @return QueryBuilder
     * @api
     */
    public function sortDesc(string $propertyName): QueryBuilderInterface
    {
        $configuration = [
            $propertyName => ['order' => 'desc']
        ];

        $this->sort($configuration);

        return $this;
    }

    /**
     * Sort ascending by $propertyName
     *
     * @param string $propertyName the property name to sort by
     * @return QueryBuilder
     * @api
     */
    public function sortAsc(string $propertyName): QueryBuilderInterface
    {
        $configuration = [
            $propertyName => ['order' => 'asc']
        ];

        $this->sort($configuration);

        return $this;
    }

    /**
     * Add a $configuration sort filter to the request
     *
     * @param  array  $configuration
     * @return QueryBuilder
     * @api
     */
    public function sort(array $configuration): QueryBuilder
    {
        $this->request->addSortFilter($configuration);

        return $this;
    }

    /**
     * output only $limit records
     *
     * Internally, we fetch $limit*$workspaceNestingLevel records, because we fetch the *conjunction* of all workspaces;
     * and then we filter after execution when we have found the right number of results.
     *
     * This algorithm can be re-checked when https://github.com/elasticsearch/elasticsearch/issues/3300 is merged.
     *
     * @param integer $limit
     * @return QueryBuilder
     * @throws IllegalObjectTypeException
     * @api
     */
    public function limit($limit): QueryBuilderInterface
    {
        if ($limit === null) {
            return $this;
        }
        $this->limit = $limit;

        // http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-request-from-size.html
        $this->request->size($limit);

        return $this;
    }

    /**
     * output records starting at $from
     *
     * @param integer $from
     * @return QueryBuilder
     * @api
     */
    public function from($from): QueryBuilderInterface
    {
        if (!$from) {
            return $this;
        }

        $this->from = $from;
        $this->request->from($from);

        return $this;
    }

    /**
     * add an exact-match query for a given property
     *
     * @param string $propertyName Name of the property
     * @param mixed $propertyValue Value for comparison
     * @return QueryBuilder
     * @throws QueryBuildingException
     * @api
     */
    public function exactMatch(string $propertyName, $propertyValue): QueryBuilderInterface
    {
        return $this->queryFilter('term', [$propertyName => $this->convertValue($propertyValue)]);
    }

    /**
     * @param string $propertyName
     * @param mixed $value
     * @return QueryBuilder
     * @throws QueryBuildingException
     */
    public function exclude(string $propertyName, $value): QueryBuilder
    {
        return $this->queryFilter('term', [$propertyName => $this->convertValue($value)], 'must_not');
    }

    /**
     * add a range filter (gt) for the given property
     *
     * @param string $propertyName Name of the property
     * @param mixed $value Value for comparison
     * @param string $clauseType one of must, should, must_not
     * @return QueryBuilder
     * @throws QueryBuildingException
     * @api
     */
    public function greaterThan(string $propertyName, $value, string $clauseType = 'must'): QueryBuilder
    {
        return $this->queryFilter('range', [$propertyName => ['gt' => $this->convertValue($value)]], $clauseType);
    }

    /**
     * add a range filter (gte) for the given property
     *
     * @param string $propertyName Name of the property
     * @param mixed $value Value for comparison
     * @param string $clauseType one of must, should, must_not
     * @return QueryBuilder
     * @throws QueryBuildingException
     * @api
     */
    public function greaterThanOrEqual(string $propertyName, $value, string $clauseType = 'must'): QueryBuilder
    {
        return $this->queryFilter('range', [$propertyName => ['gte' => $this->convertValue($value)]], $clauseType);
    }

    /**
     * add a range filter (lt) for the given property
     *
     * @param string $propertyName Name of the property
     * @param mixed $value Value for comparison
     * @param string $clauseType one of must, should, must_not
     * @return QueryBuilder
     * @throws QueryBuildingException
     * @api
     */
    public function lessThan(string $propertyName, $value, string $clauseType = 'must'): QueryBuilder
    {
        return $this->queryFilter('range', [$propertyName => ['lt' => $this->convertValue($value)]], $clauseType);
    }

    /**
     * add a range filter (lte) for the given property
     *
     * @param string $propertyName Name of the property
     * @param mixed $value Value for comparison
     * @param string $clauseType one of must, should, must_not
     * @return QueryBuilder
     * @throws QueryBuildingException
     * @api
     */
    public function lessThanOrEqual(string $propertyName, $value, string $clauseType = 'must'): QueryBuilder
    {
        return $this->queryFilter('range', [$propertyName => ['lte' => $this->convertValue($value)]], $clauseType);
    }

    /**
     * LOW-LEVEL API
     */

    /**
     * Add a filter to query.filtered.filter
     *
     * @param string $filterType
     * @param mixed $filterOptions
     * @param string $clauseType one of must, should, must_not
     * @return QueryBuilder
     * @throws QueryBuildingException
     * @api
     */
    public function queryFilter(string $filterType, $filterOptions, string $clauseType = 'must'): QueryBuilder
    {
        $this->request->queryFilter($filterType, $filterOptions, $clauseType);

        return $this;
    }

    /**
     * Append $data to the given array at $path inside $this->request.
     *
     * Low-level method to manipulate the Elasticsearch Query
     *
     * @param string $path
     * @param array $data
     * @return QueryBuilder
     * @throws QueryBuildingException
     */
    public function appendAtPath(string $path, array $data): QueryBuilder
    {
        $this->request->appendAtPath($path, $data);

        return $this;
    }

    /**
     * Add multiple filters to query.filtered.filter
     *
     * Example Usage:
     *
     *   searchFilter = Neos.Fusion:RawArray {
     *      author = 'Max'
     *      tags = Neos.Fusion:RawArray {
     *        0 = 'a'
     *        1 = 'b'
     *      }
     *   }
     *
     *   searchQuery = ${Search.queryFilterMultiple(this.searchFilter)}
     *
     * @param array $data An associative array of keys as variable names and values as variable values
     * @param  string  $clauseType one of must, should, must_not
     * @return QueryBuilder
     * @throws QueryBuildingException
     * @api
     */
    public function queryFilterMultiple(array $data, string $clauseType = 'must'): QueryBuilder
    {
        foreach ($data as $key => $value) {
            if ($value !== null) {
                if (is_array($value)) {
                    $this->queryFilter('terms', [$key => array_values($value)], $clauseType);
                } else {
                    $this->queryFilter('term', [$key => $value], $clauseType);
                }
            }
        }

        return $this;
    }

    /**
     * This method adds a field based aggregation configuration. This can be used for simple
     * aggregations like terms
     *
     * Example Usage to create a terms aggregation for a property color:
     * assets = ${Search....fieldBasedAggregation("colors", "color").execute()}
     *
     * Access all aggregation data with {assets.aggregations} in your fluid template
     *
     * @param string $name The name to identify the resulting aggregation
     * @param string $field The field to aggregate by
     * @param string $type Aggregation type
     * @param string $parentPath
     * @param int|null $size The amount of buckets to return or null if not applicable to the aggregation
     * @return QueryBuilder
     * @throws QueryBuildingException
     */
    public function fieldBasedAggregation(string $name, string $field, string $type = 'terms', string $parentPath = '', ?int $size = null): QueryBuilder
    {
        $aggregationDefinition = [
            $type => [
                'field' => $field
            ]
        ];

        if ($size !== null) {
            $aggregationDefinition[$type]['size'] = $size;
        }

        $this->aggregation($name, $aggregationDefinition, $parentPath);

        return $this;
    }

    /**
     * This method is used to create any kind of aggregation.
     *
     * Example Usage to create a terms aggregation for a property color:
     *
     * aggregationDefinition = Neos.Fusion:DataStructure {
     *   terms {
     *     field = "color"
     *   }
     * }
     *
     * assets = ${Search....aggregation("color", this.aggregationDefinition).execute()}
     *
     * Access all aggregation data with {assets.aggregations} in your fluid template
     *
     * @param string $name
     * @param array $aggregationDefinition
     * @param string $parentPath
     * @return QueryBuilder
     * @throws QueryBuildingException
     */
    public function aggregation(string $name, array $aggregationDefinition, string $parentPath = ''): QueryBuilder
    {
        $this->request->aggregation($name, $aggregationDefinition, $parentPath);

        return $this;
    }

    /**
     * This method is used to create a simple term suggestion.
     *
     * Example Usage of a term suggestion
     *
     * assets = ${Search....termSuggestions("aTerm")}
     *
     * Access all suggestions data with ${Search....getSuggestions()}
     *
     * @param string $text
     * @param string $field
     * @param string $name
     * @return QueryBuilder
     */
    public function termSuggestions(string $text, string $field = 'neos_fulltext.text', string $name = 'suggestions'): QueryBuilder
    {
        $suggestionDefinition = [
            'text' => $text,
            'term' => [
                'field' => $field
            ]
        ];

        $this->suggestions($name, $suggestionDefinition);

        return $this;
    }

    /**
     * This method is used to create any kind of suggestion.
     *
     * Example Usage of a term suggestion for the fulltext search
     *
     * suggestionDefinition = Neos.Fusion:RawArray {
     *     text = "some text"
     *     terms = Neos.Fusion:RawArray {
     *         field = "body"
     *     }
     * }
     *
     * assets = ${Search....suggestion("my-suggestions", this.suggestionDefinition).execute()}
     *
     * Access all suggestions data with {assets.suggestions} in your fluid template
     *
     * @param string $name
     * @param array $suggestionDefinition
     * @return QueryBuilder
     */
    public function suggestions(string $name, array $suggestionDefinition): QueryBuilder
    {
        $this->request->suggestions($name, $suggestionDefinition);

        return $this;
    }

    /**
     * Get the Elasticsearch request as we need it
     *
     * @return QueryInterface
     */
    public function getRequest(): QueryInterface
    {
        return $this->request;
    }

    /**
     * Log the current request to the Elasticsearch log for debugging after it has been executed.
     *
     * @param  string|null  $message an optional message to identify the log entry
     * @return QueryBuilder
     * @api
     */
    public function log(string $message = null): QueryBuilder
    {
        $this->logThisQuery = true;
        $this->logMessage = $message;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalItems(): int
    {
        return $this->evaluateResult($this->result)->getTotal();
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getFrom(): int
    {
        return $this->from;
    }

    /**
     * This low-level method can be used to look up the full Elasticsearch hit given a certain node.
     *
     * @param AssetInterface $asset
     * @return array the Elasticsearch hit for the node as array, or NULL if it does not exist.
     */
    public function getFullElasticSearchHitForAsset(AssetInterface $asset): ?array
    {
        return $this->elasticSearchHitsIndexedByAssetFromLastRequest[$asset->getIdentifier()] ?? null;
    }

    /**
     * Execute the query and return the list of assets as result.
     *
     * This method is rather internal; just to be called from the ElasticSearchQueryResult. For the public API, please use execute()
     *
     * @return array<AssetInterface>
     * @throws Exception
     * @throws \Flowpack\ElasticSearch\Exception
     * @throws \Neos\Flow\Http\Exception
     */
    public function fetch(): array
    {
        try {
            $timeBefore = microtime(true);
            $request = $this->request->getRequestAsJson();
            $response = $this->elasticSearchClient->getIndex()->request('GET', '/_search', [], $request);
            $timeAfterwards = microtime(true);

            $this->result = $response->getTreatedContent();
            $searchResult = $this->evaluateResult($this->result);

            $this->result['assets'] = [];

            $this->logThisQuery && $this->logger->debug(sprintf('Query Log (%s): Indexname: %s %s -- execution time: %s ms -- Limit: %s -- Number of results returned: %s -- Total Results: %s', $this->logMessage, $this->getIndexName(), $request, (($timeAfterwards - $timeBefore) * 1000), $this->limit, count($searchResult->getHits()), $searchResult->getTotal()), LogEnvironment::fromMethodName(__METHOD__));

            if (count($searchResult->getHits()) > 0) {
                $this->result['assets'] = $this->convertHitsToAssets($searchResult->getHits());
            }
        } catch (ApiException $exception) {
            $message = $this->throwableStorage->logThrowable($exception);
            $this->logger->error(sprintf('Request failed with %s', $message), LogEnvironment::fromMethodName(__METHOD__));
            $this->result['assets'] = [];
        }

        return $this->result;
    }

    /**
     * @param array $result
     * @return SearchResult
     */
    protected function evaluateResult(array $result): SearchResult
    {
        return new SearchResult(
            $hits = $result['hits']['hits'] ?? [],
            $total = $result['hits']['total']['value'] ?? 0
        );
    }

    /**
     * Get a query result object for lazy execution of the query
     *
     * @param  bool  $cacheResult
     * @return QueryResult
     * @throws Exception
     * @throws Exception\ConfigurationException
     * @throws JsonException
     * @api
     */
    public function execute(bool $cacheResult = true): \Traversable
    {
        $elasticSearchQuery = new Query($this);
        return $elasticSearchQuery->execute($cacheResult);
    }

    /**
     * Get a uncached query result object for lazy execution of the query
     *
     * @return QueryResult
     * @throws JsonException
     * @api
     */
    public function executeUncached(): QueryResult
    {
        $elasticSearchQuery = new Query($this);
        return $elasticSearchQuery->execute();
    }

    /**
     * Return the total number of hits for the query.
     *
     * @return integer
     * @throws Exception
     * @throws \Flowpack\ElasticSearch\Exception
     * @throws \Neos\Flow\Http\Exception
     * @api
     */
    public function count(): int
    {
        $timeBefore = microtime(true);
        $request = $this->getRequest()->getCountRequestAsJson();

        $response = $this->elasticSearchClient->getIndex()->request('GET', '/_count', [], $request);
        $timeAfterwards = microtime(true);

        $treatedContent = $response->getTreatedContent();
        $count = (int)$treatedContent['count'];

        $this->logThisQuery && $this->logger->debug('Count Query Log (' . $this->logMessage . '): Indexname: ' . $this->getIndexName() . ' ' . $request . ' -- execution time: ' . (($timeAfterwards - $timeBefore) * 1000) . ' ms -- Total Results: ' . $count, LogEnvironment::fromMethodName(__METHOD__));

        return $count;
    }

    /**
     * Match the searchword against the fulltext index
     *
     * @param string $searchWord
     * @param array $options Options to configure the query_string, see https://www.elastic.co/guide/en/elasticsearch/reference/7.6/query-dsl-query-string-query.html
     * @return QueryBuilderInterface
     * @throws JsonException
     * @api
     */
    public function fulltext(string $searchWord, array $options = []): QueryBuilderInterface
    {
        // We automatically enable result highlighting when doing fulltext searches. It is up to the user to use this information or not use it.
        $this->request->fulltext(trim(json_encode($searchWord, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), '"'), $options);
        $this->request->highlight(150, 2);

        return $this;
    }

    /**
     * Adds a prefix filter to the query
     * See: https://www.elastic.co/guide/en/elasticsearch/reference/7.6/query-dsl-prefix-query.html
     *
     * @param string $propertyName
     * @param string $prefix
     * @param string $clauseType one of must, should, must_not
     * @return $this|QueryBuilderInterface
     * @throws QueryBuildingException
     */
    public function prefix(string $propertyName, string $prefix, string $clauseType = 'must'): QueryBuilderInterface
    {
        $this->request->queryFilter('prefix', [$propertyName => $prefix], $clauseType);

        return $this;
    }

    /**
     * Filters documents that include only hits that exists within a specific distance from a geo point.
     *
     * @param string $propertyName
     * @param string|array $geoPoint Either ['lon' => x.x, 'lat' => y.y], [lon, lat], 'lat,lon', or GeoHash
     * @param string $distance Distance with unit. See: https://www.elastic.co/guide/en/elasticsearch/reference/7.6/common-options.html#distance-units
     * @param string $clauseType one of must, should, must_not
     * @return QueryBuilderInterface
     * @throws QueryBuildingException
     */
    public function geoDistance(string $propertyName, $geoPoint, string $distance, string $clauseType = 'must'): QueryBuilderInterface
    {
        $this->queryFilter('geo_distance', [
            'distance' => $distance,
            $propertyName => $geoPoint,
        ], $clauseType);

        return $this;
    }

    /**
     * Configure Result Highlighting. Only makes sense in combination with fulltext(). By default, highlighting is enabled.
     * It can be disabled by calling "highlight(FALSE)".
     *
     * @param int|bool $fragmentSize The result fragment size for highlight snippets. If this parameter is FALSE, highlighting will be disabled.
     * @param int|null $fragmentCount The number of highlight fragments to show.
     * @param int $noMatchSize
     * @param string $field
     * @return QueryBuilder
     * @api
     */
    public function highlight($fragmentSize, int $fragmentCount = null, int $noMatchSize = 150, string $field = 'neos_fulltext.*'): QueryBuilder
    {
        $this->request->highlight($fragmentSize, $fragmentCount, $noMatchSize, $field);

        return $this;
    }

    /**
     * This method is used to define a more like this query.
     * The More Like This Query (MLT Query) finds documents that are "like" a given set of documents.
     * See: https://www.elastic.co/guide/en/elasticsearch/reference/5.6/query-dsl-mlt-query.html
     *
     * @param array $like An array of strings or documents
     * @param array $fields Fields to compare other docs with
     * @param array $options Additional options for the more_like_this quey
     * @return QueryBuilder
     */
    public function moreLikeThis(array $like, array $fields = [], array $options = []): QueryBuilder
    {
        $like = is_array($like) ? $like : [$like];

        $getDocumentDefinitionByNode = function (QueryInterface $request, AssetInterface $asset): array {
            $request->queryFilter('term', ['neos_node_identifier' => $asset->getIdentifier()]);
            $response = $this->elasticSearchClient->getIndex()->request('GET', '/_search', [], $request->toArray())->getTreatedContent();
            $respondedDocuments = Arrays::getValueByPath($response, 'hits.hits');
            if (count($respondedDocuments) === 0) {
                $this->logger->info(sprintf('The asset with identifier %s was not found in the elasticsearch index.', $asset->getIdentifier()), LogEnvironment::fromMethodName(__METHOD__));
                return [];
            }

            $respondedDocument = current($respondedDocuments);
            return [
                '_id' => $respondedDocument['_id'],
                '_index' => $respondedDocument['_index'],
            ];
        };

        $processedLike = [];

        foreach ($like as $key => $likeElement) {
            if ($likeElement instanceof AssetInterface) {
                $documentDefinition = $getDocumentDefinitionByNode(clone $this->request, $likeElement);
                if (!empty($documentDefinition)) {
                    $processedLike[] = $documentDefinition;
                }
            } else {
                $processedLike[] = $likeElement;
            }
        }

        $processedLike = array_filter($processedLike);

        if (!empty($processedLike)) {
            $this->request->moreLikeThis($processedLike, $fields, $options);
        }

        return $this;
    }

    /**
     * Sets the starting point for this query. Search result should only contain assets that
     * match the context of the given node and have it as parent node in their rootline.
     *
     * @param AssetCollection|null $assetCollection
     *
     * @return QueryBuilder
     * @api
     */
    public function query(AssetCollection $assetCollection = null): QueryBuilderInterface
    {
        if (!is_null($assetCollection)) {
            $this->exactMatch('collections', $this->persistenceManager->getIdentifierByObject($assetCollection));
        }

        return $this;
    }

    /**
     * @param Tag|string $tag
     * @return QueryBuilderInterface
     */
    public function tag($tag): QueryBuilderInterface
    {
        if ($tag instanceof Tag) {
            $tag = Transliterator::urlize($tag->getLabel());
        }

        return $this->exactMatch('tags', $tag);
    }

    /**
     * Modify a part of the Elasticsearch Request denoted by $path, merging together
     * the existing values and the passed-in values.
     *
     * @param string $path
     * @param mixed $requestPart
     * @return QueryBuilder
     */
    public function request(string $path, $requestPart): QueryBuilder
    {
        $this->request->setByPath($path, $requestPart);

        return $this;
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }

    /**
     * @param array $hits
     * @return array Array of Asset objects
     */
    protected function convertHitsToAssets(array $hits): array
    {
        $assets = [];
        $elasticSearchHitPerNode = [];
        $notConvertedAssetIdentifiers = [];

        /**
         * TODO: This code below is not fully correct yet:
         *
         * We always fetch $limit * (numberOfWorkspaces) records; so that we find a node:
         * - *once* if it is only in live workspace and matches the query
         * - *once* if it is only in user workspace and matches the query
         * - *twice* if it is in both workspaces and matches the query *both times*. In this case we filter the duplicate record.
         * - *once* if it is in the live workspace and has been DELETED in the user workspace (STILL WRONG)
         * - *once* if it is in the live workspace and has been MODIFIED to NOT MATCH THE QUERY ANYMORE in user workspace (STILL WRONG)
         *
         * If we want to fix this cleanly, we'd need to do an *additional query* in order to filter all assets from a non-user workspace
         * which *do exist in the user workspace but do NOT match the current query*. This has to be done somehow "recursively"; and later
         * we might be able to use https://github.com/elasticsearch/elasticsearch/issues/3300 as soon as it is merged.
         */
        foreach ($hits as $hit) {
            $assetIdentifier = $hit['_id'];
            $asset = $this->assetRepository->findByIdentifier($assetIdentifier);

            if (!$asset instanceof AssetInterface) {
                $notConvertedAssetIdentifiers[] = $assetIdentifier;
                continue;
            }

            if (isset($assets[$asset->getIdentifier()])) {
                continue;
            }

            $assets[$asset->getIdentifier()] = $asset;
            $elasticSearchHitPerNode[$asset->getIdentifier()] = $hit;
            if ($this->limit > 0 && count($assets) >= $this->limit) {
                break;
            }
        }

        $this->logThisQuery && $this->logger->debug(sprintf('[%s] Returned %s assets.', $this->logMessage, count($assets)), LogEnvironment::fromMethodName(__METHOD__));

        if (!empty($notConvertedAssetIdentifiers)) {
            $this->logger->warning(sprintf('[%s] Search resulted in %s hits but only %s hits could be converted to assets. Nodes with paths "%s" could not have been converted.', $this->logMessage, count($hits), count($assets), implode(', ', $notConvertedAssetIdentifiers)), LogEnvironment::fromMethodName(__METHOD__));
        }

        $this->elasticSearchHitsIndexedByAssetFromLastRequest = $elasticSearchHitPerNode;

        return array_values($assets);
    }

    /**
     * @param string $dateField
     * @return int
     * @throws QueryBuildingException
     * @throws \Flowpack\ElasticSearch\Exception
     * @throws \Neos\Flow\Http\Exception
     */
    protected function getNearestFutureDate(string $dateField): int
    {
        $request = clone $this->request;

        $convertDateResultToTimestamp = static function (array $dateResult): int {
            if (!isset($dateResult['value_as_string'])) {
                return 0;
            }
            return (new \DateTime($dateResult['value_as_string']))->getTimestamp();
        };

        $request->queryFilter('range', [$dateField => ['gt' => 'now']], 'must');
        $request->aggregation('minTime', [
            'min' => [
                'field' => $dateField
            ]
        ]);

        $request->size(0);

        $requestArray = $request->toArray();

        $mustNot = Arrays::getValueByPath($requestArray, 'query.bool.filter.bool.must_not');

        /* Remove exclusion of not yet visible assets
        - range:
          neos_hidden_before_datetime:
            gt: now
        */
        unset($mustNot[1]);

        $requestArray = Arrays::setValueByPath($requestArray, 'query.bool.filter.bool.must_not', array_values($mustNot));

        $result = $this->elasticSearchClient->getIndex()->request('GET', '/_search', [], $requestArray)->getTreatedContent();

        return $convertDateResultToTimestamp(Arrays::getValueByPath($result, 'aggregations.minTime'));
    }

    /**
     * Proxy method to access the public method of the Request object
     *
     * This is used to call a method of a custom Request type where no corresponding wrapper method exist in the QueryBuilder.
     *
     * @param string $method
     * @param array $arguments
     * @return QueryBuilder
     * @throws Exception
     */
    public function __call(string $method, array $arguments): QueryBuilder
    {
        if (!method_exists($this->request, $method)) {
            throw new Exception(sprintf('Method "%s" does not exist in the current Request object "%s"', $method, get_class($this->request)), 1486763515);
        }
        call_user_func_array([$this->request, $method], $arguments);

        return $this;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function convertValue($value)
    {
        if ($value instanceof Tag || $value instanceof AssetCollection) {
            return $this->persistenceManager->getIdentifierByObject($value);
        }

        if ($value instanceof Asset) {
            return $value->getIdentifier();
        }

        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d\TH:i:sP');
        }

        return $value;
    }

    /**
     * Retrieve the indexName
     *
     * @return string
     * @throws Exception
     * @throws Exception\ConfigurationException
     */
    public function getIndexName(): string
    {
        return $this->elasticSearchClient->getIndexName();
    }
}
