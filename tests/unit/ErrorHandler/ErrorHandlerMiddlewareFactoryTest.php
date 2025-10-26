<?php

declare(strict_types=1);

namespace MobicmsTest\ErrorHandler;

use Mobicms\Container\Container;
use Mobicms\Container\Exception\NotFoundException;
use Mobicms\System\ErrorHandler\ErrorHandlerMiddlewareFactory;
use Mobicms\Contract\ConfigInterface;
use Mobicms\System\Log\LoggerFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;

class ErrorHandlerMiddlewareFactoryTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        restore_error_handler();
        restore_exception_handler();
    }

    #[DataProvider('debugDataProvider')]
    public function testCreate(bool $debug): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->method('get')
            ->willReturnCallback(
                fn(string $val) => match ($val) {
                    'log_file' => 'test.log',
                    'debug' => $debug,
                    default => null,
                }
            );

        $container = new Container(
            [
                'services' => [
                    ConfigInterface::class => $config,
                ],
            ]
        );
        $container->setFactory(LoggerInterface::class, LoggerFactory::class);

        $errorHandler = (new ErrorHandlerMiddlewareFactory())($container);
        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertInstanceOf(MiddlewareInterface::class, $errorHandler);
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

    /**
     * @return array<string, array<bool>>
     */
    public static function debugDataProvider(): array
    {
        return [
            'debug-true'  => [true],
            'debug-false' => [false],
        ];
    }
}
