<?php

declare(strict_types=1);

namespace Mobicms\Container;

use Psr\Container\ContainerInterface;

interface FactoryInterface
{
    public function create(ContainerInterface $container): object;
}
