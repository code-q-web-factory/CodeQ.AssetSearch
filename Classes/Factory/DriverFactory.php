<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Factory;

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
