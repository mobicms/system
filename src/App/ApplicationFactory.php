<?php

declare(strict_types=1);

namespace Mobicms\App;

use Devanych\Di\FactoryInterface;
use HttpSoft\{
    Basis\Application,
    Emitter\EmitterInterface,
    Router\RouteCollector,
    Runner\MiddlewarePipelineInterface,
    Runner\MiddlewareResolverInterface
};
use Mobicms\{
    Render\Engine,
    Config\ConfigInterface,
    ErrorHandler\NotFoundHandler
};
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class ApplicationFactory implements FactoryInterface
{
    public function create(ContainerInterface $container): Application
    {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress MixedArrayAccess
         */
        return new Application(
            $container->get(RouteCollector::class),
            $container->get(EmitterInterface::class),
            $container->get(MiddlewarePipelineInterface::class),
            $container->get(MiddlewareResolverInterface::class),
            new NotFoundHandler(
                $container->get(ResponseFactoryInterface::class),
                $container->get(Engine::class),
                'error::404',
                (bool) $config->get('debug')
            )
        );
    }
}
