<?php

namespace CodeQ\AssetSearch\Indexer;

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
