<?php

declare(strict_types=1);

namespace MobicmsTest\ErrorHandler;

use HttpSoft\ErrorHandler\ErrorHandlerMiddleware;
use Mobicms\Container\Container;
use Mobicms\Container\Exception\NotFoundException;
use Mobicms\ErrorHandler\ErrorHandlerMiddlewareFactory;
use Mobicms\Interface\ConfigInterface;
use Mobicms\Log\LoggerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;

class ErrorHandlerMiddlewareFactoryTest extends TestCase
{
    private ErrorHandlerMiddlewareFactory $factory;

    public function setUp(): void
    {
        $this->factory = new ErrorHandlerMiddlewareFactory();
    }

    public function debugDataProvider(): array
    {
        return [
            'debug-true'  => [true],
            'debug-false' => [false],
        ];
    }

    /**
     * @dataProvider debugDataProvider
     */
    public function testCreate(bool $debug): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->method('get')
            ->withConsecutive(['log_file'], ['debug'])
            ->willReturn('test.log', $debug);

        $container = new Container([ConfigInterface::class => $config]);
        $container->setFactory(LoggerInterface::class, LoggerFactory::class);

        $errorHandler = (new ErrorHandlerMiddlewareFactory())($container);
        $this->assertInstanceOf(MiddlewareInterface::class, $errorHandler);
        $this->assertInstanceOf(ErrorHandlerMiddleware::class, $errorHandler);
    }

    public function testCreateThrowNotFoundExceptionIfConfigIsNotSet(): void
    {
        $this->expectException(NotFoundException::class);
        (new ErrorHandlerMiddlewareFactory())(new Container());
    }

    public function testCreateThrowNotFoundExceptionIfLoggerInterfaceIsNotSet(): void
    {
        $this->expectException(NotFoundException::class);
        (new ErrorHandlerMiddlewareFactory())(new Container(['debug' => true, 'log_file' => 'test.log']));
    }
}
