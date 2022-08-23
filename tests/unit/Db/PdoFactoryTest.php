<?php

declare(strict_types=1);

namespace MobicmsTest\System\Db;

use Devanych\Di\FactoryInterface;
use Mobicms\System\Db\Exception\CommonException;
use Mobicms\System\Db\Exception\InvalidCredentialsException;
use Mobicms\System\Db\Exception\InvalidDatabaseException;
use Mobicms\System\Db\PdoFactory;
use Mobicms\Testutils\ConfigLoader;
use Mobicms\Testutils\MysqlTestCase;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

class PdoFactoryTest extends MysqlTestCase
{
    private ConfigLoader $config;
    private MockObject $container;

    public function setUp(): void
    {
        $this->config = new ConfigLoader();
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container
            ->method('has')
            ->with('config')
            ->willReturn(true);
    }

    public function testFactoryReturnsInstanceOfPdo(): void
    {
        $this->container
            ->method('get')
            ->with('config')
            ->willReturn(
                [
                    'database' => [
                        'host'   => $this->config->host(),
                        'port'   => $this->config->port(),
                        'dbname' => $this->config->dbName(),
                        'user'   => $this->config->user(),
                        'pass'   => $this->config->password(),
                    ],
                ]
            );

        $factory = new PdoFactory();
        $this->assertInstanceOf(FactoryInterface::class, $factory);
        $this->assertInstanceOf(PDO::class, $factory->create($this->container));
    }

    public function testInvalidPasswordThrowInvalidCredentialsException(): void
    {
        $this->container
            ->method('get')
            ->with('config')
            ->willReturn(
                [
                    'database' => [
                        'host'   => $this->config->host(),
                        'port'   => $this->config->port(),
                        'dbname' => $this->config->dbName(),
                        'user'   => $this->config->user(),
                        'pass'   => 'invalid_password',
                    ],
                ]
            );

        $this->expectException(InvalidCredentialsException::class);
        (new PdoFactory())->create($this->container);
    }

    public function testInvalidUserThrowInvalidCredentialsException(): void
    {
        $this->container
            ->method('get')
            ->with('config')
            ->willReturn(
                [
                    'database' => [
                        'host'   => $this->config->host(),
                        'port'   => $this->config->port(),
                        'dbname' => $this->config->dbName(),
                        'user'   => 'invalid_user',
                        'pass'   => $this->config->password(),
                    ],
                ]
            );

        $this->expectException(InvalidCredentialsException::class);
        (new PdoFactory())->create($this->container);
    }

    public function testInvalidDatabaseNameThrowInvalidDatabaseException(): void
    {
        $this->container
            ->method('get')
            ->with('config')
            ->willReturn(
                [
                    'database' => [
                        'host'   => $this->config->host(),
                        'port'   => $this->config->port(),
                        'dbname' => 'invalid_database',
                        'user'   => $this->config->user(),
                        'pass'   => $this->config->password(),
                    ],
                ]
            );

        $this->expectException(InvalidDatabaseException::class);
        (new PdoFactory())->create($this->container);
    }

    public function testInvalidHostThrowUnableToConnectException(): void
    {
        $this->container
            ->method('get')
            ->with('config')
            ->willReturn(
                [
                    'database' => [
                        'host'   => 'invalid_host',
                        'port'   => $this->config->port(),
                        'dbname' => $this->config->dbName(),
                        'user'   => $this->config->user(),
                        'pass'   => $this->config->password(),
                    ],
                ]
            );

        $this->expectException(CommonException::class);
        (new PdoFactory())->create($this->container);
    }

    public function testInvalidPortThrowUnableToConnectException(): void
    {
        $this->container
            ->method('get')
            ->with('config')
            ->willReturn(
                [
                    'database' => [
                        'host'   => $this->config->host(),
                        'port'   => 999999999,
                        'dbname' => $this->config->dbName(),
                        'user'   => $this->config->user(),
                        'pass'   => $this->config->password(),
                    ],
                ]
            );

        $this->expectException(CommonException::class);
        (new PdoFactory())->create($this->container);
    }
}
