<?php

declare(strict_types=1);

namespace Mobicms\System\Db;

use Mobicms\System\Db\Exception\CommonException;
use Mobicms\System\Db\Exception\MissingConfigException;
use Mobicms\System\Db\Exception\InvalidDatabaseException;
use Mobicms\System\Db\Exception\InvalidCredentialsException;
use Mobicms\System\Db\Exception\UnableToConnectException;
use PDO;
use Psr\Container\ContainerInterface;

class PdoFactory
{
    public function __invoke(ContainerInterface $container): PDO
    {
        if (! $container->has('database')) {
            throw new MissingConfigException('Missing database configuration', 0);
        }

        $config = (array) $container->get('database');

        return $this->factory(
            $this->prepareDsn($config),
            (string) ($config['user'] ?? ''),
            (string) ($config['pass'] ?? '')
        );
    }

    public function factory(string $dsn, string $user, string $password): PDO
    {
        try {
            return new PDO(
                $dsn,
                $user,
                $password,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (\PDOException $exception) {
            $code = (int) $exception->getCode();
            switch ($code) {
                case 1045:
                    throw new InvalidCredentialsException(
                        'Invalid database password',
                        $code
                    );
                case 1049:
                    throw new InvalidDatabaseException(
                        'Invalid database credentials (user, password)',
                        $code
                    );
                case 2002:
                    throw new UnableToConnectException(
                        'Unable to connect to the specified database server or port',
                        $code
                    );
                default:
                    throw new CommonException(
                        $exception->getMessage(),
                        $code
                    );
            }
        }
    }

    private function prepareDsn(array $config): string
    {
        if (! empty($config['dsn'])) {
            return (string) $config['dsn'];
        }

        return 'mysql:host='
            . (! empty($config['host']) ? (string) $config['host'] : 'localhost')
            . (! empty($config['port']) ? ';port=' . (int) $config['port'] : '')
            . ';dbname=' . (! empty($config['dbname']) ? (string) $config['dbname'] : '')
            . ';charset=utf8mb4';
    }
}
