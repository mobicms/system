<?php

declare(strict_types=1);

namespace MobicmsTest\System\Db;

use Devanych\Di\FactoryInterface;
use Mobicms\System\Config\ConfigInterface;
use Mobicms\System\Db\Exception\CommonException;
use Mobicms\System\Db\Exception\InvalidCredentialsException;
use Mobicms\System\Db\Exception\InvalidDatabaseException;
use Mobicms\System\Db\PdoFactory;
use Mobicms\Testutils\ConfigLoader;
use Mobicms\Testutils\MysqlTestCase;
use PDO;
use Psr\Container\ContainerInterface;

class PdoFactoryTest extends MysqlTestCase
{
    private ConfigLoader $config;

    public function setUp(): void
    {
        $this->config = new ConfigLoader();
    }

    public function testFactoryReturnsInstanceOfPdo(): void
    {
        $config = [
            'host'   => $this->config->host(),
            'port'   => $this->config->port(),
            'dbname' => $this->config->dbName(),
            'user'   => $this->config->user(),
            'pass'   => $this->config->password(),
        ];

        $factory = new PdoFactory();
        $this->assertInstanceOf(FactoryInterface::class, $factory);
        $this->assertInstanceOf(PDO::class, $factory->create($this->getContainer($config)));
    }

    public function testInvalidPasswordThrowInvalidCredentialsException(): void
    {
        $config = [
            'host'   => $this->config->host(),
            'port'   => $this->config->port(),
            'dbname' => $this->config->dbName(),
            'user'   => $this->config->user(),
            'pass'   => 'invalid_password',
        ];

        $this->expectException(InvalidCredentialsException::class);
        (new PdoFactory())->create($this->getContainer($config));
    }

    public function testInvalidUserThrowInvalidCredentialsException(): void
    {
        $config = [
            'host'   => $this->config->host(),
            'port'   => $this->config->port(),
            'dbname' => $this->config->dbName(),
            'user'   => 'invalid_user',
            'pass'   => $this->config->password(),
        ];

        $this->expectException(InvalidCredentialsException::class);
        (new PdoFactory())->create($this->getContainer($config));
    }

    public function testInvalidDatabaseNameThrowInvalidDatabaseException(): void
    {
        $config = [
            'host'   => $this->config->host(),
            'port'   => $this->config->port(),
            'dbname' => 'invalid_database',
            'user'   => $this->config->user(),
            'pass'   => $this->config->password(),
        ];

        $this->expectException(InvalidDatabaseException::class);
        (new PdoFactory())->create($this->getContainer($config));
    }

    public function testInvalidHostThrowUnableToConnectException(): void
    {
        $config = [
            'host'   => 'invalid_host',
            'port'   => $this->config->port(),
            'dbname' => $this->config->dbName(),
            'user'   => $this->config->user(),
            'pass'   => $this->config->password(),
        ];

        $this->expectException(CommonException::class);
        (new PdoFactory())->create($this->getContainer($config));
    }

    public function testInvalidPortThrowUnableToConnectException(): void
    {
        $config = [
            'host'   => $this->config->host(),
            'port'   => 999999,
            'dbname' => $this->config->dbName(),
            'user'   => $this->config->user(),
            'pass'   => $this->config->password(),
        ];

        $this->expectException(CommonException::class);
        (new PdoFactory())->create($this->getContainer($config));
    }

    private function getContainer(array $values): ContainerInterface
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->method('has')
            ->with('database')
            ->willReturn(true);
        $config
            ->method('get')
            ->with('database')
            ->willReturn($values);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->with(ConfigInterface::class)
            ->willReturn(true);
        $container
            ->method('get')
            ->with(ConfigInterface::class)
            ->willReturn($config);

        return $container;
    }
}
