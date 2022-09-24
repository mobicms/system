<?php

declare(strict_types=1);

namespace Mobicms\Log;

use Mobicms\Interface\ConfigInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public function __invoke(ContainerInterface $container): LoggerInterface
    {
        /** @var ConfigInterface $configContainer */
        $configContainer = $container->get(ConfigInterface::class);
        $logFile = (string) $configContainer->get('log_file');
        $debug = (bool) $configContainer->get('debug');

        $logger = new Logger('App');
        $logger->pushHandler(new StreamHandler($logFile, $debug ? Logger::DEBUG : Logger::WARNING));

        return $logger;
    }
}
