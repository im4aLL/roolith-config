<?php
use PHPUnit\Framework\TestCase;
use Roolith\Configuration\Config;

class ConfigMergeSemanticsTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEnvShadowsDefaultPerKeyWithoutDeepMerge()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        Config::setEnv('production');

        // Env value wins when the full path exists in the env file.
        $this->assertEquals('productionDatabase', Config::get('database'));
        $this->assertEquals('c', Config::get('a.b'));

        // Missing in env falls back to default (config.php).
        $this->assertTrue(Config::get('test'));
        $this->assertSame('generalLogPath', Config::get('log.path'));
        $this->assertEquals(['path' => 'generalLogPath'], Config::get('log'));

        // Missing everywhere returns silent null.
        $this->assertNull(Config::get('missing-in-both'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testParentArrayFromEnvIsNotDeepMerged()
    {
        $tmp = sys_get_temp_dir() . '/roolith-config-merge-' . uniqid();
        mkdir($tmp);
        file_put_contents($tmp . '/config.php', "<?php return ['log' => ['path' => 'generalLogPath', 'level' => 'debug']];");
        file_put_contents($tmp . '/production.config.php', "<?php return ['log' => ['path' => 'prodLogPath']];");

        define('ROOLITH_CONFIG_ROOT', $tmp);
        Config::reset();

        try {
            Config::setEnv('production');

            // Leaf present in env wins.
            $this->assertSame('prodLogPath', Config::get('log.path'));
            // Leaf missing in env falls back to default.
            $this->assertSame('debug', Config::get('log.level'));
            // Parent array present in env is returned as-is, not deep-merged.
            $this->assertSame(['path' => 'prodLogPath'], Config::get('log'));
        } finally {
            unlink($tmp . '/config.php');
            unlink($tmp . '/production.config.php');
            rmdir($tmp);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEnvFileWinsOverDefaultEnvNamedKey()
    {
        $tmp = sys_get_temp_dir() . '/roolith-config-collision-' . uniqid();
        mkdir($tmp);
        file_put_contents($tmp . '/config.php', "<?php return ['production' => ['database' => 'shadow'], 'database' => 'generalDatabase'];");
        file_put_contents($tmp . '/production.config.php', "<?php return ['database' => 'productionDatabase'];");

        define('ROOLITH_CONFIG_ROOT', $tmp);
        Config::reset();

        try {
            Config::setEnv('production');
            $this->assertSame('productionDatabase', Config::get('database'));
        } finally {
            unlink($tmp . '/config.php');
            unlink($tmp . '/production.config.php');
            rmdir($tmp);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testStoredNullIsPreserved()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        $this->assertNull(Config::get('nullable'));

        Config::setEnv('production');
        $this->assertNull(Config::get('nullable'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEnvNullShadowsNonNullDefault()
    {
        $tmp = sys_get_temp_dir() . '/roolith-config-null-' . uniqid();
        mkdir($tmp);
        file_put_contents($tmp . '/config.php', "<?php return ['database' => 'generalDatabase'];");
        file_put_contents($tmp . '/production.config.php', "<?php return ['database' => null];");

        define('ROOLITH_CONFIG_ROOT', $tmp);
        Config::reset();

        try {
            Config::setEnv('production');
            $this->assertNull(Config::get('database'));

            Config::setEnv('local');
            $this->assertSame('generalDatabase', Config::get('database'));
        } finally {
            unlink($tmp . '/config.php');
            unlink($tmp . '/production.config.php');
            rmdir($tmp);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPresetInvalidEnvThrows()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();
        putenv(Config::ENV_KEY . '=bad env');

        $this->expectException(\Roolith\Configuration\Exception\InvalidArgumentException::class);
        Config::get('database');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDottedEnvNamePrefersEnvFileOverDefault()
    {
        $tmp = sys_get_temp_dir() . '/roolith-config-dotted-' . uniqid();
        mkdir($tmp);
        file_put_contents($tmp . '/config.php', "<?php return ['staging' => ['database' => 'shadow'], 'database' => 'generalDatabase'];");
        file_put_contents($tmp . '/staging.config.php', "<?php return ['database' => 'stagingDatabase'];");
        file_put_contents($tmp . '/production.config.php', "<?php return ['database' => 'productionDatabase'];");

        define('ROOLITH_CONFIG_ROOT', $tmp);
        Config::reset();

        try {
            $this->assertSame('stagingDatabase', Config::get('staging.database', true));

            Config::setEnv('production');
            $this->assertSame('stagingDatabase', Config::get('staging.database'));
        } finally {
            unlink($tmp . '/config.php');
            unlink($tmp . '/staging.config.php');
            unlink($tmp . '/production.config.php');
            rmdir($tmp);
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBareEnvNameReturnsEnvFileArray()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        $staging = Config::get('staging', true);
        $this->assertSame('stagingDatabase', $staging['database']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLocalConfigFileIsIgnored()
    {
        $tmp = sys_get_temp_dir() . '/roolith-config-local-' . uniqid();
        mkdir($tmp);
        file_put_contents($tmp . '/config.php', "<?php return ['database' => 'generalDatabase'];");
        file_put_contents($tmp . '/local.config.php', "<?php return ['database' => 'localDatabase'];");

        define('ROOLITH_CONFIG_ROOT', $tmp);
        Config::reset();

        try {
            $this->assertSame('generalDatabase', Config::get('database'));
            $this->assertNull(Config::get('local.database', true));
        } finally {
            unlink($tmp . '/config.php');
            unlink($tmp . '/local.config.php');
            rmdir($tmp);
        }
    }
}
