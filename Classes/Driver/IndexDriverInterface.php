<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver;

/**
 * Elasticsearch Index Driver Interface
 */
interface IndexDriverInterface
{

    /**
     * Get the list of Indexes attached to the given alias
     *
     * @param string $alias
     * @return array
     */
    public function getIndexNamesByAlias(string $alias): array;

    /**
     * Get the list of Indexes attached to the given alias prefix
     *
     * @param string $prefix
     * @return array
     */
    public function getIndexNamesByPrefix(string $prefix): array;

    /**
     * Remove alias by name
     *
     * @param string $index
     * @return void
     */
    public function deleteIndex(string $index): void;

    /**
     * Execute batch aliases actions
     *
     * @param array $actions
     * @return mixed
     */
    public function aliasActions(array $actions);
}
