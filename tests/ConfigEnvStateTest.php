<?php
use PHPUnit\Framework\TestCase;
use Roolith\Configuration\Config;

class ConfigEnvStateTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetEnvTakesEffectImmediatelyWithoutReload()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        Config::setEnv('production');
        $this->assertEquals('productionDatabase', Config::get('database'));

        Config::setEnv('development');
        $this->assertEquals('developmentDatabase', Config::get('database'));

        Config::setEnv('local');
        $this->assertEquals('generalDatabase', Config::get('database'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUsesNamespacedEnvKey()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        Config::setEnv('production');

        $this->assertEquals('production', Config::env());
        $this->assertEquals('production', getenv(Config::ENV_KEY));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testResetClearsState()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');

        Config::setEnv('production');
        $this->assertEquals('productionDatabase', Config::get('database'));

        Config::reset();

        $this->assertFalse(Config::env());
        $this->assertFalse(getenv(Config::ENV_KEY));

        $reflection = new ReflectionClass(Config::class);
        $instanceProp = $reflection->getProperty('instance');
        $instanceProp->setAccessible(true);
        $this->assertNull($instanceProp->getValue());

        $configProp = $reflection->getProperty('configArray');
        $configProp->setAccessible(true);
        $this->assertSame([], $configProp->getValue());

        $this->assertEquals('generalDatabase', Config::get('database'));
        $this->assertEquals('local', Config::env());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRoolithEnvConstantSeedsEnvOnFreshInit()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        define('ROOLITH_ENV', 'production');
        Config::reset();

        $this->assertEquals('productionDatabase', Config::get('database'));
        $this->assertEquals('production', Config::env());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetEnvOverridesConstant()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        define('ROOLITH_ENV', 'production');
        Config::reset();

        $this->assertEquals('productionDatabase', Config::get('database'));
        $this->assertEquals('production', Config::env());

        Config::setEnv('development');
        $this->assertEquals('development', Config::env());
        $this->assertEquals('developmentDatabase', Config::get('database'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPresetNamespacedEnvBeatsConstant()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        define('ROOLITH_ENV', 'production');
        Config::reset();
        putenv(Config::ENV_KEY . '=development');

        $this->assertEquals('developmentDatabase', Config::get('database'));
        $this->assertEquals('development', Config::env());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEmptyStringEnvSeedsLocal()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();
        putenv(Config::ENV_KEY . '=');

        $this->assertEquals('generalDatabase', Config::get('database'));
        $this->assertEquals('local', Config::env());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testResetPreservesEnvWhenClearEnvFalse()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        Config::setEnv('production');
        $this->assertEquals('productionDatabase', Config::get('database'));

        Config::reset(false);

        $this->assertEquals('production', Config::env());
        $this->assertEquals('productionDatabase', Config::get('database'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testResetClearsNamespacedKey()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();
        putenv(Config::ENV_KEY . '=production');

        Config::reset();

        $this->assertFalse(getenv(Config::ENV_KEY));
    }
}
