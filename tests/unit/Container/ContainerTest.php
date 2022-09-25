<?php

declare(strict_types=1);

namespace MobicmsTest\Container;

use Mobicms\Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerTest extends TestCase
{
    public function testImplementsContainerInterface(): void
    {
        $instance = new Container();
        $this->assertInstanceOf(ContainerInterface::class, $instance);
    }

    public function testCanSetDefinitionsViaConstructor(): void
    {
        $definitions = [
            'array'  => ['foo' => 'bar'],
            'string' => 'teststring',
            'int'    => 12345,
        ];

        $container = new Container($definitions);
        $this->assertIsArray($container->get('array'));
        $this->assertIsString($container->get('string'));
        $this->assertIsInt($container->get('int'));
        $this->assertIsInt($container->get('int'));
    }
}
