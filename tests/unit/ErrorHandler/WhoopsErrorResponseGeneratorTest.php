<?php

declare(strict_types=1);

namespace MobicmsTest\ErrorHandler;

use HttpSoft\Basis\Exception\ForbiddenHttpException;
use HttpSoft\Basis\Exception\InternalServerErrorHttpException;
use HttpSoft\Message\ServerRequest;
use Mobicms\ErrorHandler\WhoopsErrorResponseGenerator;
use PHPUnit\Framework\TestCase;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Run;

use function json_encode;

class WhoopsErrorResponseGeneratorTest extends TestCase
{
    public function testGenerateWithDefaultPrettyPageHandler(): void
    {
        $generator = new WhoopsErrorResponseGenerator();
        $exception = new InternalServerErrorHttpException();
        $response = $generator->generate($exception, new ServerRequest());

        $this->assertSame($exception->getStatusCode(), $response->getStatusCode());
        $this->assertSame($exception->getReasonPhrase(), $response->getReasonPhrase());
        $this->assertSame('text/html', $response->getHeaderLine('content-type'));
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

        $this->assertSame($exception->getStatusCode(), $response->getStatusCode());
        $this->assertSame($exception->getReasonPhrase(), $response->getReasonPhrase());
        $this->assertSame('application/json', $response->getHeaderLine('content-type'));

        $exceptionData = json_encode(
            [
                'error' => [
                    'type'    => $exception::class,
                    'message' => $exception->getMessage(),
                    'code'    => $exception->getCode(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                ],
            ]
        );

        $this->assertSame($exceptionData, (string) $response->getBody());
    }
}
