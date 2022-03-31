<?php

namespace CodeQ\AssetSearch\DataSource;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Neos\Service\DataSource\AbstractDataSource;

class AssetCollectionDataSource extends AbstractDataSource
{
    /**
     * @var string
     */
    static protected $identifier = 'codeq-assetsearch-assetcollections';

    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $collectionRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @inheritDoc
     */
    public function getData(NodeInterface $node = null, array $arguments = [])
    {
        $collections = [];
        /** @var AssetCollection $assetCollection */
        foreach($this->collectionRepository->findAll() as $assetCollection) {
            $collections[] = [
                'value' => $this->persistenceManager->getIdentifierByObject($assetCollection),
                'label' => $assetCollection->getTitle(),
            ];
        }
        return $collections;
    }
}
