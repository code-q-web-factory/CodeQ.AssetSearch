<?php

namespace CodeQ\AssetSearch;

use CodeQ\AssetSearch\Indexer\AssetIndexingManager;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Booting\Sequence;
use Neos\Flow\Core\Booting\Step;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Media\Domain\Service\AssetService;
use Neos\Flow\Package\Package as BasePackage;

/**
 * The Asset Search Package
 */
class Package extends BasePackage
{
    /**
     * Invokes custom PHP code directly after the package manager has been initialized.
     *
     * @param Bootstrap $bootstrap The current bootstrap
     *
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $package = $this;
        $dispatcher->connect(Sequence::class, 'afterInvokeStep', function (Step $step) use ($package, $bootstrap) {
            if ($step->getIdentifier() === 'neos.flow:objectmanagement:runtime') {
                $package->registerIndexingSlots($bootstrap);
            }
        });
    }

    /**
     * Registers slots for signals in order to be able to index nodes
     *
     * @param Bootstrap $bootstrap
     */
    public function registerIndexingSlots(Bootstrap $bootstrap)
    {
        $configurationManager = $bootstrap->getObjectManager()->get(ConfigurationManager::class);
        $settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $this->getPackageKey());
        if (isset($settings['indexing']['realtimeIndexing']['enabled']) && $settings['indexing']['realtimeIndexing']['enabled'] === true) {
            // handle changes to nodes
            $bootstrap->getSignalSlotDispatcher()->connect(AssetService::class, 'assetCreated', AssetIndexingManager::class, 'indexAsset', false);
            $bootstrap->getSignalSlotDispatcher()->connect(AssetService::class, 'assetUpdated', AssetIndexingManager::class, 'indexAsset', false);
            $bootstrap->getSignalSlotDispatcher()->connect(AssetService::class, 'assetResourceReplaced', AssetIndexingManager::class, 'indexAsset', false);
            $bootstrap->getSignalSlotDispatcher()->connect(AssetService::class, 'assetRemoved', AssetIndexingManager::class, 'removeAsset', false);
            // make sure we always flush at the end, regardless of indexingBatchSize
            $bootstrap->getSignalSlotDispatcher()->connect(PersistenceManager::class, 'allObjectsPersisted', AssetIndexingManager::class, 'flushQueues', false);
        }
    }
}
