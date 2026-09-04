<?php
namespace Roolith\Configuration;

use Roolith\Configuration\Exception\Exception;
use Roolith\Configuration\Exception\InvalidArgumentException;
use Roolith\Configuration\Interfaces\ConfigInterface;

/**
 * Static file-based config loader.
 *
 * Environment precedence, highest first:
 *
 * 1. Explicit `Config::setEnv()` value (process env `ROOLITH_ENVIRONMENT`).
 * 2. `ROOLITH_ENV` constant, read once on first init when no process env is set.
 * 3. `local` default.
 *
 * Note: the legacy generic `environment` process env key is NOT read. It was
 * removed as a breaking change (see README upgrade note) because the generic
 * name collides with OS-level variables and silently overrode `ROOLITH_ENV`.
 *
 * `setEnv()` takes effect immediately for later `get()` calls because all
 * env files are preloaded at init; no reload is needed for env switches.
 * `ROOLITH_CONFIG_ROOT` and `ROOLITH_ENV` are read-once PHP constants.
 * Changing root within the same process requires `Config::reset(false)`
 * followed by a fresh `getInstance()`/`get()` to reload while preserving env,
 * or `Config::reset()` to also clear env state (e.g. for tests).
 */
class Config implements ConfigInterface
{
    /**
     * Process environment key holding the active environment name.
     *
     * Namespaced to avoid colliding with a generic OS `environment` variable.
     */
    public const ENV_KEY = 'ROOLITH_ENVIRONMENT';

    /**
     * Fallback environment when none is configured.
     */
    public const DEFAULT_ENV = 'local';

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
     * Seeds process env `ROOLITH_ENVIRONMENT` from `ROOLITH_ENV` or `local`
     * only when no explicit process env value exists yet. See class docblock
     * for full precedence.
     *
     * @return void
     * @throws Exception If ROOLITH_CONFIG_ROOT is undefined or config loading fails.
     */
    private function __construct()
    {
        if (!defined('ROOLITH_CONFIG_ROOT')) {
            throw new Exception('Please define `ROOLITH_CONFIG_ROOT` to your project root');
        }

        // Fail fast on missing or unreadable root before seeding env state.
        self::resolvedRoot();

        $current = getenv(self::ENV_KEY);

        if ($current === false || $current === '') {
            if (defined('ROOLITH_ENV') && ROOLITH_ENV !== '' && ROOLITH_ENV !== false) {
                self::assertValidEnv(ROOLITH_ENV);
                putenv(self::ENV_KEY.'='.ROOLITH_ENV);
            } else {
                putenv(self::ENV_KEY.'='.self::DEFAULT_ENV);
            }
        }

        self::$configArray = [];
        self::loadDefault();
        self::loadOthers();
    }

    /**
     * Resolves and validates the config root directory.
     *
     * Normalizes trailing separators and resolves symlinks, `.`, and `..`
     * via `realpath`. A missing `config.php` remains optional; only the
     * directory itself must exist and be readable.
     *
     * @return string The validated absolute config root path.
     * @throws Exception If the root is not a non-empty string, or the directory cannot be resolved or read.
     */
    private static function resolvedRoot(): string
    {
        $root = ROOLITH_CONFIG_ROOT;

        if (!is_string($root) || trim($root) === '') {
            throw new Exception('Invalid `ROOLITH_CONFIG_ROOT`: expected non-empty string, got '.gettype($root));
        }

        $normalized = rtrim($root, "/\\");

        if ($normalized === '') {
            throw new Exception('Invalid `ROOLITH_CONFIG_ROOT`: directory not found: '.$root);
        }

        $realRoot = realpath($normalized);

        if ($realRoot === false || !is_dir($realRoot)) {
            throw new Exception('Invalid `ROOLITH_CONFIG_ROOT`: directory not found: '.$root);
        }

        if (!is_readable($realRoot)) {
            throw new Exception('Invalid `ROOLITH_CONFIG_ROOT`: directory not readable: '.$root);
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

            // `default.config.php` would collide with the reserved `default`
            // key holding `config.php`. Skip it to preserve loadDefault data.
            if ($key === 'default') {
                continue;
            }

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
     * Dotted keys with an active env first try `env.key`, then fall back to
     * a literal lookup of the full dotted path. Pass `$skipEnvReplacement`
     * as true to skip the env-prefixed attempt and resolve literally.
     *
     * @param mixed $name Dot-notation config key to look up.
     * @param bool $skipEnvReplacement Whether to skip automatic environment prefixing.
     * @return mixed The configured value, or null when the key is not found.
     * @throws InvalidArgumentException If the key is empty or contains invalid characters.
     */
    public static function get($name, $skipEnvReplacement = false): mixed
    {
        self::assertValidKey($name);

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
     * Reads the namespaced process env key `ROOLITH_ENVIRONMENT`.
     *
     * @return string|false The environment name, or false when it is not set.
     */
    public static function env(): string|false
    {
        $namespaced = getenv(self::ENV_KEY);

        if ($namespaced !== false && $namespaced !== '') {
            return $namespaced;
        }

        return false;
    }

    /**
     * Sets the current environment name.
     *
     * Writes the namespaced process env key `ROOLITH_ENVIRONMENT` and takes
     * effect immediately for later `get()` calls; no reload is needed because
     * all env files are preloaded. This overrides the `ROOLITH_ENV` constant.
     *
     * @param string $name The environment name to activate.
     * @return void
     * @throws InvalidArgumentException If the env name is empty or contains invalid characters.
     */
    public static function setEnv($name): void
    {
        self::assertValidEnv($name);

        putenv(self::ENV_KEY.'='.$name);
    }

    /**
     * Validates a dot-notation config key.
     *
     * Allows `A-Za-z0-9_.-` with no empty segments, so `"0"` and
     * `"missing-key-xyz"` are valid while `""`, `".."`, `"a b"`,
     * `".a"`, `"a."`, and `"a..b"` are rejected.
     *
     * @param mixed $name Key to validate.
     * @return void
     * @throws InvalidArgumentException If the key is invalid.
     */
    private static function assertValidKey($name): void
    {
        if (!is_string($name) || $name === '' || !preg_match('/\A[A-Za-z0-9_.-]+\z/', $name)) {
            throw new InvalidArgumentException('Invalid key: '.var_export($name, true));
        }

        foreach (explode('.', $name) as $segment) {
            if ($segment === '') {
                throw new InvalidArgumentException('Invalid key: '.var_export($name, true));
            }
        }
    }

    /**
     * Validates an environment name.
     *
     * Env names must be non-empty strings without dots, so they stay
     * unambiguous as prefixes in dot-notation lookups.
     *
     * @param mixed $name Env name to validate.
     * @return void
     * @throws InvalidArgumentException If the env name is invalid.
     */
    private static function assertValidEnv($name): void
    {
        if (!is_string($name) || $name === '' || !preg_match('/\A[A-Za-z0-9_-]+\z/', $name)) {
            throw new InvalidArgumentException('Invalid env: '.var_export($name, true));
        }
    }

    /**
     * Resets singleton state for tests and root changes.
     *
     * Clears the shared instance and loaded config data. By default also
     * clears the namespaced env state so the next `getInstance()` or `get()`
     * call re-seeds env per documented precedence and reloads from the
     * current `ROOLITH_CONFIG_ROOT`.
     *
     * Pass `$clearEnv = false` to reload from a new `ROOLITH_CONFIG_ROOT`
     * while preserving the current explicit env.
     *
     * @param bool $clearEnv Whether to clear env state as well.
     * @return void
     */
    public static function reset(bool $clearEnv = true): void
    {
        self::$instance = null;
        self::$configArray = [];

        if ($clearEnv) {
            putenv(self::ENV_KEY);
        }
    }
}
