<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Eel;

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

use CodeQ\AssetSearch\Search\QueryBuilderInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Media\Domain\Model\AssetCollection;

/**
 * Eel Helper to start search queries
 */
class SearchHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Create a new Search Query
     *
     * @param AssetCollection|null $assetCollection
     *
     * @return QueryBuilderInterface
     */
    public function query(AssetCollection $assetCollection = null): QueryBuilderInterface
    {
        $queryBuilder = $this->objectManager->get(QueryBuilderInterface::class);

        return $queryBuilder->query($assetCollection);
    }

    /**
     * @param  string  $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
