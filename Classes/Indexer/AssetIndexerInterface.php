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

use Neos\Media\Domain\Model\Asset;

interface AssetIndexerInterface
{
    /**
     * Schedule an asset for indexing
     *
     * @param Asset $asset
     * @return void
     */
    public function indexAsset(Asset $asset): void;

    /**
     * Schedule an asset for removal of the index
     *
     * @param Asset $node
     * @return void
     */
    public function removeAsset(Asset $asset): void;

    /**
     * Perform all changes to the index queued up. If an implementation directly changes the index this can be no operation.
     *
     * @return void
     */
    public function flush(): void;
}
