<?php

declare(strict_types=1);

namespace MobicmsTest\System\Session;

use Mobicms\System\Config\ConfigInterface;
use Mobicms\System\Session\SessionMiddleware;
use Mobicms\System\Session\SessionMiddlewareFactory;
use Mobicms\Testutils\MysqlTestCase;
use PDO;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function is_file;
use function unlink;

class SessionMiddlewareFactoryTest extends MysqlTestCase
{
    private string $file;

    public function setUp(): void
    {
        $this->file = __DIR__ . '/../../stubs/gc.timestamp';
    }

    public function tearDown(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }
    }

    public function testExceptionIfTimestampFileIsNotWritable(): void
    {
        $file = 'unknown/gc.timestamp';

        $factory = new SessionMiddlewareFactory();
        $this->expectException(RuntimeException::class);
        $factory->checkNeedGc($file, 3600);
    }

    public function testNeedGc(): void
    {
        touch($this->file, time() - 10000);
        $factory = new SessionMiddlewareFactory();
        $this->assertTrue($factory->checkNeedGc($this->file, 3600));
    }

    public function testNotNeedGc(): void
    {
        touch($this->file);
        $factory = new SessionMiddlewareFactory();
        $this->assertFalse($factory->checkNeedGc($this->file, 3600));
    }

    public function testFactoryReturnsSessionMiddlewareInstance()
    {
        $factory = new SessionMiddlewareFactory();
        $result = $factory->create($this->getContainer(['gc_timestamp_file' => $this->file]));
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
            ->withConsecutive([ConfigInterface::class], [PDO::class])
            ->willReturn($config, self::getPdo());

        return $container;
    }
}
