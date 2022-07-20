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
