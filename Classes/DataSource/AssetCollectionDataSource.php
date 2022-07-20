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
