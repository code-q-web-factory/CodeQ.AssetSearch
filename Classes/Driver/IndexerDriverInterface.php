<?php

declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver;

use Flowpack\ElasticSearch\Domain\Model\Document as ElasticSearchDocument;
use Neos\Media\Domain\Model\Asset;

/**
 * Indexer Driver Interface
 */
interface IndexerDriverInterface
{
    /**
     * Generate the query to index the document properties
     *
     * @param  string  $indexName
     * @param  Asset  $asset
     * @param  ElasticSearchDocument  $document
     * @param  array  $documentData
     * @return array
     */
    public function document(string $indexName, Asset $asset, ElasticSearchDocument $document, array $documentData): array;

    /**
     * Generate the query to index the fulltext of the document
     *
     * @param  Asset  $asset
     * @param  array  $fulltextIndexOfAsset
     * @return array
     */
    public function fulltext(Asset $asset, array $fulltextIndexOfAsset): array;
}
