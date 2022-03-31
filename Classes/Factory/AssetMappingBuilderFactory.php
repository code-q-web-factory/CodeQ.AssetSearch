<?php

namespace CodeQ\AssetSearch\Factory;

use CodeQ\AssetSearch\Driver\AssetMappingBuilderInterface;
use CodeQ\AssetSearch\Exception\ConfigurationException;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class AssetMappingBuilderFactory extends AbstractDriverSpecificObjectFactory
{
    /**
     * @return AssetMappingBuilderInterface
     * @throws ConfigurationException
     */
    public function createAssetMappingBuilder(): AssetMappingBuilderInterface
    {
        return $this->resolve('assetMappingBuilder');
    }
}
