<?php

declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver;

/**
 * System Driver Interface
 */
interface SystemDriverInterface
{
    /**
     * Get the status of the Elastic cluster
     *
     * @return array
     */
    public function status(): array;
}
