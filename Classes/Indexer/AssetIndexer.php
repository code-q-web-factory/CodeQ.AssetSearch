<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Indexer;

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

use Behat\Transliterator\Transliterator;
use CodeQ\AssetSearch\Driver\DocumentDriverInterface;
use CodeQ\AssetSearch\Driver\IndexerDriverInterface;
use CodeQ\AssetSearch\Driver\RequestDriverInterface;
use CodeQ\AssetSearch\ErrorHandling\ErrorHandlingService;
use CodeQ\AssetSearch\AssetExtraction\AssetExtractorInterface;
use CodeQ\AssetSearch\Driver\IndexDriverInterface;
use CodeQ\AssetSearch\Driver\SystemDriverInterface;
use CodeQ\AssetSearch\ElasticSearchClient;
use CodeQ\AssetSearch\Service\IndexNameService;
use Flowpack\ElasticSearch\Domain\Model\Document;
use Flowpack\ElasticSearch\Domain\Model\Index;
use Flowpack\ElasticSearch\Transfer\Exception\ApiException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\Tag;
use Psr\Log\LoggerInterface;
use function count;

class AssetIndexer extends AbstractAssetIndexer
{
    /**
     * @var string
     */
    protected string $indexNamePostfix = '';

    /**
     * @var array
     * @Flow\InjectConfiguration(path="indexing.batchSize")
     */
    protected $batchSize;

    /**
     * The current Elasticsearch bulk request, in the format required by http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/docs-bulk.html
     *
     * @var array
     */
    protected $currentBulkRequest = [];

    /**
     * @var boolean
     */
    protected $bulkProcessing = true;

    /**
     * @Flow\Inject
     * @var ElasticSearchClient
     */
    protected $searchClient;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var AssetExtractorInterface
     */
    protected $assetExtractor;

    /**
     * @Flow\Inject
     * @var IndexDriverInterface
     */
    protected $indexDriver;

    /**
     * @Flow\Inject
     * @var SystemDriverInterface
     */
    protected $systemDriver;

    /**
     * @Flow\Inject
     * @var IndexerDriverInterface
     */
    protected $indexerDriver;

    /**
     * @Flow\Inject
     * @var DocumentDriverInterface
     */
    protected $documentDriver;

    /**
     * @Flow\Inject
     * @var RequestDriverInterface
     */
    protected $requestDriver;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ErrorHandlingService
     */
    protected $errorHandlingService;

    /**
     * @Flow\Inject
     * @var IndexNameService
     */
    protected $indexNameService;

    /**
     * @return string
     * @throws \Exception
     */
    public function getIndexName(): string
    {
        $indexName = $this->searchClient->getIndexName();
        if ($this->indexNamePostfix !== '') {
            $indexName .= IndexNameService::INDEX_PART_SEPARATOR . $this->indexNamePostfix;
        }

        return $indexName;
    }

    /**
     * @return Index
     * @throws \Flowpack\ElasticSearch\Exception
     */
    public function getIndex(): Index
    {
        return $this->searchClient->findIndex($this->getIndexName());
    }

    /**
     * @inheritDoc
     */
    public function indexAsset(Asset $asset): void
    {
        $assetCollections = array_map(function ($assetCollection) {
            return $this->persistenceManager->getIdentifierByObject($assetCollection);
        }, $asset->getAssetCollections()->toArray());

        $assetTags = array_map(function (Tag $assetTag) {
            return Transliterator::urlize($assetTag->getLabel());
        }, $asset->getTags()->toArray());

        $assetContent = $this->assetExtractor->extract($asset);
        $data = [];
        $data['title'] = $asset->getTitle() ?? $assetContent->getTitle();
        $data['caption'] = $asset->getCaption();
        $data['copyrightNotice'] = $asset->getCopyrightNotice();
        $data['lastModified'] = $asset->getLastModified() ? $asset->getLastModified()->format("Y-m-d\TH:i:sP") : null;
        $data['collections'] = $assetCollections;
        $data['tags'] = $assetTags;
        $data['contentLength'] = $assetContent->getContentLength();
        $data['contentType'] = $assetContent->getContentType();
        $data['filename'] = $asset->getResource()->getFilename();

        $sanitizedFilename = str_replace(array('_', '-'), ' ', preg_replace('/\\.[^.\\s]{3,4}$/', '', $asset->getResource()->getFilename()));
        $fulltextIndexOfNode = [
            'h1' => trim($asset->getTitle() . ' ' . $assetContent->getTitle()),
            'h2' => $data['caption'],
            'h3' => trim($sanitizedFilename . ' ' . $data['copyrightNotice']),
            'text' => $assetContent->getContent()
        ];

        $document = new Document(
            $this->getIndex()->findType(Asset::class),
            $data,
            $this->persistenceManager->getIdentifierByObject($asset)
        );
        $documentData = $document->getData();

        $this->toBulkRequest($this->indexerDriver->document($this->getIndexName(), $asset, $document, $documentData));

        if ($this->isFulltextEnabled($asset)) {
            $this->toBulkRequest($this->indexerDriver->fulltext($asset, $fulltextIndexOfNode));
        }
    }

    /**
     * @inheritDoc
     */
    public function removeAsset(Asset $asset): void
    {
        $assetIdentifier = $asset->getIdentifier();
        $this->toBulkRequest($this->documentDriver->delete($asset, $assetIdentifier));

        $this->logger->debug(sprintf('AssetIndexer: Removed asset %s from index.', $assetIdentifier), LogEnvironment::fromMethodName(__METHOD__));
    }

    /**
     * @param  array|null  $requests
     */
    protected function toBulkRequest(array $requests = null): void
    {
        if ($requests === null) {
            return;
        }

        $this->currentBulkRequest[] = new BulkRequestPart($requests);
        $this->flushIfNeeded();
    }

    /**
     * @return void
     */
    protected function flushIfNeeded(): void
    {
        if ($this->bulkRequestLength() >= $this->batchSize['elements'] || $this->bulkRequestSize() >= $this->batchSize['octets']) {
            $this->flush();
        }
    }

    /**
     * @return int
     */
    protected function bulkRequestSize(): int
    {
        return array_reduce($this->currentBulkRequest, static function ($sum, BulkRequestPart $request) {
            return $sum + $request->getSize();
        }, 0);
    }

    /**
     * @return int
     */
    protected function bulkRequestLength(): int
    {
        return count($this->currentBulkRequest);
    }

    /**
     * Perform the current bulk request
     *
     * @return void
     */
    public function flush(): void
    {
        $bulkRequest = $this->currentBulkRequest;
        $bulkRequestSize = $this->bulkRequestLength();
        if ($bulkRequestSize === 0) {
            return;
        }

        $this->logger->debug(
            vsprintf(
                'Flush bulk request, elements=%d, maximumElements=%s, octets=%d, maximumOctets=%d',
                [$bulkRequestSize, $this->batchSize['elements'], $this->bulkRequestSize(), $this->batchSize['octets']]
            ),
            LogEnvironment::fromMethodName(__METHOD__)
        );

        $payload = [];
        /** @var BulkRequestPart $bulkRequestPart */
        foreach ($bulkRequest as $bulkRequestPart) {
            if (!$bulkRequestPart instanceof BulkRequestPart) {
                throw new \RuntimeException('Invalid bulk request part', 1577016145);
            }

            foreach ($bulkRequestPart->getRequest() as $bulkRequestItem) {
                if ($bulkRequestItem === null) {
                    $this->logger->error('Indexing Error: A bulk request item could not be encoded as JSON', LogEnvironment::fromMethodName(__METHOD__));
                    continue 2;
                }
                $payload[] = $bulkRequestItem;
            }
        }

        if ($payload === []) {
            $this->reset();
            return;
        }

        $response = $this->requestDriver->bulk($this->getIndex(), implode(chr(10), $payload));

        if (isset($response['errors']) && $response['errors'] !== false) {
            foreach ($response['items'] as $responseInfo) {
                if ((int)current($responseInfo)['status'] > 299) {
                     $this->logger->error('index error', $responseInfo);
                    // $this->errorHandlingService->log($this->errorStorage->logErrorResult($responseInfo), LogEnvironment::fromMethodName(__METHOD__));
                }
            }
        }

        $this->reset();
    }

    protected function reset(): void
    {
        $this->currentBulkRequest = [];
    }

    /**
     * Update the index alias
     *
     * @return void
     */
    public function updateIndexAlias(): void
    {
        $aliasName = $this->searchClient->getIndexName(); // The alias name is the unprefixed index name
        if ($this->getIndexName() === $aliasName) {
            throw new \Exception('UpdateIndexAlias is only allowed to be called when setIndexNamePostfix() has been called.', 1383649061);
        }

        if (!$this->getIndex()->exists()) {
            throw new \Exception(sprintf('The target index "%s" does not exist.', $this->getIndex()->getName()), 1383649125);
        }

        $aliasActions = [];
        try {
            $indexNames = $this->indexDriver->getIndexNamesByAlias($aliasName);
            if ($indexNames === []) {
                // if there is an actual index with the name we want to use as alias, remove it now
                $this->indexDriver->deleteIndex($aliasName);
            } else {
                // Remove all existing aliasses
                foreach ($indexNames as $indexName) {
                    $aliasActions[] = [
                        'remove' => [
                            'index' => $indexName,
                            'alias' => $aliasName
                        ]
                    ];
                }
            }
        } catch (ApiException $exception) {
            // in case of 404, do not throw an error...
            if ($exception->getResponse()->getStatusCode() !== 404) {
                throw $exception;
            }
        }

        $aliasActions[] = [
            'add' => [
                'index' => $this->getIndexName(),
                'alias' => $aliasName
            ]
        ];

        $this->indexDriver->aliasActions($aliasActions);
    }

    /**
     * Update the main alias to allow to query all indices at once
     */
    public function updateMainAlias(): void
    {
        $aliasActions = [];
        $aliasNamePrefix = $this->searchClient->getIndexNamePrefix(); // The alias name is the unprefixed index name

        $indexNames = IndexNameService::filterIndexNamesByPostfix($this->indexDriver->getIndexNamesByPrefix($aliasNamePrefix), $this->indexNamePostfix);

        $cleanupAlias = function ($alias) use (&$aliasActions) {
            try {
                $indexNames = $this->indexDriver->getIndexNamesByAlias($alias);
                if ($indexNames === []) {
                    // if there is an actual index with the name we want to use as alias, remove it now
                    $this->indexDriver->deleteIndex($alias);
                } else {
                    foreach ($indexNames as $indexName) {
                        $aliasActions[] = [
                            'remove' => [
                                'index' => $indexName,
                                'alias' => $alias
                            ]
                        ];
                    }
                }
            } catch (ApiException $exception) {
                // in case of 404, do not throw an error...
                if ($exception->getResponse()->getStatusCode() !== 404) {
                    throw $exception;
                }
            }
        };

        if (count($indexNames) > 0) {
            $cleanupAlias($aliasNamePrefix);

            foreach ($indexNames as $indexName) {
                $aliasActions[] = [
                    'add' => [
                        'index' => $indexName,
                        'alias' => $aliasNamePrefix
                    ]
                ];
            }
        }

        $this->indexDriver->aliasActions($aliasActions);
    }

    /**
     * Remove old indices which are not active anymore (remember, each bulk index creates a new index from scratch,
     * making the "old" index a stale one).
     *
     * @return array<string> a list of index names which were removed
     */
    public function removeOldIndices(): array
    {
        $aliasName = $this->searchClient->getIndexName(); // The alias name is the unprefixed index name

        $currentlyLiveIndices = $this->indexDriver->getIndexNamesByAlias($aliasName);

        $indexStatus = $this->systemDriver->status();
        $allIndices = array_keys($indexStatus['indices']);

        $indicesToBeRemoved = [];

        foreach ($allIndices as $indexName) {
            if (strpos($indexName, $aliasName . IndexNameService::INDEX_PART_SEPARATOR) !== 0) {
                // filter out all indices not starting with the alias-name, as they are unrelated to our application
                continue;
            }

            if (in_array($indexName, $currentlyLiveIndices, true)) {
                // skip the currently live index names from deletion
                continue;
            }

            $indicesToBeRemoved[] = $indexName;
        }

        array_map(function ($index) {
            $this->indexDriver->deleteIndex($index);
        }, $indicesToBeRemoved);

        return $indicesToBeRemoved;
    }

    /**
     * Perform indexing without checking about duplication document
     *
     * This is used during bulk indexing to improve performance
     *
     * @param callable $callback
     * @throws \Exception
     */
    public function withBulkProcessing(callable $callback): void
    {
        $bulkProcessing = $this->bulkProcessing;
        $this->bulkProcessing = true;
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $callback->__invoke();
        } catch (\Exception $exception) {
            $this->bulkProcessing = $bulkProcessing;
            throw $exception;
        }
        $this->bulkProcessing = $bulkProcessing;
    }

    /**
     * @param  string  $postfix
     * @return void
     */
    public function setIndexNamePostfix(string $postfix)
    {
        $this->indexNamePostfix = $postfix;
    }

    /**
     * @param  Asset  $asset
     * @return bool
     */
    protected function isFulltextEnabled(Asset $asset)
    {
        // @todo introduce setting
        return true;
    }
}
