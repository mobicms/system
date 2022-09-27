<?php

declare(strict_types=1);

namespace Mobicms\Config;

/**
 * @version 1.0.0
 */
interface ConfigInterface
{
    public function has(string $key): bool;

    public function get(string|array $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function unset(string $key): void;
}
