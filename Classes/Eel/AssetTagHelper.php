<?php

namespace CodeQ\AssetSearch\Eel;

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
