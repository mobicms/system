<?php

declare(strict_types=1);

namespace Mobicms\System\Log;

use Mobicms\Contract\ConfigInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * @psalm-api
 */
final class LoggerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): LoggerInterface
    {
        /** @var ConfigInterface $configContainer */
        $configContainer = $container->get(ConfigInterface::class);
        $logFile = (string) $configContainer->get('log_file');
        $debug = (bool) $configContainer->get('debug');

        $logger = new Logger('App');
        $logger->pushHandler(new StreamHandler($logFile, $debug ? Level::Debug : Level::Warning));

        return $logger;
    }
}
