<?php

namespace CodeQ\AssetSearch\Client;

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
