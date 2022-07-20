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

use CodeQ\AssetSearch\Driver\AbstractDriver;
use CodeQ\AssetSearch\Driver\RequestDriverInterface;
use Flowpack\ElasticSearch\Domain\Model\Index;
use Flowpack\ElasticSearch\Exception;
use Neos\Flow\Annotations as Flow;

/**
 * Request driver for Elasticsearch version 6.x
 *
 * @Flow\Scope("singleton")
 */
class RequestDriver extends AbstractDriver implements RequestDriverInterface
{
    /**
     * {@inheritdoc}
     * @throws Exception
     * @throws \Neos\Flow\Http\Exception
     */
    public function bulk(Index $index, $request): array
    {
        if (is_array($request)) {
            $request = json_encode($request);
        }

        // Bulk request MUST end with line return
        $request = trim($request) . "\n";
        return $index->request('POST', '/_bulk', [], $request)->getTreatedContent();
    }
}
