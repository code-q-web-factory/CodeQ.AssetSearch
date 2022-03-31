<?php

namespace CodeQ\AssetSearch\AssetExtraction;

use CodeQ\AssetSearch\Dto\AssetContent;
use Neos\Media\Domain\Model\AssetInterface;

interface AssetExtractorInterface
{
    /**
     * Takes an asset and extracts content and meta data.
     *
     * @param AssetInterface $asset
     * @return AssetContent
     */
    public function extract(AssetInterface $asset): AssetContent;
}
