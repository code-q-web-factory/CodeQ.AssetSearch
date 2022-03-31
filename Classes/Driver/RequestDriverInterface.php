<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver;

use Flowpack\ElasticSearch\Domain\Model\Index;

/**
 * Elasticsearch Request Driver Interface
 */
interface RequestDriverInterface
{
    /**
     * Execute a bulk request
     *
     * @param Index $index
     * @param array|string $request an array or a raw JSON request payload
     * @return array An array of respones per batch entry.
     */
    public function bulk(Index $index, $request): array;
}
