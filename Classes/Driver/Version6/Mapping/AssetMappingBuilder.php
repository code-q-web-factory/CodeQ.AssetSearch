<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver\Version6\Mapping;

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

use CodeQ\AssetSearch\Driver\AbstractMappingBuilder;
use Flowpack\ElasticSearch\Domain\Model\Index;
use Flowpack\ElasticSearch\Domain\Model\Mapping;
use Flowpack\ElasticSearch\Mapping\MappingCollection;
use Neos\Error\Messages\Result;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\Asset;

class AssetMappingBuilder extends AbstractMappingBuilder
{
    /**
     * @Flow\InjectConfiguration(path="indexing.mapping")
     * @var array
     */
    protected array $mappingSettings;

    /**
     * @param  Index  $index
     * @return MappingCollection
     */
    public function buildMappingInformation(Index $index): MappingCollection
    {
        $this->lastMappingErrors = new Result();

        $mappings = new MappingCollection(MappingCollection::TYPE_ENTITY);

        $mapping = new Mapping($index->findType(Asset::class));
        $mapping->setFullMapping($this->mappingSettings);
        $mappings->add($mapping);

        return $mappings;
    }
}
