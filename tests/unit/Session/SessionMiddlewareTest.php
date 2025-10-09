<?php

declare(strict_types=1);

namespace MobicmsTest\System\Session;

use HttpSoft\Cookie\CookieManagerInterface;
use Mobicms\System\Session\SessionHandler;
use Mobicms\System\Session\SessionMiddleware;
use Mobicms\Testutils\MysqlTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddlewareTest extends MysqlTestCase
{
    private SessionMiddleware $middleware;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function setUp(): void
    {
        $this->middleware = new SessionMiddleware(
            new SessionHandler(
                self::getPdo(),
                $this->createMock(CookieManagerInterface::class)
            )
        );
    }

    public function testProcess(): void
    {
        $result = $this->middleware->process(
            $this->mockRequest(),
            $this->mockRequestHandler()
        );

        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertInstanceOf(ResponseInterface::class, $result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function mockRequest(): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('withAttribute')
            ->willReturn($request);

        return $request;
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function mockRequestHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->method('handle')
            ->willReturn($this->createMock(ResponseInterface::class));

        return $handler;
    }
}
