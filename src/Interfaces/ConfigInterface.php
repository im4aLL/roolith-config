<?php
namespace Roolith\Configuration\Interfaces;

use Roolith\Configuration\Exception\Exception;
use Roolith\Configuration\Exception\InvalidArgumentException;

interface ConfigInterface
{
    /**
     * Returns the shared config singleton instance.
     *
     * @return self The shared Config instance.
     * @throws Exception If initialization fails due to missing root or invalid data.
     */
    public static function getInstance(): self;

    /**
     * Retrieves a configuration value by dot-notation key.
     *
     * @param mixed $name Dot-notation config key to look up.
     * @param bool $skipEnvReplacement Whether to skip automatic environment prefixing.
     * @return mixed The configured value, or null when the key is not found.
     * @throws InvalidArgumentException If the key is empty or contains invalid characters.
     */
    public static function get($name, $skipEnvReplacement = false): mixed;

    /**
     * Returns the current environment name.
     *
     * @return string|false The environment name, or false when it is not set.
     */
    public static function env(): string|false;

    /**
     * Sets the current environment name.
     *
     * @param string $name The environment name to activate.
     * @return void
     */
    public static function setEnv($name): void;
}
