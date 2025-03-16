<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Search;

/*
 * This file is part of the CodeQ.AssetSearch package.
 * Most of the code is based on the Flowpack.ElasticSearch.ContentRepositoryAdaptor and the Neos.ContentRepository.Search package.
 *
 * (c) Contributors of the Neos Project - www.neos.io and Code Q Web Factory - www.codeq.at
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use CodeQ\AssetSearch\Exception;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Media\Domain\Model\AssetInterface;

class QueryResult implements QueryResultInterface, ProtectedContextAwareInterface
{
    /**
     * @var Query
     */
    protected $elasticSearchQuery;

    /**
     * @var array|null
     */
    protected ?array $result = null;

    /**
     * @var array
     */
    protected array $assets;

    /**
     * @var integer|null
     */
    protected ?int $count = null;

    public function __construct(Query $elasticSearchQuery)
    {
        $this->elasticSearchQuery = $elasticSearchQuery;
    }

    /**
     * Initialize the results by really executing the query
     *
     * @return void
     */
    protected function initialize(): void
    {
        if ($this->result === null) {
            $queryBuilder = $this->elasticSearchQuery->getQueryBuilder();
            $this->result = $queryBuilder->fetch();
            $this->assets = $this->result['assets'];
            $this->count = $queryBuilder->getTotalItems();
        }
    }

    /**
     * @return Query
     */
    public function getQuery(): QueryInterface
    {
        return clone $this->elasticSearchQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function current(): mixed
    {
        $this->initialize();

        return current($this->assets);
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->initialize();
        next($this->assets);
    }

    /**
     * {@inheritdoc}
     */
    public function key(): mixed
    {
        $this->initialize();

        return key($this->assets);
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        $this->initialize();

        return current($this->assets) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->initialize();
        reset($this->assets);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        $this->initialize();

        return isset($this->assets[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): mixed
    {
        $this->initialize();

        return $this->assets[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->initialize();
        $this->assets[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        $this->initialize();
        unset($this->assets[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFirst()
    {
        $this->initialize();
        if (count($this->assets) > 0) {
            return array_values($this->assets)[0];
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        $this->initialize();

        return $this->assets;
    }

    /**
     * {@inheritdoc}
     * @return int
     * @throws Exception
     * @throws \Flowpack\ElasticSearch\Exception
     * @throws \Neos\Flow\Http\Exception
     */
    public function count(): int
    {
        if ($this->count === null) {
            $this->count = $this->elasticSearchQuery->getQueryBuilder()->count();
        }

        return $this->count;
    }

    /**
     * @return int the current number of results which can be iterated upon
     * @api
     */
    public function getAccessibleCount(): int
    {
        $this->initialize();

        return count($this->assets);
    }

    /**
     * @return array
     */
    public function getAggregations(): array
    {
        $this->initialize();
        if (array_key_exists('aggregations', $this->result)) {
            return $this->result['aggregations'];
        }

        return [];
    }

    /**
     * Returns an array of type
     * [
     *     <suggestionName> => [
     *         'text' => <term>
     *         'options' => [
     *              [
     *               'text' => <suggestionText>
     *               'score' => <score>
     *              ],
     *              [
     *              ...
     *              ]
     *         ]
     *     ]
     * ]
     *
     * @return array
     */
    public function getSuggestions(): array
    {
        $this->initialize();
        if (array_key_exists('suggest', $this->result)) {
            return $this->result['suggest'];
        }

        return [];
    }

    /**
     * Returns the Elasticsearch "hit" (e.g. the raw content being transferred back from Elasticsearch)
     * for the given node.
     *
     * Can be used for example to access highlighting information.
     *
     * @param  AssetInterface  $asset
     * @return array|null the Elasticsearch hit, or NULL if it does not exist.
     * @api
     */
    public function searchHitForAsset(AssetInterface $asset): ?array
    {
        return $this->elasticSearchQuery->getQueryBuilder()->getFullElasticSearchHitForAsset($asset);
    }

    /**
     * Returns the array with all sort values for a given node. The values are fetched from the raw content
     * Elasticsearch returns within the hit data
     *
     * @param AssetInterface $asset
     * @return array
     */
    public function getSortValuesForNode(AssetInterface $asset): array
    {
        $hit = $this->searchHitForAsset($asset);
        if (is_array($hit) && array_key_exists('sort', $hit)) {
            return $hit['sort'];
        }

        return [];
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
