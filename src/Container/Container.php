<?php

declare(strict_types=1);

namespace Mobicms\Container;

use Mobicms\Container\Exception\NotFoundException;
use Mobicms\Container\Exception\ContainerException;
use Mobicms\Container\Exception\ServiceAlreadyExistsException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function array_key_exists;
use function class_exists;
use function is_callable;
use function is_string;
use function sprintf;

final class Container implements ContainerInterface
{
    private array $services = [];
    private array $factories = [];
    private array $definitions = [];

    public function __construct(array $config = [])
    {
        $services = (array) ($config['services'] ?? []);
        $factories = (array) ($config['factories'] ?? []);
        $definitions = (array) ($config['definitions'] ?? []);

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
    }

    public function setService(string $id, array|object $service): void
    {
        if ($this->has($id)) {
            throw new ServiceAlreadyExistsException($id);
        }

        $this->services[$id] = $service;
    }

    public function setFactory(string $id, string|callable $factory): void
    {
        if ($this->has($id)) {
            throw new ServiceAlreadyExistsException($id);
        }

        $this->factories[$id] = $factory;
    }

    public function setDefinition(string $id, string $definition): void
    {
        if ($this->has($id)) {
            throw new ServiceAlreadyExistsException($id);
        }

        $this->definitions[$id] = $definition;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services)
            || isset($this->factories[$id])
            || isset($this->definitions[$id]);
    }

    public function get($id): mixed
    {
        if (array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }

        return $this->services[$id] = $this->getNew($id);
    }

    /**
     * @psalm-suppress MixedArgument
     */
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
        /** @var string|callable $factory */
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
        try {
            $reflection = new ReflectionClass($className);
            $constructor = $reflection->getConstructor();
        } catch (ReflectionException $e) {
            throw new ContainerException(sprintf('Unable to create object `%s`.', $className), 0, $e);
        }

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
