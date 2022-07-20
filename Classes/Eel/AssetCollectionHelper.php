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
