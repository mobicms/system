<?php

declare(strict_types=1);

namespace Mobicms\Container;

use Mobicms\Container\Exception\InvalidAliasException;
use Mobicms\Container\Exception\NotFoundException;
use Mobicms\Container\Exception\AlreadyExistsException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;

use function array_key_exists;
use function class_exists;
use function is_callable;
use function is_string;
use function sprintf;

final class Container implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $services = [];

    /** @var array<string, string|callable> */
    private array $factories = [];

    /** @var array<string, string> */
    private array $definitions = [];

    /** @var array<string, string> */
    private array $aliases = [];

    public function __construct(array $config = [])
    {
        $services = (array) ($config['services'] ?? []);
        $factories = (array) ($config['factories'] ?? []);
        $definitions = (array) ($config['definitions'] ?? []);
        $aliases = (array) ($config['aliases'] ?? []);

        /**
         * @var string       $serviceId
         * @var array|object $service
         */
        foreach ($services as $serviceId => $service) {
            $this->setService($serviceId, $service);
        }

        /**
         * @var string          $factoryId
         * @var string|callable $factory
         */
        foreach ($factories as $factoryId => $factory) {
            $this->setFactory($factoryId, $factory);
        }

        /**
         * @var string $definitionId
         * @var string $definition
         */
        foreach ($definitions as $definitionId => $definition) {
            $this->setDefinition($definitionId, $definition);
        }

        /**
         * @var string $aliasId
         * @var string $alias
         */
        foreach ($aliases as $aliasId => $alias) {
            $this->setAlias($aliasId, $alias);
        }
    }

    public function setService(string $id, array|object $service): void
    {
        if ($this->has($id)) {
            throw new AlreadyExistsException(
                sprintf('Service "%s" already exists.', $id)
            );
        }

        $this->services[$id] = $service;
    }

    public function setFactory(string $id, string|callable $factory): void
    {
        if ($this->has($id)) {
            throw new AlreadyExistsException(
                sprintf('Factory "%s" already exists.', $id)
            );
        }

        $this->factories[$id] = $factory;
    }

    public function setDefinition(string $id, string $definition): void
    {
        if ($this->has($id)) {
            throw new AlreadyExistsException(
                sprintf('Definition "%s" already exists.', $id)
            );
        }

        $this->definitions[$id] = $definition;
    }

    public function setAlias(string $id, string $alias): void
    {
        if ($this->has($id)) {
            throw new AlreadyExistsException(
                sprintf('Alias "%s" already exists.', $id)
            );
        }

        if (
            array_key_exists($alias, $this->services)
            || isset($this->factories[$alias])
            || isset($this->definitions[$alias])
            || class_exists($alias)
        ) {
            $this->aliases[$id] = $alias;
        } else {
            throw new InvalidAliasException(
                sprintf(
                    'An alias (ID: %s, ALIAS: %s) can only be assigned ' .
                    'to an already registered service, or to an existing class.',
                    $id,
                    $alias
                )
            );
        }
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services)
            || isset($this->factories[$id])
            || isset($this->definitions[$id])
            || isset($this->aliases[$id]);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }

        if (isset($this->aliases[$id])) {
            return $this->get($this->aliases[$id]);
        }

        return $this->services[$id] = $this->getNew($id);
    }

    public function getNew(string $id): mixed
    {
        return match (true) {
            isset($this->factories[$id])
            => $this->createFromFactory($id),

            ! array_key_exists($id, $this->definitions)
            => (function () use ($id) {
                if (! class_exists($id)) {
                    throw new NotFoundException(sprintf('`%s` is not set in container and is not a class name.', $id));
                }

                return $this->createObject($id);
            })(),

            class_exists($this->definitions[$id])
            => $this->createObject($this->definitions[$id]),

            default
            => throw new NotFoundException(sprintf('`%s` is not a class name.', $id))
        };
    }

    /**
     * @psalm-suppress MixedMethodCall
     */
    private function createFromFactory(string $id): mixed
    {
        $factory = $this->factories[$id];

        if (is_string($factory) && class_exists($factory)) {
            $factory = new $factory();
        }

        if (is_callable($factory)) {
            return $factory($this);
        }

        throw new NotFoundException(sprintf('Unable to resolve service "%s" to a factory.', $id));
    }

    /**
     * @psalm-suppress ArgumentTypeCoercion
     */
    private function createObject(string $className): object
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if (null === $constructor) {
            return $reflection->newInstance();
        }

        return $reflection->newInstanceArgs($this->getArguments($constructor));
    }

    /**
     * @return array<int, mixed>
     * @psalm-suppress MixedAssignment
     */
    private function getArguments(ReflectionMethod $constructor): array
    {
        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            /** @var \ReflectionNamedType|null $type */
            $type = $parameter->getType();

            if (null !== $type) {
                $typeName = $type->getName();

                if (! $type->isBuiltin() && $this->has($typeName)) {
                    $arguments[] = $this->get($typeName);
                }
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            }
        }

        return $arguments;
    }
}
