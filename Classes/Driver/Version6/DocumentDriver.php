<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver\Version6;

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

use CodeQ\AssetSearch\Driver\AbstractDriver;
use CodeQ\AssetSearch\Driver\DocumentDriverInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\Asset;

/**
 * Document driver for Elasticsearch version 6.x
 *
 * @Flow\Scope("singleton")
 */
class DocumentDriver extends AbstractDriver implements DocumentDriverInterface
{
    /**
     * {@inheritdoc}
     */
    public function delete(Asset $asset, string $identifier): array
    {
        return [
            [
                'delete' => [
                    '_type' =>'_doc',
                    '_id' => $identifier
                ]
            ]
        ];
    }
}
