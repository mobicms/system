<?php

declare(strict_types=1);

namespace Mobicms\Session;

interface SessionInterface
{
    public function has(string $name): bool;

    public function get(string $name, mixed $default = null): mixed;

    public function set(string $name, mixed $value): void;

    public function unset(string $name): void;

    public function clear(): void;
}
