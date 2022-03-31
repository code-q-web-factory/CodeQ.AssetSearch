<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver\Version6;

use CodeQ\AssetSearch\Driver\AbstractDriver;
use CodeQ\AssetSearch\Driver\SystemDriverInterface;
use Neos\Flow\Annotations as Flow;

/**
 * System driver for Elasticsearch version 6.x
 *
 * @Flow\Scope("singleton")
 */
class SystemDriver extends AbstractDriver implements SystemDriverInterface
{
    /**
     * @inheritDoc
     */
    public function status(): array
    {
        return $this->searchClient->request('GET', '/_stats')->getTreatedContent();
    }
}
