<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver\Version6;

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
