<?php

namespace CodeQ\AssetSearch\Service;

use CodeQ\AssetSearch\Exception\ConfigurationException;
use Neos\Flow\Annotations as Flow;

class IndexNameStrategy implements IndexNameStrategyInterface
{
    /**
     * @var string
     * @Flow\InjectConfiguration(path="elasticSearch.indexName")
     */
    protected string $indexName;

    /**
     * @return string
     */
    public function get(): string
    {
        $name = $this->indexName;
        if ($name === '') {
            throw new ConfigurationException('Index name can not be null, check Settings at path: CodeQ.AssetSearch.elasticSearch.indexName', 1574327388);
        }

        return $name;
    }
}
