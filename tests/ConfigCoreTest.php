<?php
use PHPUnit\Framework\TestCase;
use Roolith\Configuration\Config;

class ConfigCoreTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShouldThrowExceptionIfConfigRootNotDefined()
    {
        Config::reset();
        $this->expectException(\Roolith\Configuration\Exception\Exception::class);
        Config::getInstance();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShouldGetEnv()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__. '/config-test');
        Config::reset();
        Config::getInstance();

        $this->assertEquals('local', Config::env());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShouldGetInstance()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__. '/config-test');
        Config::reset();
        $this->assertInstanceOf(Config::class, Config::getInstance());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShouldThrowExceptionForInvalidName()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__. '/config-test');
        Config::reset();
        $this->expectException(\Roolith\Configuration\Exception\InvalidArgumentException::class);
        Config::get('(name');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShouldGetConfigData()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__. '/config-test');
        Config::reset();
        $this->assertEquals('generalDatabase', Config::get('database'));
        $this->assertEquals('developmentDatabase', Config::get('development.database'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShouldGetEnvironmentSpecificConfigData()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__. '/config-test');
        Config::reset();
        Config::setEnv('production');
        $this->assertEquals('productionDatabase', Config::get('database'));
        $this->assertEquals('c', Config::get('a.b'));
    }
}
