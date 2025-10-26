<?php

declare(strict_types=1);

namespace Mobicms\System\ErrorHandler;

use HttpSoft\Basis\ErrorHandler\LogErrorListener;
use HttpSoft\ErrorHandler\ErrorHandlerMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-api
 */
final class ErrorHandlerMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ErrorHandlerMiddleware
    {
        $errorHandler = new ErrorHandlerMiddleware(new WhoopsErrorResponseGenerator());
        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);
        $errorHandler->addListener(new LogErrorListener($logger));

        return $errorHandler;
    }
}
