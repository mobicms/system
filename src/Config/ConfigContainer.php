<?php

declare(strict_types=1);

namespace Mobicms\System\Config;

use Mobicms\Contract\ConfigInterface;
use Mobicms\System\Config\Exception\KeyAlreadyExistsException;

use function array_key_exists;
use function is_array;

/** @psalm-api */
class ConfigContainer implements ConfigInterface
{
    /**
     * @var array<array-key, mixed>
     */
    private array $data;

    /**
     * @param array<array-key, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    #[\Override]
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @param string|array<mixed> $key
     */
    #[\Override]
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

    #[\Override]
    public function set(string $key, mixed $value): void
    {
        if (array_key_exists($key, $this->data)) {
            throw new KeyAlreadyExistsException($key);
        }

        $this->data[$key] = $value;
    }

    #[\Override]
    public function unset(string $key): void
    {
        unset($this->data[$key]);
    }
}
