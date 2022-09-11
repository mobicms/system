<?php

declare(strict_types=1);

namespace Mobicms\System\Config;

use InvalidArgumentException;

use function array_key_exists;

class ConfigContainer implements ConfigInterface
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function set(string $key, mixed $value): void
    {
        if (array_key_exists($key, $this->data)) {
            throw new InvalidArgumentException('This key "' . $key . '" already exists');
        }

        $this->data[$key] = $value;
    }

    public function unset(string $key): void
    {
        unset($this->data[$key]);
    }
}
