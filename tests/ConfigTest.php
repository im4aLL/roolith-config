<?php
use PHPUnit\Framework\TestCase;
use Roolith\Configuration\Config;

class ConfigTest extends TestCase
{
    public function testShouldThrowExceptionIfConfigRootNotDefined()
    {
        $this->expectException(\Roolith\Configuration\Exception\Exception::class);
        Config::getInstance();
    }

    public function testShouldGetEnv()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__. '/config-test');
        Config::getInstance();

        $this->assertEquals('local', Config::env());
    }

    public function testShouldGetInstance()
    {
        $this->assertInstanceOf(Config::class, Config::getInstance());
    }

    public function testShouldThrowExceptionForInvalidName()
    {
        $this->expectException(\Roolith\Configuration\Exception\InvalidArgumentException::class);
        Config::get('(name');
    }

    public function testShouldGetConfigData()
    {
        $this->assertEquals('generalDatabase', Config::get('database'));
        $this->assertEquals('developmentDatabase', Config::get('development.database'));
    }

    public function testShouldGetEnvironmentSpecificConfigData()
    {
        Config::setEnv('production');
        $this->assertEquals('productionDatabase', Config::get('database'));
        $this->assertEquals('c', Config::get('a.b'));
    }
}
