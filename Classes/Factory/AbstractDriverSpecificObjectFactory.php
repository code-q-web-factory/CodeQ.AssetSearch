<?php

namespace CodeQ\AssetSearch\Factory;

use CodeQ\AssetSearch\Exception\ConfigurationException;
use Neos\Flow\Annotations as Flow;
use Psr\Log\LoggerInterface;

/**
 * Builds objects which are specific to an elastic search version
 *
 * @Flow\Scope("singleton")
 */
class AbstractDriverSpecificObjectFactory
{
    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="driver.mapping")
     */
    protected $mapping;

    /**
     * @var int
     * @Flow\InjectConfiguration(path="driver.version")
     */
    protected $driverVersion;

    /**
     * @param  string  $type
     * @return mixed
     * @throws ConfigurationException
     */
    protected function resolve(string $type)
    {
        $version = trim($this->driverVersion);
        if (!isset($this->mapping[$version][$type]['className']) || trim($this->driverVersion) === '') {
            throw new ConfigurationException(sprintf('Missing or wrongly configured driver type "%s" with the given version: %s', $type, $version ?: '[missing]'), 1485933538);
        }

        $className = trim($this->mapping[$version][$type]['className']);

        if (!isset($this->mapping[$version][$type]['arguments'])) {
            return new $className();
        }

        return new $className(...array_values($this->mapping[$version][$type]['arguments']));
    }
}
