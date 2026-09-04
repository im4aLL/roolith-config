<?php
use PHPUnit\Framework\TestCase;
use Roolith\Configuration\Config;

class ConfigSkipEnvTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testActiveEnvLookupUsesBareKey()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        Config::setEnv('production');
        $this->assertEquals('productionDatabase', Config::get('database'));

        Config::setEnv('development');
        $this->assertEquals('developmentDatabase', Config::get('database'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSkipEnvReturnsDefaultForBareKey()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        Config::setEnv('production');
        $this->assertEquals('generalDatabase', Config::get('database', true));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExplicitCrossEnvLookupWithSkip()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        Config::setEnv('production');
        $this->assertEquals('stagingDatabase', Config::get('staging.database', true));

        Config::setEnv('development');
        $this->assertEquals('stagingDatabase', Config::get('staging.database', true));
        $this->assertEquals('productionDatabase', Config::get('production.database', true));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSkipEnvIgnoresActiveEnvForNestedKey()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        Config::setEnv('production');
        $this->assertEquals('c', Config::get('a.b'));
        $this->assertNull(Config::get('a.b', true));
    }
}
