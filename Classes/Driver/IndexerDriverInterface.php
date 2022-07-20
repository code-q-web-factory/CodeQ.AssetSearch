<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver;

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
