<?php

namespace CodeQ\AssetSearch\ErrorHandling;

use Neos\Flow\Annotations as Flow;
use Psr\Log\LoggerInterface;

/**
 * Error Handling Service
 *
 * @Flow\Scope("singleton")
 */
class ErrorHandlingService
{
    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var int
     */
    protected $errorCount = 0;

    /**
     * @param string $message
     * @param $context
     */
    public function log(string $message, $context): void
    {
        $this->errorCount++;
        $this->logger->error($message, $context);
    }

    /**
     * @return int
     */
    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->errorCount > 0;
    }
}
