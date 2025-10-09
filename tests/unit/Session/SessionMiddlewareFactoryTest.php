<?php

declare(strict_types=1);

namespace MobicmsTest\System\Session;

use HttpSoft\Cookie\CookieManagerInterface;
use Mobicms\Contract\ConfigInterface;
use Mobicms\System\Session\Exception\CannotWhiteTimestampException;
use Mobicms\System\Session\SessionMiddleware;
use Mobicms\System\Session\SessionMiddlewareFactory;
use Mobicms\Testutils\MysqlTestCase;
use Mobicms\Testutils\SqlDumpLoader;
use PDO;
use PHPUnit\Framework\MockObject\Exception;
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
        $this->file = __DIR__ . '/gc.timestamp';
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
        self::assertTrue(
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
        self::assertFalse(
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
        self::assertTrue(is_file($this->file));
    }

    public function testFactoryReturnsSessionMiddlewareInstance(): void
    {
        $loader = new SqlDumpLoader(self::getPdo());
        $loader->loadFile('tests/stub/dump.sql');

        if ($loader->hasErrors()) {
            self::fail(implode("\n", $loader->getErrors()));
        }

        touch($this->file, time() - 10000);
        $result = (new SessionMiddlewareFactory())($this->getContainer(['gc_timestamp_file' => $this->file]));
        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertInstanceOf(SessionMiddleware::class, $result);
    }

    /**
     * @param array<array-key, string> $options
     * @throws Exception
     */
    private function getContainer(array $options): ContainerInterface
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->method('get')
            ->with('session')
            ->willReturn($options);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->with(ConfigInterface::class)
            ->willReturn(true);
        $container
            ->method('get')
            ->willReturnCallback(
                fn($val) => match ($val) {
                    ConfigInterface::class => $config,
                    PDO::class => self::getPdo(),
                    CookieManagerInterface::class => $this->createMock(CookieManagerInterface::class),
                    default => null,
                }
            );

        return $container;
    }
}
