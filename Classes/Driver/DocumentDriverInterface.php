<?php

declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver;

use Neos\Media\Domain\Model\Asset;

/**
 * Elasticsearch Document Driver Interface
 */
interface DocumentDriverInterface
{
    /**
     * Generate the query to delete Elastic document for the give node
     *
     * @param  Asset  $asset
     * @param  string  $identifier
     * @return array
     */
    public function delete(Asset $asset, string $identifier): array;
}
