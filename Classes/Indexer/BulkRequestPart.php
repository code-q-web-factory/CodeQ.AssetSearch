<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Indexer;

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

use Generator;

class BulkRequestPart
{
    /**
     * JSON Payload of the current requests
     * @var string
     */
    protected $requests = [];

    /**
     * Size in octet of the current request
     * @var int
     */
    protected $size = 0;

    public function __construct(array $requests)
    {
        $this->requests = array_map(function (array $request) {
            $data = json_encode($request);
            if ($data === false) {
                return null;
            }
            $this->size += strlen($data);
            return $data;
        }, $requests);
    }

    public function getRequest(): Generator
    {
        foreach ($this->requests as $request) {
            yield $request;
        }
    }

    public function getSize(): int
    {
        return $this->size;
    }
}
