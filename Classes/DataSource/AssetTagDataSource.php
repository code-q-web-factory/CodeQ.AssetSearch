<?php

namespace CodeQ\AssetSearch\DataSource;

use Behat\Transliterator\Transliterator;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\Neos\Service\DataSource\AbstractDataSource;

class AssetTagDataSource extends AbstractDataSource
{
    /**
     * @var string
     */
    static protected $identifier = 'codeq-assetsearch-assettags';

    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $collectionRepository;

    /**
     * @Flow\Inject
     * @var TagRepository
     */
    protected $tagRepository;

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
        $tags = [];

        if (array_key_exists('collection', $arguments) && $arguments['collection'] instanceof AssetCollection) {
            $tagObjects = $this->tagRepository->findByAssetCollections([$arguments['collection']]);
        } else if (array_key_exists('collection', $arguments) && is_string($arguments['collection'])) {
            $collection = $this->collectionRepository->findByIdentifier($arguments['collection']);
            $tagObjects = $this->tagRepository->findByAssetCollections([$collection]);
        } else {
            $tagObjects = $this->tagRepository->findAll();
        }

        /** @var Tag $tagObject */
        foreach($tagObjects as $tagObject) {
            $tags[] = [
                'value' => Transliterator::urlize($tagObject->getLabel()),
                'label' => $tagObject->getLabel(),
            ];
        }
        return $tags;
    }
}
