<?php

declare(strict_types=1);

namespace MobicmsTest\Log;

use Mobicms\Contract\ConfigInterface;
use Mobicms\System\Log\LoggerFactory;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class LoggerFactoryTest extends TestCase
{
    #[DataProvider('debugDataProvider')]
    public function testCreate(?bool $debug): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->method('get')
            ->willReturnCallback(
                fn($val) => match ($val) {
                    'log_file' => 'test.log',
                    'debug' => $debug,
                    default => null,
                }
            );
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with(ConfigInterface::class)
            ->willReturn($config);

        /** @var Logger $logger */
        $logger = (new LoggerFactory())($container);
        self::assertTrue($logger->isHandling($debug ? Level::Debug : Level::Warning));

        foreach ($logger->getHandlers() as $handler) {
            self::assertInstanceOf(StreamHandler::class, $handler);
        }
    }

    /**
     * @return iterable<array-key, array<array-key, bool|null>>
     */
    public static function debugDataProvider(): iterable
    {
        return [
            'debug-true'  => [true],
            'debug-false' => [false],
            'debug-null'  => [null],
        ];
    }
}
