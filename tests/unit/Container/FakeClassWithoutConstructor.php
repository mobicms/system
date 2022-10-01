<?php

declare(strict_types=1);

namespace MobicmsTest\Container;

class FakeClassWithoutConstructor
{
    public function get(): string
    {
        return 'test';
    }
}
