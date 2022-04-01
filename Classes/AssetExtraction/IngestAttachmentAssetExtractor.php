<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\AssetExtraction;

use Flowpack\ElasticSearch\Exception;
use Neos\Flow\Annotations as Flow;
use CodeQ\AssetSearch\Dto\AssetContent;
use CodeQ\AssetSearch\ElasticSearchClient;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Utility\Arrays;
use Psr\Log\LoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
class IngestAttachmentAssetExtractor implements AssetExtractorInterface
{
    /**
     * @Flow\Inject
     * @var ElasticSearchClient
     */
    protected $elasticsearchClient;

    /**
     * @Flow\Inject
     * @var ThrowableStorageInterface
     */
    protected $throwableStorage;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\InjectConfiguration(path="indexing.assetExtraction.maximumFileSize")
     * @var int
     */
    protected $maximumFileSize;

    /**
     * Takes an asset and extracts content and meta data.
     *
     * @param AssetInterface $asset
     * @return AssetContent
     */
    public function extract(AssetInterface $asset): AssetContent
    {
        if ($asset->getResource()->getFileSize() > $this->maximumFileSize) {
            $this->logger->info(sprintf('The asset %s with size of %s bytes exceeds the maximum size of %s bytes. The file content was not ingested.', $asset->getResource()->getFilename(), $asset->getResource()->getFileSize(), $this->maximumFileSize), LogEnvironment::fromMethodName(__METHOD__));
            return $this->buildAssetContentObject([]);
        }

        $extractedAsset = null;

        try {
            $request = [
                'pipeline' => [
                    'description' => 'Attachment Extraction',
                    'processors' => [
                        [
                            'attachment' => [
                                'field' => 'neos_asset',
                                'indexed_chars' => 100000,
                                'ignore_missing' => true,
                            ]
                        ]
                    ]
                ],
                'docs' => [
                    [
                        '_source' => [
                            'neos_asset' => $this->getAssetContent($asset)
                        ]
                    ]
                ]
            ];

            $result = $this->elasticsearchClient->request('POST', '_ingest/pipeline/_simulate', [], json_encode($request))->getTreatedContent();

            if (is_array($result)) {
                $extractedAsset = Arrays::getValueByPath($result, 'docs.0.doc._source.attachment');
            }

            if (!is_array($extractedAsset)) {
                $this->logger->error(sprintf('Error while extracting fulltext data from file "%s". See Elasticsearch error log line for details.', $asset->getResource()->getFilename()),
                    LogEnvironment::fromMethodName(__METHOD__));
            } else {
                $this->logger->debug(sprintf('Extracted asset %s of type %s. Extracted %s characters of content', $asset->getResource()->getFilename(),
                    $extractedAsset['content_type'] ?? '-no-content-type-', $extractedAsset['content_length'] ?? '0'), LogEnvironment::fromMethodName(__METHOD__));
            }

            return $this->buildAssetContentObject($extractedAsset);
        } catch (Exception $e) {
            $this->logger->warning(sprintf('The asset %s with size of %s bytes could not be ingested.', $asset->getResource()->getFilename(), $asset->getResource()->getFileSize()), LogEnvironment::fromMethodName(__METHOD__));
            return $this->buildAssetContentObject([]);
        }
    }

    /**
     * @param AssetInterface $asset
     * @return string
     */
    protected function getAssetContent(AssetInterface $asset): string
    {
        try {
            $stream = $asset->getResource()->getStream();
        } catch (\Exception $e) {
            $message = $this->throwableStorage->logThrowable($e);
            $this->logger->error(sprintf('An exception occured while fetching resource with sha1 %s of asset %s. %s', $asset->getResource()->getSha1(), $asset->getResource()->getFilename(), $message), LogEnvironment::fromMethodName(__METHOD__));
            return '';
        }

        if ($stream === false) {
            $this->logger->error(sprintf('Could not get the file stream of resource with sha1 %s of asset %s.', $asset->getResource()->getSha1(), $asset->getResource()->getFilename()), LogEnvironment::fromMethodName(__METHOD__));
            return '';
        }

        stream_filter_append($stream, 'convert.base64-encode');
        $result = stream_get_contents($stream);
        return $result !== false ? $result : '';
    }

    /**
     * @param  array|null  $extractedAsset
     * @return AssetContent
     */
    protected function buildAssetContentObject(?array $extractedAsset): AssetContent
    {
        return new AssetContent(
            $extractedAsset['content'] ?? '',
            $extractedAsset['title'] ?? '',
            $extractedAsset['name'] ?? '',
            $extractedAsset['author'] ?? '',
            $extractedAsset['keywords'] ?? '',
            $extractedAsset['date'] ?? '',
            $extractedAsset['content_type'] ?? '',
            $extractedAsset['content_length'] ?? 0,
            $extractedAsset['language'] ?? ''
        );
    }
}
