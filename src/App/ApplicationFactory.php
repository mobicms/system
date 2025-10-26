<?php

declare(strict_types=1);

namespace Mobicms\System\App;

use HttpSoft\Basis\Application;
use HttpSoft\Emitter\EmitterInterface;
use HttpSoft\Router\RouteCollector;
use HttpSoft\Runner\MiddlewarePipelineInterface;
use HttpSoft\Runner\MiddlewareResolverInterface;
use Mobicms\System\ErrorHandler\NotFoundHandler;
use Mobicms\Contract\ConfigInterface;
use Mobicms\Render\Engine;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * @psalm-api
 */
final class ApplicationFactory
{
    public function __invoke(ContainerInterface $container): Application
    {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        /**
         * @psalm-suppress MixedArgument
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
