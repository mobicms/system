<?php

declare(strict_types=1);

namespace MobicmsTest\System\Config;

use InvalidArgumentException;
use Mobicms\Config\ConfigContainer;
use Mobicms\Config\ConfigInterface;
use PHPUnit\Framework\TestCase;

class ConfigContainerTest extends TestCase
{
    private ConfigContainer $instance;

    public function setUp(): void
    {
        $this->instance = new ConfigContainer(
            [
                'string' => 'foo',
                'int'    => 123,
                'bool'   => true,
                'array'  => [
                    'string' => 'foo',
                    'int'    => 123,
                    'bool'   => true,
                ],
            ]
        );
    }

    public function testClassImplementsInterface(): void
    {
        $this->assertInstanceOf(ConfigInterface::class, $this->instance);
    }

    public function testHasMethod(): void
    {
        $this->assertTrue($this->instance->has('string'));
        $this->assertFalse($this->instance->has('unknown'));
    }

    public function testGetMethod(): void
    {
        // String
        $this->assertSame('foo', $this->instance->get('string'));
        // Integer
        $this->assertSame(123, $this->instance->get('int'));
        // Boolean
        $this->assertSame(true, $this->instance->get('bool'));
        // Default value for non-existent key
        $this->assertSame('bar', $this->instance->get('unknown', 'bar'));
        // Null for non-existent key
        $this->assertNull($this->instance->get('baz'));
    }

    public function testGetMethodWithNestedArray(): void
    {
        // String
        $this->assertSame('foo', $this->instance->get(['array', 'string']));
        // Integer
        $this->assertSame(123, $this->instance->get(['array', 'int']));
        // Boolean
        $this->assertSame(true, $this->instance->get(['array', 'bool']));
        // Default value for non-existent key
        $this->assertSame('bar', $this->instance->get(['unknown'], 'bar'));
        $this->assertSame('bar', $this->instance->get(['array', 'unknown'], 'bar'));
        // Null for non-existent key
        $this->assertNull($this->instance->get(['array', 'unknown']));
    }

    public function testSetMethod(): void
    {
        $this->instance->set('key', 'value');
        $this->assertSame('value', $this->instance->get('key'));
    }

    public function testSetMethodExistingKeyThrowInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->instance->set('string', 'value');
    }

    public function testUnsetMethod(): void
    {
        $this->instance->unset('string');
        $this->assertFalse($this->instance->has('string'));
    }
}
