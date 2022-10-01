<?php

declare(strict_types=1);

namespace MobicmsTest\Container;

use ArrayObject;
use Psr\Container\ContainerInterface;

class FakeFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new ArrayObject(['faketest' => 'fakestring']);
    }
}
