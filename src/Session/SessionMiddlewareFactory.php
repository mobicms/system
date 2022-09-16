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
        /** @var PDO $pdo */
        $pdo = $container->get(PDO::class);

        $config = (array) $configContainer->get('session', []);
        $session = new SessionHandler($pdo, $config);

        if ($this->checkGc($config)) {
            $session->garbageCollector();
        }

        return new SessionMiddleware($session);
    }

    public function checkGc(array $config): bool
    {
        $file = (string) ($config['gc_timestamp_file'] ?? '');
        $gcPeriod = (int) ($config['gc_period'] ?? 3600);

        if (! is_writable(dirname($file))) {
            throw new RuntimeException('Cannot white session GC timestamp file ' . $file);
        };

        if (file_exists($file)) {
            if (filemtime($file) < time() - $gcPeriod) {
                touch($file);
                return true;
            }
        } else {
            touch($file);
        }

        return false;
    }
}
