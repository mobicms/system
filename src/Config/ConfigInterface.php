<?php

declare(strict_types=1);

namespace Mobicms\Config;

/** @psalm-api */
interface ConfigInterface
{
    public function has(string $key): bool;

    /**
     * @param string|array<mixed> $key
     */
    public function get(string|array $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function unset(string $key): void;
}
