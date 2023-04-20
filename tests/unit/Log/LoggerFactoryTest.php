<?php

declare(strict_types=1);

namespace MobicmsTest\Log;

use Mobicms\Config\ConfigInterface;
use Mobicms\Log\LoggerFactory;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LoggerFactoryTest extends TestCase
{
    /**
     * @dataProvider debugDataProvider
     */
    public function testCreate(?bool $debug): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->method('get')
            ->willReturnCallback(
                fn($val) => match ($val) {
                    'log_file' => 'test.log',
                    'debug' => $debug
                }
            );
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with(ConfigInterface::class)
            ->willReturn($config);

        /** @var Logger $logger */
        $logger = (new LoggerFactory())($container);
        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertTrue($logger->isHandling($debug ? Logger::DEBUG : Logger::WARNING));

        foreach ($logger->getHandlers() as $handler) {
            $this->assertInstanceOf(StreamHandler::class, $handler);
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
