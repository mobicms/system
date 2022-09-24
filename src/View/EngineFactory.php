<?php

declare(strict_types=1);

namespace Mobicms\View;

use Mobicms\Interface\ConfigInterface;
use Mobicms\Render\Engine;
use Psr\Container\ContainerInterface;

class EngineFactory
{
    public function __invoke(ContainerInterface $container): Engine
    {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        $paths = (array) $config->get(['templates', 'paths'], []);

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
