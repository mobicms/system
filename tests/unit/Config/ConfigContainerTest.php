<?php

declare(strict_types=1);

namespace MobicmsTest\Config;

use Mobicms\System\Config\ConfigContainer;
use Mobicms\System\Config\Exception\KeyAlreadyExistsException;
use PHPUnit\Framework\TestCase;

class ConfigContainerTest extends TestCase
{
    public function testHasMethod(): void
    {
        $config = new ConfigContainer(['foo' => 'bar']);
        self::assertTrue($config->has('foo'));
        self::assertFalse($config->has('baz'));
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
        self::assertSame(12345, $config->get('int'));
        self::assertSame('teststring', $config->get('string'));
        self::assertIsArray($config->get('array'));

        // Nested data
        self::assertSame('bar', $config->get(['array', 'foo']));
        self::assertSame('bat', $config->get(['array', 'nested', 'baz']));
    }

    public function testGetMethodCanReturnDefaultData(): void
    {
        $config = new ConfigContainer();
        self::assertNull($config->get('foo'));
        self::assertSame('string', $config->get('foo', 'string'));
        self::assertSame(12345, $config->get('foo', 12345));
        self::assertSame('string', $config->get(['foo', 'bar'], 'string'));
        self::assertSame(12345, $config->get(['foo', 'bar'], 12345));
    }

    public function testSetMethod(): void
    {
        $config = new ConfigContainer();
        self::assertFalse($config->has('string'));
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
        self::assertSame('test', $config->get('string'));
        self::assertSame(12345, $config->get('int'));
        self::assertIsArray($config->get('array'));
        self::assertSame('bat', $config->get(['array', 'nested', 'baz']));
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
        self::assertTrue($config->has('foo'));
        $config->unset('foo');
        self::assertFalse($config->has('foo'));
    }
}
