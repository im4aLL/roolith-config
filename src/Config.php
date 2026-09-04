<?php
namespace Roolith\Configuration;

use Roolith\Configuration\Exception\Exception;
use Roolith\Configuration\Exception\InvalidArgumentException;
use Roolith\Configuration\Interfaces\ConfigInterface;

class Config implements ConfigInterface
{
    /**
     * @var array
     */
    private static $configArray = [];

    /**
     * @var null
     */
    private static $instance = null;


    /**
     * Config constructor.
     *
     * @throws Exception
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
     * Resolve and validate config root.
     *
     * @return string
     * @throws Exception
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
     * Load default config file
     *
     * @return void
     * @throws Exception
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
     * Load other config files
     *
     * @return void
     * @throws Exception
     */
    protected static function loadOthers(): void
    {
        $fileArray = glob(self::resolvedRoot().'/*.config.php');

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
     * @inheritDoc
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }

        return self::$instance;
    }

    /**
     * @inheritDoc
     */
    public static function get($name, $skipEnvReplacement = false): mixed
    {
        if (!$name || is_null($name) || !is_string($name) || strpbrk($name, '{}()/\@:')) {
            throw new InvalidArgumentException('Invalid key: '.var_export($name, true));
        }

        self::getInstance();

        $actualName = $name;

        if (!$skipEnvReplacement) {
            $environment = self::env();

            if ($environment !== 'local') {
                $actualName = $environment.'.'.$name;
            }
        }

        if (strstr($actualName, '.')) {
            return self::getCustomValue($actualName);
        }

        return isset(self::$configArray['default'][$actualName]) ? self::$configArray['default'][$actualName] : null;
    }

    /**
     * Get dot value from array
     *
     * @param $name
     * @return mixed|null
     */
    protected static function getCustomValue($name): mixed
    {
        $result = null;
        $array = explode('.', $name);

        if (isset(self::$configArray['default'][$array[0]])) {
            $result = self::getValueByArrayPath(self::$configArray['default'], $array);
        }

        if (is_null($result) && isset(self::$configArray[$array[0]])) {
            $result = self::getValueByArrayPath(self::$configArray[$array[0]], array_slice($array, 1));
        }

        return $result;
    }

    /**
     * Find array value from key array
     *
     * @param $config
     * @param $array
     * @return mixed
     */
    protected static function getValueByArrayPath($config, $array): mixed
    {
        $result = $config;

        foreach ($array as $key) {
            $result = &$result[$key];
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public static function env(): string|false
    {
        return getenv('environment');
    }

    /**
     * @inheritDoc
     */
    public static function setEnv($name): void
    {
        putenv('environment='.$name);
    }
}
