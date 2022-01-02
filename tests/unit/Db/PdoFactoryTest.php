<?php

declare(strict_types=1);

namespace MobicmsTest\System\Db;

use Mobicms\System\Db\Exception\CommonException;
use Mobicms\System\Db\Exception\InvalidCredentialsException;
use Mobicms\System\Db\Exception\InvalidDatabaseException;
use Mobicms\System\Db\Exception\MissingConfigException;
use Mobicms\System\Db\Exception\UnableToConnectException;
use Mobicms\System\Db\PdoFactory;
use Mobicms\Testutils\DbHelpersTrait;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PdoFactoryTest extends TestCase
{
    use DbHelpersTrait;

    private MockObject $container;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container
            ->method('has')
            ->with('database')
            ->willReturn(true);
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testFactoryReturnsInstanceOfPdo(): void
    {
        $this->container
            ->method('get')
            ->with('database')
            ->willReturn(
                [
                    'host'   => self::$dbHost,
                    'port'   => self::$dbPort,
                    'dbname' => self::$dbName,
                    'user'   => self::$dbUser,
                    'pass'   => self::$dbPass,
                ]
            );

        $factory = (new PdoFactory())($this->container);
        $this->assertInstanceOf(PDO::class, $factory);
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testFactoryReturnsInstanceOfPdoUsingDsn(): void
    {
        $this->container
            ->method('get')
            ->with('database')
            ->willReturn(
                [
                    'dsn'  => self::$dsn,
                    'user' => self::$dbUser,
                    'pass' => self::$dbPass,
                ]
            );

        $factory = (new PdoFactory())($this->container);
        $this->assertInstanceOf(PDO::class, $factory);
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testInvalidPasswordThrowInvalidCredentialsException(): void
    {
        $this->container
            ->method('get')
            ->with('database')
            ->willReturn(
                [
                    'dsn'  => self::$dsn,
                    'user' => self::$dbUser,
                    'pass' => 'invalid_password',
                ]
            );

        $this->expectException(InvalidCredentialsException::class);
        (new PdoFactory())($this->container);
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testInvalidUserThrowInvalidCredentialsException(): void
    {
        $this->container
            ->method('get')
            ->with('database')
            ->willReturn(
                [
                    'dsn'  => self::$dsn,
                    'user' => 'invalid_user',
                    'pass' => self::$dbPass,
                ]
            );

        $this->expectException(InvalidCredentialsException::class);
        (new PdoFactory())($this->container);
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testInvalidDatabaseNameThrowInvalidDatabaseException(): void
    {
        $this->container
            ->method('get')
            ->with('database')
            ->willReturn(
                [
                    'host'   => self::$dbHost,
                    'port'   => self::$dbPort,
                    'dbname' => 'invalid_database',
                    'user'   => self::$dbUser,
                    'pass'   => self::$dbPass,
                ]
            );

        $this->expectException(InvalidDatabaseException::class);
        (new PdoFactory())($this->container);
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testInvalidHostThrowUnableToConnectException(): void
    {
        $this->container
            ->method('get')
            ->with('database')
            ->willReturn(
                [
                    'host'   => 'invalid_host',
                    'port'   => self::$dbPort,
                    'dbname' => self::$dbName,
                    'user'   => self::$dbUser,
                    'pass'   => self::$dbPass,
                ]
            );

        $this->expectException(UnableToConnectException::class);
        (new PdoFactory())($this->container);
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testInvalidPortThrowUnableToConnectException(): void
    {
        $this->container
            ->method('get')
            ->with('database')
            ->willReturn(
                [
                    'host'   => self::$dbHost,
                    'port'   => 9999999999,
                    'dbname' => self::$dbName,
                    'user'   => self::$dbUser,
                    'pass'   => self::$dbPass,
                ]
            );

        $this->expectException(UnableToConnectException::class);
        (new PdoFactory())($this->container);
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testInvalidDsnThrowCommonException(): void
    {
        $this->container
            ->method('get')
            ->with('database')
            ->willReturn(
                [
                    'dsn'  => 'invalid_dsn',
                    'user' => self::$dbUser,
                    'pass' => self::$dbPass,
                ]
            );

        $this->expectException(CommonException::class);
        (new PdoFactory())($this->container);
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testMissingConfigThrowMissingConfigException(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->with('database')
            ->willReturn(false);
        $this->expectException(MissingConfigException::class);
        (new PdoFactory())($container);
    }
}
