<?php
use PHPUnit\Framework\TestCase;
use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\InvalidArgumentException;

class ConfigInputValidationTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetZeroKeyIsValidAndReturnsNullWhenMissing()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        $this->assertNull(Config::get('0'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetInvalidKeysThrow()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();
        $this->assertEquals('generalDatabase', Config::get('database'));

        foreach (['', '..', 'a b', '.a', 'a.', 'a..b', '(invalid', 'a/b', 'a:b', 'a@b', "foo\n", "foo\r\n"] as $key) {
            try {
                Config::get($key);
                $this->fail('Expected InvalidArgumentException for key: ' . var_export($key, true));
            } catch (InvalidArgumentException $e) {
                $this->assertInstanceOf(InvalidArgumentException::class, $e);
            }
        }

        foreach ([null, 0, 123, [], ['a']] as $key) {
            try {
                Config::get($key);
                $this->fail('Expected InvalidArgumentException for key: ' . var_export($key, true));
            } catch (InvalidArgumentException $e) {
                $this->assertInstanceOf(InvalidArgumentException::class, $e);
            }
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetEnvInvalidThrows()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        foreach (['', 'a b', 'a.b', 'a/b', 'a:b', "prod\n", 'a=b'] as $env) {
            try {
                Config::setEnv($env);
                $this->fail('Expected InvalidArgumentException for env: ' . var_export($env, true));
            } catch (InvalidArgumentException $e) {
                $this->assertInstanceOf(InvalidArgumentException::class, $e);
            }
        }

        foreach ([null, 123, [], ['production']] as $env) {
            try {
                Config::setEnv($env);
                $this->fail('Expected InvalidArgumentException for env: ' . var_export($env, true));
            } catch (InvalidArgumentException $e) {
                $this->assertInstanceOf(InvalidArgumentException::class, $e);
            }
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetEnvZeroAndHyphenAreValid()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        Config::setEnv('0');
        $this->assertEquals('0', Config::env());

        Config::setEnv('staging-1');
        $this->assertEquals('staging-1', Config::env());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testResetFalsePreservesEnvAndReloads()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        Config::setEnv('production');
        $this->assertEquals('productionDatabase', Config::get('database'));

        // Same-process root constants cannot be redefined, so a true new-root
        // reload needs a fresh process. Here we verify reset(false) preserves
        // env while forcing a reload from the current root.
        Config::reset(false);

        $this->assertEquals('production', Config::env());
        $this->assertEquals('productionDatabase', Config::get('database'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMalformedRoolithEnvConstantThrows()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        define('ROOLITH_ENV', 'bad env');
        Config::reset();

        $this->expectException(InvalidArgumentException::class);
        Config::get('database');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDefaultConfigFileDoesNotOverwriteReservedKey()
    {
        $tmp = sys_get_temp_dir() . '/roolith-config-reserved-' . uniqid();
        mkdir($tmp);
        file_put_contents($tmp . '/config.php', "<?php return ['database' => 'generalDatabase'];");
        file_put_contents($tmp . '/default.config.php', "<?php return ['database' => 'shouldNotWin'];");

        define('ROOLITH_CONFIG_ROOT', $tmp);
        Config::reset();

        try {
            $this->assertEquals('generalDatabase', Config::get('database'));
        } finally {
            unlink($tmp . '/config.php');
            unlink($tmp . '/default.config.php');
            rmdir($tmp);
        }
    }
}
