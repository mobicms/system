<?php

declare(strict_types=1);

namespace Mobicms\System\View;

use Devanych\Di\FactoryInterface;
use Mobicms\Render\Engine;
use Psr\Container\ContainerInterface;

class EngineFactory implements FactoryInterface
{
    public function create(ContainerInterface $container): Engine
    {
        /** @var array $config */
        $config = $container->get('config');

        /** @var array $paths */
        $paths = $config['templates']['paths'] ?? [];

        $engine = new Engine();

        /**
         * @var string $namespace
         * @var array $pathArray
         */
        foreach ($paths as $namespace => $pathArray) {
            /** @var string $path */
            foreach ($pathArray as $path) {
                $engine->addPath($path, $namespace);
            }
        }

        return $engine;
    }
}
