<?php

declare(strict_types=1);

namespace MobicmsTest\Session;

use HttpSoft\Cookie\CookieManagerInterface;
use Mobicms\Config\ConfigInterface;
use Mobicms\Session\Exception\CannotWhiteTimestampException;
use Mobicms\Session\SessionMiddleware;
use Mobicms\Session\SessionMiddlewareFactory;
use Mobicms\Testutils\MysqlTestCase;
use Mobicms\Testutils\SqlDumpLoader;
use PDO;
use Psr\Container\ContainerInterface;

use function is_file;
use function time;
use function touch;
use function unlink;

class SessionMiddlewareFactoryTest extends MysqlTestCase
{
    private string $file;
    private SessionMiddlewareFactory $factory;

    public function setUp(): void
    {
        $this->file = __DIR__ . '/../../stubs/gc.timestamp';
        $this->factory = new SessionMiddlewareFactory();
    }

    public function tearDown(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }
    }

    public function testExceptionIfTimestampFileIsNotWritable(): void
    {
        $this->expectException(CannotWhiteTimestampException::class);
        $this->factory->checkGc(
            [
                'gc_timestamp_file' => 'unknown/gc.timestamp',
                'gc_period'         => 3600,
            ]
        );
    }

    public function testNeedGarbageCollection(): void
    {
        touch($this->file, time() - 10000);
        $this->assertTrue(
            $this->factory->checkGc(
                [
                    'gc_timestamp_file' => $this->file,
                    'gc_period'         => 3600,
                ]
            )
        );
    }

    public function testNotNeedGarbageCollection(): void
    {
        touch($this->file);
        $this->assertFalse(
            $this->factory->checkGc(
                [
                    'gc_timestamp_file' => $this->file,
                    'gc_period'         => 3600,
                ]
            )
        );
    }

    public function testCreateTimestampFile(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }

        $this->factory->checkGc(['gc_timestamp_file' => $this->file]);
        $this->assertTrue(is_file($this->file));
    }

    public function testFactoryReturnsSessionMiddlewareInstance()
    {
        $loader = new SqlDumpLoader(self::getPdo());
        $loader->loadFile('install/sql/system.sql');

        if ($loader->hasErrors()) {
            $this->fail(implode("\n", $loader->getErrors()));
        }

        touch($this->file, time() - 10000);
        $result = (new SessionMiddlewareFactory())($this->getContainer(['gc_timestamp_file' => $this->file]));
        $this->assertInstanceOf(SessionMiddleware::class, $result);
    }

    private function getContainer(array $options): ContainerInterface
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->method('get')
            ->with('session')
            ->willReturn($options);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(
                fn($val) => match ($val) {
                    ConfigInterface::class => $config,
                    PDO::class => self::getPdo(),
                    CookieManagerInterface::class => $this->createMock(CookieManagerInterface::class)
                }
            );

        return $container;
    }
}
