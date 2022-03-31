<?php

namespace CodeQ\AssetSearch\Indexer;

use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetInterface;
use SplObjectStorage;

/**
 * @Flow\Scope("singleton")
 */
class AssetIndexingManager
{
    /**
     * @var SplObjectStorage<Asset>
     */
    protected $assetsToBeIndexed;

    /**
     * @var SplObjectStorage<Asset>
     */
    protected $assetsToBeRemoved;

    /**
     * the indexing batch size (from the settings)
     *
     * @Flow\InjectConfiguration(path="indexing.batchSize.elements")
     * @var integer
     */
    protected $indexingBatchSize;

    /**
     * @Flow\Inject
     * @var AssetIndexerInterface
     */
    protected $assetIndexer;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->assetsToBeIndexed = new SplObjectStorage();
        $this->assetsToBeRemoved = new SplObjectStorage();
    }

    /**
     * Schedule a node for indexing
     *
     * @param AssetInterface $asset
     * @return void
     */
    public function indexAsset(AssetInterface $asset)
    {
        $this->assetsToBeRemoved->detach($asset);
        $this->assetsToBeIndexed->attach($asset);

        $this->flushQueuesIfNeeded();
    }

    /**
     * Schedule a node for removal of the index
     *
     * @param AssetInterface $asset
     * @return void
     */
    public function removeAsset(AssetInterface $asset)
    {
        $this->assetsToBeIndexed->detach($asset);
        $this->assetsToBeRemoved->attach($asset);

        $this->flushQueuesIfNeeded();
    }

    /**
     * Flush the indexing/removal queues, actually processing them, if the
     * maximum indexing batch size has been reached.
     *
     * @return void
     */
    protected function flushQueuesIfNeeded()
    {
        if ($this->assetsToBeIndexed->count() + $this->assetsToBeRemoved->count() > $this->indexingBatchSize) {
            $this->flushQueues();
        }
    }

    /**
     * Flush the indexing/removal queues, actually processing them.
     *
     * @return void
     */
    public function flushQueues()
    {
        /** @var AssetInterface $assetToBeIndexed */
        foreach ($this->assetsToBeIndexed as $assetToBeIndexed) {
            $this->assetIndexer->indexAsset($assetToBeIndexed);
        }

        /** @var AssetInterface $assetToBeRemoved */
        foreach ($this->assetsToBeRemoved as $assetToBeRemoved) {
            $this->assetIndexer->removeAsset($assetToBeRemoved);
        }

        $this->assetIndexer->flush();
        $this->assetsToBeIndexed = new SplObjectStorage();
        $this->assetsToBeRemoved = new SplObjectStorage();
    }
}
