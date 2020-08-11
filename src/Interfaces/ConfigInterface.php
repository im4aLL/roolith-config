<?php
namespace Roolith\Interfaces;

use Roolith\Exception\Exception;
use Roolith\Exception\InvalidArgumentException;

interface ConfigInterface
{
    /**
     * Get config instance
     *
     * @return $this
     */
    public static function getInstance();

    /**
     * Get config value by name
     *
     * @param $name
     * @param bool $skipEnvReplacement
     * @return mixed
     */
    public static function get($name, $skipEnvReplacement = false);

    /**
     * Get environment name
     *
     * @return string
     */
    public static function env();

    /**
     * Set env
     *
     * @param $name
     * @return $this
     */
    public static function setEnv($name);
}
