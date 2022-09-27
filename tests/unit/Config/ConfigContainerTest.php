<?php

declare(strict_types=1);

namespace MobicmsTest\Config;

use Mobicms\Config\ConfigContainer;
use Mobicms\Config\Exception\KeyAlreadyExistsException;
use Mobicms\Config\ConfigInterface;
use PHPUnit\Framework\TestCase;

class ConfigContainerTest extends TestCase
{
    public function testImplementsConfigInterface(): void
    {
        $this->assertInstanceOf(ConfigInterface::class, new ConfigContainer());
    }

    public function testHasMethod(): void
    {
        $config = new ConfigContainer(['foo' => 'bar']);
        $this->assertTrue($config->has('foo'));
        $this->assertFalse($config->has('baz'));
    }

    public function testGetMethod(): void
    {
        $data = [
            'int'    => 12345,
            'string' => 'teststring',
            'array'  => [
                'foo'    => 'bar',
                'nested' => [
                    'baz' => 'bat',
                ],
            ],
        ];
        $config = new ConfigContainer($data);
        $this->assertSame(12345, $config->get('int'));
        $this->assertSame('teststring', $config->get('string'));
        $this->assertIsArray($config->get('array'));

        // Nested data
        $this->assertSame('bar', $config->get(['array', 'foo']));
        $this->assertSame('bat', $config->get(['array', 'nested', 'baz']));
    }

    public function testGetMethodCanReturnDefaultData(): void
    {
        $config = new ConfigContainer();
        $this->assertNull($config->get('foo'));
        $this->assertSame('string', $config->get('foo', 'string'));
        $this->assertSame(12345, $config->get('foo', 12345));
        $this->assertSame('string', $config->get(['foo', 'bar'], 'string'));
        $this->assertSame(12345, $config->get(['foo', 'bar'], 12345));
    }

    public function testSetMethod(): void
    {
        $config = new ConfigContainer();
        $this->assertFalse($config->has('string'));
        $config->set('string', 'test');
        $config->set('int', 12345);
        $config->set(
            'array',
            [
                'foo'    => 'bar',
                'nested' => [
                    'baz' => 'bat',
                ],
            ]
        );
        $this->assertSame('test', $config->get('string'));
        $this->assertSame(12345, $config->get('int'));
        $this->assertIsArray($config->get('array'));
        $this->assertSame('bat', $config->get(['array', 'nested', 'baz']));
    }

    public function testSetMethodThrowExceptionOnExistingKey(): void
    {
        $config = new ConfigContainer(['foo' => 'bar']);
        $this->expectException(KeyAlreadyExistsException::class);
        $config->set('foo', 'somedata');
    }

    public function testUnsetMethod(): void
    {
        $config = new ConfigContainer(['foo' => 'bar']);
        $this->assertTrue($config->has('foo'));
        $config->unset('foo');
        $this->assertFalse($config->has('foo'));
    }
}
