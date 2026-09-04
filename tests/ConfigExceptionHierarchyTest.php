<?php
use PHPUnit\Framework\TestCase;
use Roolith\Configuration\Config;
use Roolith\Configuration\Exception\Exception;
use Roolith\Configuration\Exception\InvalidArgumentException;

class ConfigExceptionHierarchyTest extends TestCase
{
    public function testInvalidArgumentExceptionExtendsBaseException()
    {
        $this->assertTrue(is_subclass_of(InvalidArgumentException::class, Exception::class));
        $this->assertInstanceOf(Exception::class, new InvalidArgumentException('msg'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBaseExceptionCatchCoversInvalidKey()
    {
        define('ROOLITH_CONFIG_ROOT', __DIR__ . '/config-test');
        Config::reset();

        // Prove the fixture root loads before exercising the invalid-key guard.
        $this->assertEquals('generalDatabase', Config::get('database'));

        $caught = null;

        try {
            Config::get('(invalid');
        } catch (Exception $e) {
            $caught = $e;
        }

        $this->assertInstanceOf(Exception::class, $caught);
        $this->assertInstanceOf(InvalidArgumentException::class, $caught);
    }
}
