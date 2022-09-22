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
    private array $definitions = [];
    private array $instances = [];

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

    public function set(string $id, mixed $definition): void
    {
        if (array_key_exists($id, $this->instances)) {
            unset($this->instances[$id]);
        }

        $this->definitions[$id] = $definition;
    }

    public function get($id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        $this->instances[$id] = $this->getNew($id);

        return $this->instances[$id];
    }

    public function getNew(string $id): mixed
    {
        /** @var object $instance */
        $instance = $this->createInstance($id);

        if ($instance instanceof FactoryInterface) {
            return $instance->create($this);
        }

        return $instance;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->definitions);
    }

    private function createInstance(string $id): mixed
    {
        if (! $this->has($id)) {
            if (class_exists($id)) {
                return $this->createObject($id);
            }

            throw new NotFoundException(sprintf('`%s` is not set in container and is not a class name.', $id));
        }

        if (is_string($this->definitions[$id]) && class_exists($this->definitions[$id])) {
            return $this->createObject($this->definitions[$id]);
        }

        if ($this->definitions[$id] instanceof Closure) {
            return $this->definitions[$id]($this);
        }

        return $this->definitions[$id];
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
