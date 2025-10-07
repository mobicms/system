<?php

declare(strict_types=1);

namespace Mobicms\System\Db;

use Mobicms\System\Db\Exception\CommonException;
use Mobicms\System\Db\Exception\InvalidDatabaseException;
use Mobicms\System\Db\Exception\InvalidCredentialsException;
use Mobicms\Contract\ConfigInterface;
use PDO;
use PDOException;
use Psr\Container\ContainerInterface;

/**
 * @psalm-api
 */
class PdoFactory
{
    public function __invoke(ContainerInterface $container): PDO
    {
        /** @var ConfigInterface $configContainer */
        $configContainer = $container->get(ConfigInterface::class);
        $config = (array) $configContainer->get('database', []);

        return $this->connect($config);
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function connect(array $config): PDO
    {
        try {
            return new PDO(
                sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    (string) ($config['host'] ?? 'localhost'),
                    (int) ($config['port'] ?? 3306),
                    (string) ($config['dbname'] ?? 'mobicms')
                ),
                (string) ($config['user'] ?? 'root'),
                (string) ($config['pass'] ?? 'root'),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            $code = (int) $exception->getCode();

            throw match ($code) {
                1045, 2054 => new InvalidCredentialsException('Invalid database credentials (user, password)', $code),
                1049 => new InvalidDatabaseException('Unknown database', $code),
                default => new CommonException($exception->getMessage(), $code)
            };
        }
    }
}
