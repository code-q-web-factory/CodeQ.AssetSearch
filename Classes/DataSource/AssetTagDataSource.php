<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\DataSource;

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
