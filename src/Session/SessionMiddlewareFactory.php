<?php

declare(strict_types=1);

namespace Mobicms\System\Session;

use Devanych\Di\FactoryInterface;
use Mobicms\System\Config\ConfigInterface;
use PDO;
use Psr\Container\ContainerInterface;
use RuntimeException;

class SessionMiddlewareFactory implements FactoryInterface
{
    public function create(ContainerInterface $container): SessionMiddleware
    {
        /** @var ConfigInterface $configContainer */
        $configContainer = $container->get(ConfigInterface::class);
        $config = $configContainer->get('session', []);

        return new SessionMiddleware(
            $container->get(PDO::class),
            $config,
            $this->checkNeedGc(
                $config['gc_timestamp_file'] ?? '',
                $config['gc_period'] ?? 3600
            )
        );
    }

    public function checkNeedGc(string $file, int $gcPeriod): bool
    {
        if (file_exists($file)) {
            if (filemtime($file) < time() - $gcPeriod) {
                touch($file);
                return true;
            }
        } else {
            if (! is_writable(dirname($file))) {
                throw new RuntimeException('Cannot white session GC timestamp file ' . $file);
            };

            touch($file);
        }

        return false;
    }
}
