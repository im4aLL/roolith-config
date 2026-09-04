<?php
namespace Roolith\Configuration;

use Roolith\Configuration\Exception\Exception;
use Roolith\Configuration\Exception\InvalidArgumentException;
use Roolith\Configuration\Interfaces\ConfigInterface;

class Config implements ConfigInterface
{
    /**
     * @var array<string, mixed>
     */
    private static $configArray = [];

    /**
     * @var self|null
     */
    private static $instance = null;


    /**
     * Initializes the singleton and loads all config files.
     *
     * @return void
     * @throws Exception If ROOLITH_CONFIG_ROOT is undefined or config loading fails.
     */
    private function __construct()
    {
        if (!defined('ROOLITH_CONFIG_ROOT')) {
            throw new Exception('Please define `ROOLITH_CONFIG_ROOT` to your project root');
        }

        if (!self::env()) {
            if (defined('ROOLITH_ENV')) {
                putenv('environment='.ROOLITH_ENV);
            } else {
                putenv('environment=local');
            }
        }

        self::loadDefault();
        self::loadOthers();
    }

    /**
     * Resolves and validates the config root directory.
     *
     * @return string The validated absolute config root path.
     * @throws Exception If the directory cannot be resolved or does not exist.
     */
    private static function resolvedRoot(): string
    {
        $root = ROOLITH_CONFIG_ROOT;
        $realRoot = realpath($root);

        if ($realRoot === false || !is_dir($realRoot)) {
            throw new Exception('Invalid `ROOLITH_CONFIG_ROOT`: directory not found: '.$root);
        }

        return $realRoot;
    }

    /**
     * Loads the default config.php file.
     *
     * @return void
     * @throws Exception If config.php does not return an array.
     */
    protected static function loadDefault(): void
    {
        $defaultConfig = self::resolvedRoot().'/config.php';

        if (file_exists($defaultConfig)) {
            $data = include $defaultConfig;

            if (!is_array($data)) {
                throw new Exception('Invalid config data in `config.php`: expected array, got '.gettype($data));
            }

            self::$configArray['default'] = $data;
        }
    }

    /**
     * Loads all environment-specific *.config.php files.
     *
     * @return void
     * @throws Exception If any environment config file does not return an array.
     */
    protected static function loadOthers(): void
    {
        $fileArray = glob(self::resolvedRoot().'/*.config.php') ?: [];

        if (count($fileArray) === 0) {
            return;
        }

        foreach ($fileArray as $file) {
            $key = str_replace('.config.php', '', basename($file));

            $data = include $file;

            if (!is_array($data)) {
                throw new Exception('Invalid config data in `'.basename($file).'`: expected array, got '.gettype($data));
            }

            self::$configArray[$key] = $data;
        }
    }


    /**
     * Returns the shared config singleton instance.
     *
     * @return self The shared Config instance.
     * @throws Exception If initialization fails due to missing root or invalid data.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }

        return self::$instance;
    }

    /**
     * Retrieves a configuration value by dot-notation key.
     *
     * @param mixed $name Dot-notation config key to look up.
     * @param bool $skipEnvReplacement Whether to skip automatic environment prefixing.
     * @return mixed The configured value, or null when the key is not found.
     * @throws InvalidArgumentException If the key is empty or contains invalid characters.
     */
    public static function get($name, $skipEnvReplacement = false): mixed
    {
        if (!$name || is_null($name) || !is_string($name) || strpbrk($name, '{}()/\@:')) {
            throw new InvalidArgumentException('Invalid key: '.var_export($name, true));
        }

        self::getInstance();

        $actualName = $name;
        $environment = null;

        if (!$skipEnvReplacement) {
            $env = self::env();

            if ($env !== false && $env !== '' && $env !== 'local') {
                $environment = $env;
                $actualName = $environment.'.'.$name;
            }
        }

        if ($environment !== null) {
            if (self::hasCustomValue($actualName)) {
                return self::getCustomValue($actualName);
            }

            if (strstr($name, '.')) {
                return self::getCustomValue($name);
            }

            $default = self::$configArray['default'] ?? [];

            return array_key_exists($name, $default) ? $default[$name] : null;
        }

        if (strstr($actualName, '.')) {
            return self::getCustomValue($actualName);
        }

        $default = self::$configArray['default'] ?? [];

        return array_key_exists($actualName, $default) ? $default[$actualName] : null;
    }

    /**
     * Resolves a dot-notation key against loaded config data.
     *
     * @param string $name Dot-notation key to resolve.
     * @return mixed|null The matched value, or null when not found.
     */
    protected static function getCustomValue($name): mixed
    {
        $array = explode('.', $name);
        $default = self::$configArray['default'] ?? [];

        if (self::hasValueByArrayPath($default, $array)) {
            return self::getValueByArrayPath($default, $array);
        }

        if (array_key_exists($array[0], self::$configArray)) {
            $rest = array_slice($array, 1);

            if (self::hasValueByArrayPath(self::$configArray[$array[0]], $rest)) {
                return self::getValueByArrayPath(self::$configArray[$array[0]], $rest);
            }
        }

        return null;
    }

    /**
     * Checks whether a dot-notation key exists in loaded config data.
     *
     * @param string $name Dot-notation key to check.
     * @return bool True when the key path exists, false otherwise.
     */
    private static function hasCustomValue($name): bool
    {
        $array = explode('.', $name);
        $default = self::$configArray['default'] ?? [];

        if (self::hasValueByArrayPath($default, $array)) {
            return true;
        }

        if (array_key_exists($array[0], self::$configArray)) {
            return self::hasValueByArrayPath(self::$configArray[$array[0]], array_slice($array, 1));
        }

        return false;
    }

    /**
     * Checks whether a nested array path exists.
     *
     * @param array<string, mixed> $config The config array to search.
     * @param array<int, string> $array Ordered list of key segments.
     * @return bool True when the full path exists, false otherwise.
     */
    private static function hasValueByArrayPath($config, $array): bool
    {
        $result = $config;

        foreach ($array as $key) {
            if (!is_array($result) || !array_key_exists($key, $result)) {
                return false;
            }

            $result = $result[$key];
        }

        return true;
    }

    /**
     * Retrieves a nested value by key path segments.
     *
     * @param array<string, mixed> $config The config array to search.
     * @param array<int, string> $array Ordered list of key segments.
     * @return mixed The nested value, or null when the path does not exist.
     */
    protected static function getValueByArrayPath($config, $array): mixed
    {
        $result = $config;

        foreach ($array as $key) {
            if (!is_array($result) || !array_key_exists($key, $result)) {
                return null;
            }

            $result = $result[$key];
        }

        return $result;
    }

    /**
     * Returns the current environment name.
     *
     * @return string|false The environment name, or false when it is not set.
     */
    public static function env(): string|false
    {
        return getenv('environment');
    }

    /**
     * Sets the current environment name.
     *
     * @param string $name The environment name to activate.
     * @return void
     */
    public static function setEnv($name): void
    {
        putenv('environment='.$name);
    }
}
