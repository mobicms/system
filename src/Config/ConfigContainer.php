<?php

declare(strict_types=1);

namespace Mobicms\Config;

use Mobicms\Config\Exception\KeyAlreadyExistsException;

use function array_key_exists;
use function is_array;

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

    public function get(string|array $key, mixed $default = null): mixed
    {
        if (is_array($key)) {
            $value = $this->data;

            /** @var string $nested */
            foreach ($key as $nested) {
                if (! is_array($value) || ! array_key_exists($nested, $value)) {
                    return $default;
                }

                /** @var array|mixed $value */
                $value = $value[$nested];
            }

            return $value;
        }

        return array_key_exists($key, $this->data)
            ? $this->data[$key]
            : $default;
    }

    public function set(string $key, mixed $value): void
    {
        if (array_key_exists($key, $this->data)) {
            throw new KeyAlreadyExistsException($key);
        }

        $this->data[$key] = $value;
    }

    public function unset(string $key): void
    {
        unset($this->data[$key]);
    }
}
