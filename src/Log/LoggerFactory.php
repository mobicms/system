<?php

declare(strict_types=1);

namespace Mobicms\System\Log;

use Devanych\Di\FactoryInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class LoggerFactory implements FactoryInterface
{
    public function create(ContainerInterface $container): LoggerInterface
    {
        $logger = new Logger('App');

        /** @var array $config */
        $config = $container->get('config');

        $logger->pushHandler(
            new StreamHandler(
                (string) $config['log_file'],
                $config['debug'] ? Logger::DEBUG : Logger::WARNING
            )
        );

        return $logger;
    }
}
