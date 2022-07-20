<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Client;

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

use CodeQ\AssetSearch\ElasticSearchClient;
use Flowpack\ElasticSearch\Domain\Model\Client;
use Flowpack\ElasticSearch\Exception;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class ClientFactory
{
    /**
     * @Flow\Inject
     * @var \Flowpack\ElasticSearch\Domain\Factory\ClientFactory
     */
    protected $clientFactory;

    /**
     * Create a client
     *
     * @return Client
     * @throws Exception
     */
    public function create(): Client
    {
        return $this->clientFactory->create(null, ElasticSearchClient::class);
    }
}
