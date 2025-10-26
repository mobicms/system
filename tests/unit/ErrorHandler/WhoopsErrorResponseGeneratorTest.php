<?php

declare(strict_types=1);

namespace MobicmsTest\ErrorHandler;

use HttpSoft\Basis\Exception\ForbiddenHttpException;
use HttpSoft\Basis\Exception\InternalServerErrorHttpException;
use HttpSoft\Message\ServerRequest;
use Mobicms\System\ErrorHandler\WhoopsErrorResponseGenerator;
use PHPUnit\Framework\TestCase;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Run;

use function json_encode;

class WhoopsErrorResponseGeneratorTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        restore_error_handler();
        restore_exception_handler();
    }

    public function testGenerateWithDefaultPrettyPageHandler(): void
    {
        $generator = new WhoopsErrorResponseGenerator();
        $exception = new InternalServerErrorHttpException();
        $response = $generator->generate($exception, new ServerRequest());

        self::assertSame($exception->getStatusCode(), $response->getStatusCode());
        self::assertSame($exception->getReasonPhrase(), $response->getReasonPhrase());
        self::assertSame('text/html', $response->getHeaderLine('content-type'));
    }

    public function testGenerateWithPassedJsonResponseHandler(): void
    {
        $handler = new JsonResponseHandler();
        $handler->addTraceToOutput(false);

        $whoops = new Run();
        $whoops->pushHandler($handler);
        $whoops->writeToOutput(false);
        $whoops->allowQuit(false);
        $whoops->register();

        $generator = new WhoopsErrorResponseGenerator($whoops);
        $exception = new ForbiddenHttpException();
        $response = $generator->generate($exception, new ServerRequest());

        self::assertSame($exception->getStatusCode(), $response->getStatusCode());
        self::assertSame($exception->getReasonPhrase(), $response->getReasonPhrase());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        $exceptionData = json_encode(
            [
                'error' => [
                    'type' => $exception::class,
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ],
            ]
        );

        self::assertSame($exceptionData, (string)$response->getBody());
    }
}
