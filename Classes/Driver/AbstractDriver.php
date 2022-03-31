<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver;

use CodeQ\AssetSearch\ElasticSearchClient;
use Psr\Log\LoggerInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Abstract Elasticsearch driver
 */
abstract class AbstractDriver
{
    /**
     * @Flow\Inject
     * @var ElasticSearchClient
     */
    protected $searchClient;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;
}
