<?php

declare(strict_types=1);

namespace Mobicms\ErrorHandler;

use HttpSoft\Basis\ErrorHandler\LogErrorListener;
use HttpSoft\ErrorHandler\ErrorHandlerMiddleware;
use Mobicms\Container\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class ErrorHandlerMiddlewareFactory implements FactoryInterface
{
    public function create(ContainerInterface $container): ErrorHandlerMiddleware
    {
        $errorHandler = new ErrorHandlerMiddleware(new WhoopsErrorResponseGenerator());
        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);
        $errorHandler->addListener(new LogErrorListener($logger));

        return $errorHandler;
    }
}
