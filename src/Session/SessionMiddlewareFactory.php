<?php

declare(strict_types=1);

namespace Mobicms\Session;

use HttpSoft\Cookie\CookieManagerInterface;
use Mobicms\Config\ConfigInterface;
use Mobicms\Session\Exception\CannotWhiteTimestampException;
use PDO;
use Psr\Container\ContainerInterface;

class SessionMiddlewareFactory
{
    /**
     * @psalm-suppress MixedArgument
     */
    public function __invoke(ContainerInterface $container): SessionMiddleware
    {
        /** @var ConfigInterface $configContainer */
        $configContainer = $container->get(ConfigInterface::class);
        $config = (array) $configContainer->get('session', []);

        $session = new SessionHandler(
            $container->get(PDO::class),
            $container->get(CookieManagerInterface::class),
            $config
        );

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
            throw new CannotWhiteTimestampException($file);
        }

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
