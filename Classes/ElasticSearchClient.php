<?php

namespace CodeQ\AssetSearch;

use CodeQ\AssetSearch\Exception\ConfigurationException;
use CodeQ\AssetSearch\Service\IndexNameStrategyInterface;
use Flowpack\ElasticSearch\Domain\Model\Client;
use Flowpack\ElasticSearch\Domain\Model\Index;
use Flowpack\ElasticSearch\Exception;
use Neos\Flow\Annotations as Flow;

class ElasticSearchClient extends Client
{

    /**
     * @Flow\Inject
     * @var IndexNameStrategyInterface
     */
    protected $indexNameStrategy;

    /**
     * @return string
     */
    public function getIndexNamePrefix(): string
    {
        $name = trim($this->indexNameStrategy->get());
        if ($name === '') {
            throw new ConfigurationException('IndexNameStrategy ' . get_class($this->indexNameStrategy) . ' returned an empty index name', 1582538800);
        }

        return $name;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getIndexName(): string
    {
        return $this->getIndexNamePrefix();
    }

    /**
     * @return Index
     * @throws Exception
     */
    public function getIndex(): Index
    {
        return $this->findIndex($this->getIndexName());
    }
}
