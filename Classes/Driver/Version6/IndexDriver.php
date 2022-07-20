<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver\Version6;

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

use CodeQ\AssetSearch\Exception;
use CodeQ\AssetSearch\Driver\AbstractDriver;
use CodeQ\AssetSearch\Driver\IndexDriverInterface;
use CodeQ\AssetSearch\Service\IndexNameService;
use Flowpack\ElasticSearch\Transfer\Exception\ApiException;
use Neos\Flow\Annotations as Flow;

/**
 * Index management driver for Elasticsearch version 6.x
 *
 * @Flow\Scope("singleton")
 */
class IndexDriver extends AbstractDriver implements IndexDriverInterface
{
    /**
     * {@inheritdoc}
     */
    public function aliasActions(array $actions)
    {
        $this->searchClient->request('POST', '/_aliases', [], \json_encode(['actions' => $actions]));
    }

    /**
     * @param string $index
     * @throws ApiException
     */
    public function deleteIndex(string $index): void
    {
        $response = $this->searchClient->request('HEAD', '/' . $index);
        if ($response->getStatusCode() === 200) {
            $response = $this->searchClient->request('DELETE', '/' . $index);
            if ($response->getStatusCode() !== 200) {
                throw new \Neos\Flow\Exception('The index "' . $index . '" could not be deleted. (return code: ' . $response->getStatusCode() . ')', 1395419177);
            }
        }
    }

    /**
     * @param string $alias
     * @return array
     * @throws ApiException
     */
    public function getIndexNamesByAlias(string $alias): array
    {
        $response = $this->searchClient->request('GET', '/_alias/' . $alias);
        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200 && $statusCode !== 404) {
            throw new \Neos\Flow\Exception('The alias "' . $alias . '" was not found with some unexpected error... (return code: ' . $statusCode . ')', 1383650137);
        }

        // return empty array if content from response cannot be read as an array
        $treatedContent = $response->getTreatedContent();

        return is_array($treatedContent) ? array_keys($treatedContent) : [];
    }

    /**
     * @param string $prefix
     * @return array
     * @throws ApiException
     */
    public function getIndexNamesByPrefix(string $prefix): array
    {
        $treatedContent = $this->searchClient->request('GET', '/_alias/')->getTreatedContent();

        // return empty array if content from response cannot be read as an array
        if (!\is_array($treatedContent)) {
            return [];
        }

        return \array_filter(\array_keys($treatedContent), static function ($indexName) use ($prefix) {
            $prefix .= IndexNameService::INDEX_PART_SEPARATOR;
            return strpos($indexName, $prefix) === 0;
        });
    }
}
