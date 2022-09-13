<?php

declare(strict_types=1);

namespace Mobicms\System\Session;

use Devanych\Di\FactoryInterface;
use Mobicms\System\Config\ConfigInterface;
use PDO;
use Psr\Container\ContainerInterface;

class SessionMiddlewareFactory implements FactoryInterface
{
    public function create(ContainerInterface $container): SessionMiddleware
    {
        /** @var ConfigInterface $configContainer */
        $configContainer = $container->get(ConfigInterface::class);
        $config = (array) $configContainer->get('session', []);

        $timestampFile = $config['gc_timestamp_file'] ?? __FILE__;
        $gc = false;

        if (file_exists($timestampFile)) {
            if (filemtime($timestampFile) < time() - ($config['gc_period'] ?? 3600)) {
                $gc = true;
                touch($timestampFile);
            }
        } else {
            if (! touch($timestampFile)) {
                throw new \RuntimeException('Cannot white session GC timestamp file');
            };
        }

        return new SessionMiddleware($container->get(PDO::class), $config, $gc);
    }
}
