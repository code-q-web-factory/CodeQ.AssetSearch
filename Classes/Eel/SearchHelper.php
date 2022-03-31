<?php

namespace CodeQ\AssetSearch\Eel;

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
