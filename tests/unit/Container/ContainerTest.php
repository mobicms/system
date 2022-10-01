<?php

declare(strict_types=1);

namespace MobicmsTest\Container;

use ArrayObject;
use Mobicms\Container\Container;
use Mobicms\Container\Exception\AlreadyExistsException;
use Mobicms\Container\Exception\InvalidAliasException;
use Mobicms\Container\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionException;

class ContainerTest extends TestCase
{
    public function testImplementsContainerInterface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, new Container());
    }

    public function testHasMethodAndConfigurationViaConstructor(): void
    {
        $container = new Container(
            [
                'services'    => ['foo' => []],
                'factories'   => ['bar' => fn() => new ArrayObject()],
                'definitions' => ['baz' => ArrayObject::class],
                'aliases'     => ['bat' => 'foo'],
            ]
        );
        $this->assertTrue($container->has('foo'));
        $this->assertTrue($container->has('bar'));
        $this->assertTrue($container->has('baz'));
        $this->assertTrue($container->has('bat'));
    }

    public function testConfigurationThrowExceptionOnFactoryWithDuplicatedKey(): void
    {
        $this->expectException(AlreadyExistsException::class);
        new Container(
            [
                'services'  => ['foo' => []],
                'factories' => ['foo' => fn() => new ArrayObject()],
            ]
        );
    }

    public function testConfigurationThrowExceptionOnDefinitionWithDuplicatedKey(): void
    {
        $this->expectException(AlreadyExistsException::class);
        new Container(
            [
                'services'    => ['foo' => []],
                'definitions' => ['foo' => ArrayObject::class],
            ]
        );
    }

    public function testConfigurationThrowExceptionOnAliasWithDuplicatedKey(): void
    {
        $this->expectException(AlreadyExistsException::class);
        new Container(
            [
                'services'  => ['foo' => []],
                'factories' => ['bar' => fn() => new ArrayObject()],
                'aliases'   => ['foo' => 'bar'],
            ]
        );
    }

    public function testSetServiceMethod(): void
    {
        $container = new Container();
        $this->assertFalse($container->has('foo'));
        $container->setService('foo', []);
        $this->assertTrue($container->has('foo'));
    }

    public function testSetServiceMethodThrowExceptionOnDuplicatedId(): void
    {
        $this->expectException(AlreadyExistsException::class);
        $container = new Container(['services' => ['foo' => []],]);
        $container->setService('foo', []);
    }

    public function testSetFactoryMethod(): void
    {
        $container = new Container();
        $this->assertFalse($container->has('bar'));
        $container->setFactory('bar', fn() => new ArrayObject());
        $this->assertTrue($container->has('bar'));
    }

    public function testSetFactoryMethodThrowExceptionOnDuplicatedId(): void
    {
        $this->expectException(AlreadyExistsException::class);
        $container = new Container(['services' => ['foo' => []],]);
        $container->setFactory('foo', fn() => new ArrayObject());
    }

    public function testSetDefinitionMethod(): void
    {
        $container = new Container();
        $this->assertFalse($container->has('baz'));
        $container->setDefinition('baz', ArrayObject::class);
        $this->assertTrue($container->has('baz'));
    }

    public function testSetDefinitionMethodThrowExceptionOnDuplicatedId(): void
    {
        $this->expectException(AlreadyExistsException::class);
        $container = new Container(['services' => ['foo' => []],]);
        $container->setDefinition('foo', ArrayObject::class);
    }

    public function testSetAliasMethod(): void
    {
        $container = new Container(
            [
                'services'    => ['foo' => []],
                'factories'   => ['bar' => fn() => new ArrayObject()],
                'definitions' => ['baz' => ArrayObject::class],
            ]
        );
        $this->assertFalse($container->has('alias1'));
        $this->assertFalse($container->has('alias2'));
        $this->assertFalse($container->has('alias3'));
        $container->setAlias('alias1', 'foo');
        $container->setAlias('alias2', 'bar');
        $container->setAlias('alias3', 'baz');
        $this->assertTrue($container->has('alias1'));
        $this->assertTrue($container->has('alias2'));
        $this->assertTrue($container->has('alias3'));
    }

    public function testSetAliasThrowExceptionOnUndefinedService(): void
    {
        $this->expectException(InvalidAliasException::class);
        $container = new Container();
        $container->setAlias('alias1', 'foo');
    }

    public function testGetMethodReturnDefinedServices(): void
    {
        $container = $this->getContainer();

        // Get defined service
        $this->assertIsArray($container->get('foo'));

        // Get alias of defined service
        $this->assertIsArray($container->get('bat'));

        // Get factory defined via closure function
        $closureFactory = $container->get('bar');
        $this->assertSame('string', $closureFactory->offsetGet('test'));

        // Get factory defined via factory class
        $closureClass = $container->get('fake_factory');
        $this->assertSame('fakestring', $closureClass->offsetGet('faketest'));

        // Get defined class with dependencies injection
        $definedClassWithDependencies = $container->get('class_with_dependencies');
        $this->assertIsArray($definedClassWithDependencies->get());

        // Get undefined class with dependencies injection
        $undefinedClassWithDependencies = $container->get(FakeClassWithDependencies::class);
        $this->assertIsArray($undefinedClassWithDependencies->get());

        // Get defined class without dependencies
        $definedClassWithoutDependencies = $container->get('class_without_dependencies');
        $this->assertSame('test', $definedClassWithoutDependencies->get());

        // Get undefined class without dependencies
        $undefinedClassWithoutDependencies = $container->get(FakeClassWithoutConstructor::class);
        $this->assertSame('test', $undefinedClassWithoutDependencies->get());
    }

    public function testGetMethodThrowExceptionOnUnknownDefinedClass(): void
    {
        $this->expectException(NotFoundException::class);
        $container = $this->getContainer();
        $container->get('unknown_class');
    }

    public function testGetMethodThrowExceptionOnUnknownUndefinedClass(): void
    {
        $this->expectException(NotFoundException::class);
        $container = $this->getContainer();
        $container->get(Unknown::class);
    }

    public function testGetMethodThrowExceptionOnInvalidFactory(): void
    {
        $this->expectException(NotFoundException::class);
        $container = $this->getContainer();
        $container->get('invalid_factory');
    }

    public function testGetMethodThrowExceptionOnUnknownFactory(): void
    {
        $this->expectException(NotFoundException::class);
        $container = $this->getContainer();
        $container->get('unknown_factory');
    }

    public function testGetMethodThrowExceptionOnInvalidClass(): void
    {
        $this->expectException(ReflectionException::class);
        $container = $this->getContainer();
        $container->get(FakeInvalidClass::class);
    }

    private function getContainer(): Container
    {
        $config = [
            'services'    => ['foo' => [],],
            'aliases'     => ['bat' => 'foo'],
            'factories'   =>
                [
                    'bar'             => fn() => new ArrayObject(['test' => 'string']),
                    'fake_factory'    => FakeFactory::class,
                    'invalid_factory' => FakeClassWithoutConstructor::class,
                    'unknown_factory' => Unknown::class,
                ],
            'definitions' =>
                [
                    'class_with_dependencies'    => FakeClassWithDependencies::class,
                    'class_without_dependencies' => FakeClassWithoutConstructor::class,
                    'unknown_class'              => Unknown::class,
                ],
        ];

        $container = new Container($config);
        $container->setService(ContainerInterface::class, $container);

        return $container;
    }
}
