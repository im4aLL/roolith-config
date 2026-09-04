<?php
use PHPUnit\Framework\TestCase;
use Roolith\Configuration\Config;

class ConfigHardeningTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShouldThrowOnNonArrayDefault()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-bad-nonarray');

        $this->expectException(\Roolith\Configuration\Exception\Exception::class);
        Config::get('database');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShouldThrowOnNonArrayEnvFile()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-bad-env');

        $this->expectException(\Roolith\Configuration\Exception\Exception::class);
        Config::get('database');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShouldThrowOnMissingDir()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/does-not-exist-xyz');

        $this->expectException(\Roolith\Configuration\Exception\Exception::class);
        Config::get('database');
    }
}
