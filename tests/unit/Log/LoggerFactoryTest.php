<?php

declare(strict_types=1);

namespace MobicmsTest\Log;

use Mobicms\Config\ConfigInterface;
use Mobicms\Log\LoggerFactory;
use Devanych\Di\Container;
use Devanych\Di\Exception\NotFoundException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggerFactoryTest extends TestCase
{
    private LoggerFactory $factory;

    public function setUp(): void
    {
        $this->factory = new LoggerFactory();
    }

    /**
     * @dataProvider debugDataProvider
     */
    public function testCreate(?bool $debug): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->method('get')
            ->withConsecutive(['log_file'], ['debug'])
            ->willReturn('test.log', $debug);

        $container = new Container(
            [
                ConfigInterface::class => $config,
            ]
        );

        /** @var Logger $logger */
        $logger = $this->factory->create($container);
        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertTrue($logger->isHandling($debug ? Logger::DEBUG : Logger::WARNING));

        foreach ($logger->getHandlers() as $handler) {
            $this->assertInstanceOf(StreamHandler::class, $handler);
        }
    }

    public function testCreateThrowNotFoundExceptionIfConfigIsNotSet(): void
    {
        $this->expectException(NotFoundException::class);
        $this->factory->create(new Container());
    }

    /**
     * @return iterable<array-key, array<array-key, bool|null>>
     */
    public function debugDataProvider(): iterable
    {
        return [
            'debug-true'  => [true],
            'debug-false' => [false],
            'debug-null'  => [null],
        ];
    }
}
