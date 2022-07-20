<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\AssetExtraction;

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
