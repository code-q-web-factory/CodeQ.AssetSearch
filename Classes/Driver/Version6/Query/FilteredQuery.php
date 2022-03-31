<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver\Version6\Query;

use CodeQ\AssetSearch\Driver\AbstractQuery;
use CodeQ\AssetSearch\Exception\QueryBuildingException;

/**
 * Filtered query for Elasticsearch version 6.x
 */
class FilteredQuery extends AbstractQuery
{

    /**
     * {@inheritdoc}
     */
    public function getCountRequestAsJson(): string
    {
        $request = $this->request;
        foreach ($this->unsupportedFieldsInCountRequest as $field) {
            if (isset($request[$field])) {
                unset($request[$field]);
            }
        }

        return json_encode($request);
    }

    /**
     * {@inheritdoc}
     */
    public function size(int $size): void
    {
        $this->request['size'] = $size;
    }

    /**
     * {@inheritdoc}
     */
    public function from(int $size): void
    {
        $this->request['from'] = $size;
    }

    /**
     * {@inheritdoc}
     */
    public function fulltext(string $searchWord, array $options = []): void
    {
        $this->appendAtPath('query.bool.must', [
            'query_string' => array_merge(
                $this->queryStringParameters,
                $options,
                [ 'query' => $searchWord ]
            )
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function queryFilter(string $filterType, $filterOptions, string $clauseType = 'must'): void
    {
        if (!in_array($clauseType, ['must', 'should', 'must_not', 'filter'])) {
            throw new QueryBuildingException('The given clause type "' . $clauseType . '" is not supported. Must be one of "must", "should", "must_not".', 1383716082);
        }

        $this->appendAtPath('query.bool.filter.bool.' . $clauseType, [$filterType => $filterOptions]);
    }
}
