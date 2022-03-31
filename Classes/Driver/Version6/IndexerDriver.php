<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver\Version6;

use CodeQ\AssetSearch\Driver\AbstractIndexerDriver;
use CodeQ\AssetSearch\Driver\IndexerDriverInterface;
use Flowpack\ElasticSearch\Domain\Model\Document as ElasticSearchDocument;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\Asset;

/**
 * Indexer driver for Elasticsearch version 6.x
 *
 * @Flow\Scope("singleton")
 */
class IndexerDriver extends AbstractIndexerDriver implements IndexerDriverInterface
{
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * {@inheritdoc}
     */
    public function document(string $indexName, Asset $asset, ElasticSearchDocument $document, array $documentData): array
    {
        return [
            [
                'update' => [
                    '_type' => '_doc',
                    '_id' => $document->getId(),
                    '_index' => $indexName,
                    'retry_on_conflict' => 3
                ]
            ],
            // http://www.elasticsearch.org/guide/en/elasticsearch/reference/5.0/docs-update.html
            [
                'script' => [
                    'lang' => 'painless',
                    'source' => '
                        HashMap fulltext = (ctx._source.containsKey("neos_fulltext") && ctx._source.neos_fulltext instanceof Map ? ctx._source.neos_fulltext : new HashMap());
                        HashMap fulltextParts = (ctx._source.containsKey("neos_fulltext_parts") && ctx._source.neos_fulltext_parts instanceof Map ? ctx._source.neos_fulltext_parts : new HashMap());
                        ctx._source = params.newData;
                        ctx._source.neos_fulltext = fulltext;
                        ctx._source.neos_fulltext_parts = fulltextParts',
                    'params' => [
                        'newData' => $documentData
                    ]
                ],
                'upsert' => $documentData
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fulltext(Asset $asset, array $fulltextIndexOfAsset): array
    {
        $upsertFulltextParts = [];

        return [
            [
                'update' => [
                    '_type' => '_doc',
                    '_id' => $asset->getIdentifier()
                ]
            ],
            // http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/docs-update.html
            [
                // first, update the neos_fulltext_parts, then re-generate the neos_fulltext from all neos_fulltext_parts
                'script' => [
                    'lang' => 'painless',
                    'source' => '
                        ctx._source.neos_fulltext = new HashMap();
                        if (!ctx._source.containsKey("neos_fulltext_parts") || !(ctx._source.neos_fulltext_parts instanceof Map)) {
                            ctx._source.neos_fulltext_parts = new HashMap();
                        }

                        ctx._source.neos_fulltext_parts.put(params.identifier, params.fulltext);

                        for (fulltextPart in ctx._source.neos_fulltext_parts.entrySet()) {
                            for (entry in fulltextPart.getValue().entrySet()) {
                                def value = "";
                                if (ctx._source.neos_fulltext.containsKey(entry.getKey())) {
                                    value = ctx._source.neos_fulltext[entry.getKey()] + " " + entry.getValue().trim();
                                } else {
                                    value = entry.getValue().trim();
                                }
                                ctx._source.neos_fulltext[entry.getKey()] = value;
                            }
                        }',
                    'params' => [
                        'identifier' => $asset->getIdentifier(),
                        'fulltext' => $fulltextIndexOfAsset
                    ],
                ],
                'upsert' => [
                    'neos_fulltext' => $fulltextIndexOfAsset,
                    'neos_fulltext_parts' => $upsertFulltextParts
                ]
            ]
        ];
    }
}
