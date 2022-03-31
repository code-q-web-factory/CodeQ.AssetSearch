<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Driver\Version6;

use CodeQ\AssetSearch\Driver\AbstractDriver;
use CodeQ\AssetSearch\Driver\RequestDriverInterface;
use Flowpack\ElasticSearch\Domain\Model\Index;
use Flowpack\ElasticSearch\Exception;
use Neos\Flow\Annotations as Flow;

/**
 * Request driver for Elasticsearch version 6.x
 *
 * @Flow\Scope("singleton")
 */
class RequestDriver extends AbstractDriver implements RequestDriverInterface
{
    /**
     * {@inheritdoc}
     * @throws Exception
     * @throws \Neos\Flow\Http\Exception
     */
    public function bulk(Index $index, $request): array
    {
        if (is_array($request)) {
            $request = json_encode($request);
        }

        // Bulk request MUST end with line return
        $request = trim($request) . "\n";
        return $index->request('POST', '/_bulk', [], $request)->getTreatedContent();
    }
}
