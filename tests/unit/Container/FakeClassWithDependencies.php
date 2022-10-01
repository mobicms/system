<?php

declare(strict_types=1);

namespace MobicmsTest\Container;

use Psr\Container\ContainerInterface;

class FakeClassWithDependencies
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container, string $defaultValue = 'test')
    {
        $this->container = $container;
    }

    public function get(): mixed
    {
        return $this->container->get('foo');
    }
}
