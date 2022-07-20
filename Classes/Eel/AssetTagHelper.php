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

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Repository\TagRepository;

class AssetTagHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @return array
     */
    public function all()
    {
        return $this->tagRepository->findAll()->toArray();
    }

    /**
     * @param  AssetCollection  $collection
     * @return array
     */
    public function byCollection(AssetCollection $collection)
    {
        return $this->tagRepository->findByAssetCollections([$collection])->toArray();
    }

    /**
     * @param $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
