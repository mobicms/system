<?php

declare(strict_types=1);

namespace Mobicms\System\View;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mobicms\Render\Engine;
use Psr\Container\ContainerInterface;

class EngineFactory
{
    public function __invoke(ContainerInterface $container): Engine
    {
        $engine = new Engine();

        $this->registerFunctions($container, $engine);
        $this->addTemplatePaths($container, $engine);

        return $engine;
    }

    private function registerFunctions(ContainerInterface $container, Engine $engine): void
    {
        /** @var UrlHelper $urlHelper */
        $urlHelper = $container->get(UrlHelper::class);
        $engine->registerFunction('url', $urlHelper);

        /** @var ServerUrlHelper $serverUrlHelper */
        $serverUrlHelper = $container->get(ServerUrlHelper::class);
        $engine->registerFunction('serverurl', $serverUrlHelper);
    }

    private function addTemplatePaths(ContainerInterface $container, Engine $engine): void
    {
        $config = (array) $container->get('config');
        $templates = (array) $config['templates'];
        $allPaths = (array) $templates['paths'];

        /**
         * @var string $namespace
         * @var array $pathArray
         */
        foreach ($allPaths as $namespace => $pathArray) {
            /** @var string $path */
            foreach ($pathArray as $path) {
                $engine->addPath($path, $namespace);
            }
        }
    }
}
