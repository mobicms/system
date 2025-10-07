<?php

declare(strict_types=1);

namespace MobicmsTest\Db;

use Mobicms\System\Db\Exception\CommonException;
use Mobicms\System\Db\Exception\InvalidCredentialsException;
use Mobicms\System\Db\Exception\InvalidDatabaseException;
use Mobicms\System\Db\PdoFactory;
use Mobicms\Contract\ConfigInterface;
use Mobicms\Testutils\ConfigLoader;
use Mobicms\Testutils\MysqlTestCase;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerInterface;

class PdoFactoryTest extends MysqlTestCase
{
    private ConfigLoader $config;

    public function setUp(): void
    {
        $this->config = new ConfigLoader();
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
        (new PdoFactory())($this->getContainer($config));
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
        (new PdoFactory())($this->getContainer($config));
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
        (new PdoFactory())($this->getContainer($config));
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
        (new PdoFactory())($this->getContainer($config));
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
        (new PdoFactory())($this->getContainer($config));
    }

    /**
     * @param array<mixed> $values
     * @throws Exception
     */
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
