<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Service;

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
