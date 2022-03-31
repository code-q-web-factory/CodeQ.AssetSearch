<?php

namespace CodeQ\AssetSearch\Factory;

use CodeQ\AssetSearch\Driver\QueryInterface;
use CodeQ\AssetSearch\Exception\ConfigurationException;
use Neos\Flow\Annotations as Flow;

/**
 * A factory for creating the ElasticSearch Query
 *
 * @Flow\Scope("singleton")
 */
class QueryFactory extends AbstractDriverSpecificObjectFactory
{
    /**
     * @return QueryInterface
     * @throws ConfigurationException
     */
    public function createQuery(): QueryInterface
    {
        return $this->resolve('query');
    }
}
