<?php

namespace CodeQ\AssetSearch\Eel;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Repository\AssetCollectionRepository;

class AssetCollectionHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * @return array
     */
    public function all()
    {
        return $this->assetCollectionRepository->findAll()->toArray();
    }

    /**
     * @param  string  $identifier
     * @return object|null
     */
    public function byIdentifier(string $identifier)
    {
        return $this->assetCollectionRepository->findByIdentifier($identifier);
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
