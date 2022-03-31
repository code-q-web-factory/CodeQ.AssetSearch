<?php

namespace CodeQ\AssetSearch\Dto;

class SearchResult
{
    /**
     * @var int
     */
    protected $total;

    /**
     * @var array
     */
    protected $hits;

    public function __construct(array $hits, int $total)
    {
        $this->hits = $hits;
        $this->total = $total;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return array
     */
    public function getHits(): array
    {
        return $this->hits;
    }
}
