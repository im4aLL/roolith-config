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
     * Load default config file
     */
    protected static function loadDefault()
    {
        $defaultConfig = ROOLITH_CONFIG_ROOT.'/config.php';

        if (file_exists($defaultConfig)) {
            self::$configArray['default'] = include $defaultConfig;
        }
    }

    /**
     * Load other config files
     *
     * @return bool
     */
    protected static function loadOthers()
    {
        $fileArray = glob(ROOLITH_CONFIG_ROOT.'/*.config.php');

        if (count($fileArray) === 0) {
            return false;
        }

        foreach ($fileArray as $file) {
            $key = str_replace('.config.php', '', basename($file));

            self::$configArray[$key] = include $file;
        }
    }


    /**
     * @inheritDoc
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }

        return self::$instance;
    }

    /**
     * @inheritDoc
     */
    public static function get($name, $skipEnvReplacement = false)
    {
        if (!$name || is_null($name) || !is_string($name) || strpbrk($name, '{}()/\@:')) {
            throw new InvalidArgumentException('Invalid key: '.var_export($name, true));
            return false;
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
    protected static function getCustomValue($name)
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
    protected static function getValueByArrayPath($config, $array)
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
    public static function env()
    {
        return getenv('environment');
    }

    /**
     * @inheritDoc
     */
    public static function setEnv($name)
    {
        putenv('environment='.$name);
    }
}
