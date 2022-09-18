<?php

declare(strict_types=1);

namespace Mobicms\Log;

use Devanych\Di\FactoryInterface;
use Mobicms\Config\ConfigInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class LoggerFactory implements FactoryInterface
{
    public function create(ContainerInterface $container): LoggerInterface
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
