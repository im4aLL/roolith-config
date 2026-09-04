<?php
use PHPUnit\Framework\TestCase;
use Roolith\Configuration\Config;

class ConfigPathNormalizationTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testTrailingSlashRootLoadsSameData()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test/');
        Config::reset();

        $this->assertEquals('generalDatabase', Config::get('database'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDotSegmentsRootIsNormalized()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test/../config-test');
        Config::reset();

        $this->assertEquals('generalDatabase', Config::get('database'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFilePathRootThrows()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test/config.php');

        $this->expectException(\Roolith\Configuration\Exception\Exception::class);
        Config::get('database');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMissingDirThrows()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/does-not-exist-xyz');

        $this->expectException(\Roolith\Configuration\Exception\Exception::class);
        Config::get('database');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEmptyStringRootThrows()
    {
        define('ROOLITH_CONFIG_ROOT', '');

        $this->expectException(\Roolith\Configuration\Exception\Exception::class);
        Config::get('database');
    }
}
