<?php

declare(strict_types=1);

namespace Mobicms\Container;

use Closure;
use Mobicms\Container\Exception\NotFoundException;
use Mobicms\Container\Exception\ContainerException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function array_key_exists;
use function class_exists;
use function is_string;
use function sprintf;

final class Container implements ContainerInterface
{
    private array $factories = [];
    private array $definitions = [];
    private array $services = [];

    public function __construct(array $definitions = [])
    {
        /**
         * @var string $id
         * @var mixed  $definition
         */
        foreach ($definitions as $id => $definition) {
            $this->set($id, $definition);
        }
    }

    public function setFactory(string $id, string|callable $factory): void
    {
        //TODO: добавить проверку на уже имеющийся сервис
        $this->factories[$id] = $factory;
    }

    public function set(string $id, mixed $definition): void
    {
        //TODO: добавить проверку на уже имеющийся сервис
        if (array_key_exists($id, $this->services)) {
            unset($this->services[$id]);//TODO: разобраться
        }

        $this->definitions[$id] = $definition;
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

            $this->definitions[$id] instanceof Closure
            => $this->definitions[$id]($this),

            is_string($this->definitions[$id]) && class_exists($this->definitions[$id])
            => $this->createObject($this->definitions[$id]),

            default
            => $this->definitions[$id]
        };
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->factories) || array_key_exists($id, $this->definitions);
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
        } catch (ReflectionException $e) {
            throw new ContainerException(sprintf('Unable to create object `%s`.', $className), 0, $e);
        }

        if (($constructor = $reflection->getConstructor()) === null) {
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

                if (! $type->isBuiltin() && ($this->has($typeName) || class_exists($typeName))) {
                    $arguments[] = $this->get($typeName);
                    continue;
                }

                if ($type->isBuiltin() && $typeName === 'array' && ! $parameter->isDefaultValueAvailable()) {
                    $arguments[] = [];
                    continue;
                }
            }

            if ($parameter->isDefaultValueAvailable()) {
                try {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                } catch (ReflectionException $e) {
                    throw new ContainerException(
                        sprintf(
                            'Unable to create object `%s`. Unable to get default value of constructor parameter: `%s`.',
                            $constructor->getName(),
                            $parameter->getName()
                        )
                    );
                }
            }

            throw new ContainerException(
                sprintf(
                    'Unable to create object. Unable to process a constructor parameter: `%s`.',
                    $parameter->getName()
                )
            );
        }

        return $arguments;
    }
}
