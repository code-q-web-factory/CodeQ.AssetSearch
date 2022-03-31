<?php

namespace CodeQ\AssetSearch\Factory;

use CodeQ\AssetSearch\Driver\DocumentDriverInterface;
use CodeQ\AssetSearch\Driver\IndexDriverInterface;
use CodeQ\AssetSearch\Driver\IndexerDriverInterface;
use CodeQ\AssetSearch\Driver\RequestDriverInterface;
use CodeQ\AssetSearch\Driver\SystemDriverInterface;
use CodeQ\AssetSearch\Exception\ConfigurationException;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class DriverFactory extends AbstractDriverSpecificObjectFactory
{
    /**
     * @return DocumentDriverInterface
     * @throws ConfigurationException
     */
    public function createDocumentDriver(): DocumentDriverInterface
    {
        return $this->resolve('document');
    }

    /**
     * @return IndexerDriverInterface
     * @throws ConfigurationException
     */
    public function createIndexerDriver(): IndexerDriverInterface
    {
        return $this->resolve('indexer');
    }

    /**
     * @return IndexDriverInterface
     * @throws ConfigurationException
     */
    public function createIndexManagementDriver(): IndexDriverInterface
    {
        return $this->resolve('indexManagement');
    }

    /**
     * @return RequestDriverInterface
     * @throws ConfigurationException
     */
    public function createRequestDriver(): RequestDriverInterface
    {
        return $this->resolve('request');
    }

    /**
     * @return SystemDriverInterface
     * @throws ConfigurationException
     */
    public function createSystemDriver(): SystemDriverInterface
    {
        return $this->resolve('system');
    }
}
