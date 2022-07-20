<?php
declare(strict_types=1);

namespace CodeQ\AssetSearch\Command;

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

use CodeQ\AssetSearch\Driver\AssetMappingBuilderInterface;
use CodeQ\AssetSearch\Driver\IndexDriverInterface;
use CodeQ\AssetSearch\ElasticSearchClient;
use CodeQ\AssetSearch\ErrorHandling\ErrorHandlingService;
use CodeQ\AssetSearch\Exception\RuntimeException;
use CodeQ\AssetSearch\Indexer\AssetIndexerInterface;
use CodeQ\AssetSearch\Service\IndexNameService;
use Flowpack\ElasticSearch\Domain\Model\Mapping;
use Flowpack\ElasticSearch\Transfer\Exception\ApiException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Booting\Scripts;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Utility\Files;
use Psr\Log\LoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
class AssetIndexCommandController extends CommandController
{
    /**
     * @Flow\InjectConfiguration(package="Neos.Flow")
     * @var array
     */
    protected $flowSettings;

    /**
     * @var array
     * @Flow\InjectConfiguration(package="Neos.ContentRepository.Search")
     */
    protected $settings;

    /**
     * @var bool
     * @Flow\InjectConfiguration(path="command.useSubProcesses")
     */
    protected $useSubProcesses = true;

    /**
     * @Flow\Inject
     * @var ErrorHandlingService
     */
    protected $errorHandlingService;

    /**
     * @Flow\Inject
     * @var AssetIndexerInterface
     */
    protected $assetIndexer;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @Flow\Inject
     * @var ElasticSearchClient
     */
    protected $searchClient;

    /**
     * @var IndexDriverInterface
     * @Flow\Inject
     */
    protected $indexDriver;

    /**
     * @Flow\Inject
     * @var AssetMappingBuilderInterface
     */
    protected $assetMappingBuilder;

    /**
     * Index all assets by creating a new index and when everything was completed, switch the index alias.
     *
     * This command (re-)indexes all assets contained in the content repository and sets the schema beforehand.
     *
     * @param  int|null  $limit  Amount of assets to index at maximum
     * @param  bool  $update  if TRUE, do not throw away the index at the start. Should *only be used for development*.
     * @param  string|null  $postfix
     * @return void
     * @throws ApiException
     */
    public function buildCommand(int $limit = null, bool $update = false, string $postfix = null): void
    {
        $this->logger->info(sprintf('Starting elasticsearch indexing %s sub processes', $this->useSubProcesses ? 'with' : 'without'), LogEnvironment::fromMethodName(__METHOD__));

        $postfix = (string)($postfix ?: time());
        $this->assetIndexer->setIndexNamePostfix($postfix);

        $createIndicesAndApplyMapping = function () use ($update, $postfix) {
            $this->executeInternalCommand('createInternal', [
                'update' => $update,
                'postfix' => $postfix,
            ]);
        };

        $buildIndex = function () use ($limit) {
            $this->build($limit);
        };

        $refresh = function () use ($postfix) {
            $this->executeInternalCommand('refreshInternal', [
                'postfix' => $postfix,
            ]);
        };

        $updateAliases = function () use ($update, $postfix) {
            $this->executeInternalCommand('aliasInternal', [
                'update' => $update,
                'postfix' => $postfix,
            ]);
        };

        $runAndLog = function ($command, string $stepInfo) {
            $timeStart = microtime(true);
            $this->output(str_pad($stepInfo . '... ', 20));
            $command();
            $this->outputLine('<success>Done</success> (took %s seconds)', [number_format(microtime(true) - $timeStart, 2)]);
        };

        $runAndLog($createIndicesAndApplyMapping, 'Creating indices and apply mapping');

        if ($this->aliasesExist() === false) {
            $runAndLog($updateAliases, 'Set up aliases');
        }

        $runAndLog($buildIndex, 'Indexing assets');

        $runAndLog($refresh, 'Refresh indicies');
        $runAndLog($updateAliases, 'Update aliases');

        $this->outputLine('Update main alias');
        $this->assetIndexer->updateMainAlias();

        $this->outputLine();
        $this->outputMemoryUsage();
    }

    /**
     * @return bool
     * @throws ApiException
     */
    private function aliasesExist(): bool
    {
        $aliasName = $this->searchClient->getIndexName();
        $aliasesExist = false;
        try {
            $aliasesExist = $this->indexDriver->getIndexNamesByAlias($aliasName) !== [];
        } catch (ApiException $exception) {
            // in case of 404, do not throw an error...
            if ($exception->getResponse()->getStatusCode() !== 404) {
                throw $exception;
            }
        }

        return $aliasesExist;
    }

    /**
     * @param  int|null  $limit
     * @return void
     * @throws RuntimeException
     */
    private function build(?int $limit = null): void
    {
        $this->logger->info(vsprintf('Indexing %s assets to %s', [($limit !== null ? 'the first ' . $limit . ' ' : ''), $this->assetIndexer->getIndexName()]), LogEnvironment::fromMethodName(__METHOD__));
        $this->executeInternalCommand('buildInternal', [ 'limit' => $limit ]);
        // $this->outputLine('<info>+</info> %s', [$output]);
        $this->outputErrorHandling();
    }

    /**
     * @param  bool  $update
     * @return void
     */
    public function createInternalCommand(bool $update = false, string $postfix = null): void
    {
        if ($update === true) {
            $this->logger->warning('!!! Update Mode (Development) active!', LogEnvironment::fromMethodName(__METHOD__));
        } else {
            $this->assetIndexer->setIndexNamePostfix($postfix);
            if ($this->assetIndexer->getIndex()->exists() === true) {
                $this->logger->warning(sprintf('Deleted index with the same postfix (%s)!', $postfix), LogEnvironment::fromMethodName(__METHOD__));
                $this->assetIndexer->getIndex()->delete();
            }
            $this->assetIndexer->getIndex()->create();
            $this->logger->info('Created index ' . $this->assetIndexer->getIndexName(), LogEnvironment::fromMethodName(__METHOD__));
        }

        $this->applyMapping();
        $this->outputErrorHandling();
    }

    /**
     * Internal subcommand to refresh the index
     *
     * @Flow\Internal
     */
    public function refreshInternalCommand(string $postfix): void
    {
        $this->assetIndexer->setIndexNamePostfix($postfix);
        $this->logger->info(vsprintf('Refreshing index %s', [$this->assetIndexer->getIndexName()]), LogEnvironment::fromMethodName(__METHOD__));
        $this->assetIndexer->getIndex()->refresh();

        $this->outputErrorHandling();
    }

    /**
     * @param  string  $postfix
     * @param  bool  $update
     * @throws \Exception
     * @Flow\Internal
     */
    public function aliasInternalCommand(string $postfix, bool $update = false): void
    {
        if ($update === true) {
            return;
        }
        $this->assetIndexer->setIndexNamePostfix($postfix);

        $this->logger->info(vsprintf('Update alias for index %s', [$this->assetIndexer->getIndexName()]), LogEnvironment::fromMethodName(__METHOD__));
        $this->assetIndexer->updateIndexAlias();
        $this->outputErrorHandling();
    }

    /**
     * Clean up old indexes (i.e. all but the current one)
     *
     * @return void
     */
    public function cleanupCommand(): void
    {
        $removed = false;
        try {
            $removedIndices = $this->assetIndexer->removeOldIndices();

            foreach ($removedIndices as $indexToBeRemoved) {
                $removed = true;
                $this->logger->info('Removing old index ' . $indexToBeRemoved, LogEnvironment::fromMethodName(__METHOD__));
            }
        } catch (ApiException $exception) {
            $exception->getResponse()->getBody()->rewind();
            $response = json_decode($exception->getResponse()->getBody()->getContents(), false);
            $message = sprintf('Nothing removed. ElasticSearch responded with status %s', $response->status);

            if (isset($response->error->type)) {
                $this->logger->error(sprintf('%s, saying "%s: %s"', $message, $response->error->type, $response->error->reason), LogEnvironment::fromMethodName(__METHOD__));
            } else {
                $this->logger->error(sprintf('%s, saying "%s"', $message, $response->error), LogEnvironment::fromMethodName(__METHOD__));
            }
        }

        if ($removed === false) {
            $this->logger->info('Nothing to remove.', LogEnvironment::fromMethodName(__METHOD__));
        }
    }

    private function outputErrorHandling(): void
    {
        if ($this->errorHandlingService->hasError() === false) {
            return;
        }

        $this->outputLine();
        $this->outputLine('<error>%s Errors where returned while indexing. Check your logs for more information.</error>', [$this->errorHandlingService->getErrorCount()]);
    }

    /**
     * @param string $command
     * @param array $arguments
     * @return void
     */
    private function executeInternalCommand(string $command, array $arguments): void
    {
        ob_start(null, 1 << 20);

        if ($this->useSubProcesses) {
            $commandIdentifier = 'flowpack.elasticsearch.contentrepositoryadaptor:nodeindex:' . $command;
            $status = Scripts::executeCommand($commandIdentifier, $this->flowSettings, true, array_filter($arguments));

            if ($status !== true) {
                throw new RuntimeException(vsprintf('Command: %s with parameters: %s', [$commandIdentifier, json_encode($arguments)]), 1426767159);
            }
        } else {
            $commandIdentifier = $command . 'Command';
            call_user_func_array([self::class, $commandIdentifier], $arguments);
        }

        ob_get_clean();
    }

    /**
     * Apply the mapping to the current index.
     *
     * @return void
     */
    private function applyMapping(): void
    {
        $nodeTypeMappingCollection = $this->assetMappingBuilder->buildMappingInformation($this->assetIndexer->getIndex());
        foreach ($nodeTypeMappingCollection as $mapping) {
            /** @var Mapping $mapping */
            $mapping->apply();
        }
    }

    private function outputMemoryUsage(): void
    {
        $this->outputLine('! Memory usage %s', [Files::bytesToSizeString(memory_get_usage(true))]);
    }

    /**
     * @param  int|null  $limit
     * @return void
     * @throws \Neos\Flow\Persistence\Exception\InvalidQueryException
     */
    public function buildInternalCommand(int $limit = null): void
    {
        $assetsIterator = $this->assetRepository->findAll();
        foreach ($assetsIterator as $asset) {
            $this->assetIndexer->indexAsset($asset);
        }
        $this->assetIndexer->flush();
    }
}
