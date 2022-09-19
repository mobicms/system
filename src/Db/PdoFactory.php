<?php

declare(strict_types=1);

namespace Mobicms\Db;

use Devanych\Di\FactoryInterface;
use Mobicms\Db\Exception\{
    CommonException,
    InvalidDatabaseException,
    InvalidCredentialsException
};
use Mobicms\Interface\ConfigInterface;
use PDO;
use PDOException;
use Psr\Container\ContainerInterface;

class PdoFactory implements FactoryInterface
{
    public function create(ContainerInterface $container): PDO
    {
        /** @var ConfigInterface $configContainer */
        $configContainer = $container->get(ConfigInterface::class);
        $config = (array) $configContainer->get('database', []);

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
                1045 => new InvalidCredentialsException('Invalid database credentials (user, password)', $code),
                1049 => new InvalidDatabaseException('Unknown database', $code),
                default => new CommonException($exception->getMessage(), $code)
            };
        }
    }
}
