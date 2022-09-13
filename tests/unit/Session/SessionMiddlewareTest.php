<?php

declare(strict_types=1);

namespace MobicmsTest\System\Session;

use Mobicms\System\Session\SessionMiddleware;
use Mobicms\Testutils\MysqlTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddlewareTest extends MysqlTestCase
{
    private SessionMiddleware $middleware;

    public function setUp(): void
    {
        $this->middleware = new SessionMiddleware(self::getPdo(), [], false);
    }

    public function testImplementsMiddlewareInterface(): void
    {
        $this->assertInstanceOf(MiddlewareInterface::class, $this->middleware);
    }

    public function testProcess(): void
    {
        $result = $this->middleware->process(
            $this->mockRequest(),
            $this->mockRequestHandler()
        );

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    private function mockRequest(): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('withAttribute')
            ->willReturn($request);

        return $request;
    }

    private function mockRequestHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->willReturn($this->createMock(ResponseInterface::class));

        return $handler;
    }
}
