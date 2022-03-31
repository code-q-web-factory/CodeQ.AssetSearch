<?php

namespace CodeQ\AssetSearch\Indexer;

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
