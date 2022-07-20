<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch;

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
