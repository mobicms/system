<?php

declare(strict_types=1);

namespace Mobicms\System\View;

use Mobicms\Contract\ConfigInterface;
use Mobicms\Render\Engine;
use Psr\Container\ContainerInterface;

/**
 * @psalm-api
 */
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
         * @var array<string> $pathArray
         */
        foreach ($paths as $namespace => $pathArray) {
            foreach ($pathArray as $path) {
                $engine->addPath($path, $namespace);
            }
        }

        return $engine;
    }
}
