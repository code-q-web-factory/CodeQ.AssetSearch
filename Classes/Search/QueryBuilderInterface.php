<?php

namespace CodeQ\AssetSearch\Search;

use Neos\Media\Domain\Model\Asset;
use Traversable;

interface QueryBuilderInterface
{
    /**
     * Sort descending by $propertyName
     *
     * @param string $propertyName the property name to sort by
     * @return QueryBuilderInterface
     */
    public function sortDesc(string $propertyName): QueryBuilderInterface;

    /**
     * Sort ascending by $propertyName
     *
     * @param string $propertyName the property name to sort by
     * @return QueryBuilderInterface
     */
    public function sortAsc(string $propertyName): QueryBuilderInterface;

    /**
     * output only $limit records
     *
     * @param int $limit
     * @return QueryBuilderInterface
     */
    public function limit($limit): QueryBuilderInterface;

    /**
     * output records starting at $from
     *
     *
     * @param integer $from
     * @return QueryBuilderInterface
     */
    public function from($from): QueryBuilderInterface;

    /**
     * add an exact-match query for a given property
     *
     * @param string $propertyName
     * @param mixed $propertyPropertyValue
     * @return QueryBuilderInterface
     */
    public function exactMatch(string $propertyName, $propertyPropertyValue): QueryBuilderInterface;

    /**
     * Match the searchword against the fulltext index
     *
     * @param string $searchWord
     * @param array $options
     * @return QueryBuilderInterface
     */
    public function fulltext(string $searchWord, array $options = []): QueryBuilderInterface;

    /**
     * Execute the query and return the list of assets as result
     *
     * @return Traversable<Asset>
     */
    public function execute(): Traversable;

    /**
     * Return the total number of hits for the query.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Sets the starting point for this query. Search result should only contain assets that
     * match the context of the given node and have it as parent node in their rootline.
     *
     * @return QueryBuilderInterface
     */
    public function query(): QueryBuilderInterface;

    /**
     * Filter by asset type, taking inheritance into account.
     *
     * @param string $nodeType the node type to filter for
     * @return QueryBuilderInterface
     */
    public function assetType(string $nodeType): QueryBuilderInterface;
}
