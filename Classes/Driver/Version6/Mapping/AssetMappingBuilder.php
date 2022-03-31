<?php

namespace CodeQ\AssetSearch\Driver\Version6\Mapping;

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
