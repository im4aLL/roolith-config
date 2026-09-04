<?php
use PHPUnit\Framework\TestCase;
use Roolith\Configuration\Config;

class ConfigFallbackTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMissingKeyReturnsNullWithoutWarning()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::setEnv('local');

        $warnings = [];
        set_error_handler(function ($no, $str) use (&$warnings) {
            $warnings[] = $str;
            return true;
        }, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE);

        try {
            $this->assertNull(Config::get('missing-key-xyz'));
            $this->assertNull(Config::get('nope.nested.deep'));
            $this->assertNull(Config::get('database.nested.deep'));
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEnvFallsBackToDefaultWhenEnvFileLacksKey()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');

        $warnings = [];
        set_error_handler(function ($no, $str) use (&$warnings) {
            $warnings[] = $str;
            return true;
        }, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE);

        try {
            Config::setEnv('production');
            $this->assertEquals('productionDatabase', Config::get('database'));
            $this->assertTrue(Config::get('test'));
            $this->assertNull(Config::get('missing-in-both'));

            Config::setEnv('development');
            $this->assertEquals('developmentDatabase', Config::get('database'));
            $this->assertTrue(Config::get('test'));
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDottedMissingWithEnvReturnsNullWithoutWarning()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');

        $warnings = [];
        set_error_handler(function ($no, $str) use (&$warnings) {
            $warnings[] = $str;
            return true;
        }, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE);

        try {
            Config::setEnv('production');
            $this->assertEquals('c', Config::get('a.b'));
            $this->assertSame('generalLogPath', Config::get('log.path'));
            $this->assertNull(Config::get('a.missing'));
            $this->assertNull(Config::get('missing.nested'));
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
    }
}
