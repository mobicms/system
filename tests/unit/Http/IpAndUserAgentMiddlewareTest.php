<?php

declare(strict_types=1);

namespace MobicmsTest\Http;

use Mobicms\System\Http\IpAndUserAgentMiddleware;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IpAndUserAgentMiddlewareTest extends TestCase
{
    /**
     * @var MockObject|ServerRequestInterface
     */
    private ServerRequestInterface|MockObject $request;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
    }

    public function testDetermineIpAddress(): void
    {
        $this->request
            ->expects(self::once())
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '31.23.209.1']);
        $middleware = new IpAndUserAgentMiddleware();
        self::assertSame('31.23.209.1', $middleware->determineIpAddress($this->request));
    }

    public function testDetermineIpAddressWithInvalidIpReturnsNull(): void
    {
        $this->request
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '392.268.0.9']);
        $middleware = new IpAndUserAgentMiddleware();
        self::assertNull($middleware->determineIpAddress($this->request));
    }

    public function testDetermineIpAddressWithoutIp(): void
    {
        $this->request
            ->expects(self::once())
            ->method('getServerParams')
            ->willReturn([]);
        $middleware = new IpAndUserAgentMiddleware();
        self::assertNull($middleware->determineIpAddress($this->request));
    }

    public function testDetermineIpViaProxyAddress(): void
    {
        $this->request
            ->expects(self::once())
            ->method('hasHeader')
            ->with('Forwarded')
            ->willReturn(true);
        $this->request
            ->expects(self::once())
            ->method('getHeaderLine')
            ->with('Forwarded')
            ->willReturn('212.58.119.76, 91.221.6.36');
        $this->request
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '31.23.209.1']);
        $middleware = new IpAndUserAgentMiddleware();
        self::assertSame('212.58.119.76', $middleware->determineIpViaProxyAddress($this->request));
    }

    public function testDetermineIpViaProxyAddressSkipPrivateNetworks(): void
    {
        $this->request
            ->expects(self::once())
            ->method('hasHeader')
            ->with('Forwarded')
            ->willReturn(true);
        $this->request
            ->expects(self::once())
            ->method('getHeaderLine')
            ->with('Forwarded')
            ->willReturn('10.0.0.1, 172.16.0.1, 192.168.0.1, 212.58.119.76');
        $this->request
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '31.23.209.1']);
        $middleware = new IpAndUserAgentMiddleware();
        self::assertSame('212.58.119.76', $middleware->determineIpViaProxyAddress($this->request));
    }

    public function testDetermineIpViaProxyAddressSkipSameIpAsRemote(): void
    {
        $this->request
            ->expects(self::once())
            ->method('hasHeader')
            ->with('Forwarded')
            ->willReturn(true);
        $this->request
            ->expects(self::once())
            ->method('getHeaderLine')
            ->with('Forwarded')
            ->willReturn('31.23.209.1, 212.58.119.76');
        $this->request
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '31.23.209.1']);
        $middleware = new IpAndUserAgentMiddleware();
        self::assertSame('212.58.119.76', $middleware->determineIpViaProxyAddress($this->request));
    }

    public function testDetermineIpViaProxyAddressWithoutValidIp(): void
    {
        $this->request
            ->method('hasHeader')
            ->willReturn(true);
        $this->request
            ->method('getHeaderLine')
            ->willReturn('331.23.209.1, test');
        $middleware = new IpAndUserAgentMiddleware();
        self::assertNull($middleware->determineIpViaProxyAddress($this->request));
    }

    public function testDetermineIpViaProxyAddressWithoutRequiredHeaders(): void
    {
        $this->request
            ->method('hasHeader')
            ->willReturn(false);
        $middleware = new IpAndUserAgentMiddleware();
        self::assertNull($middleware->determineIpViaProxyAddress($this->request));
    }

    public function testDetermineUserAgent(): void
    {
        $this->request
            ->expects(self::once())
            ->method('hasHeader')
            ->with('User-Agent')
            ->willReturn(true);
        $this->request
            ->expects(self::once())
            ->method('getHeaderLine')
            ->with('User-Agent')
            ->willReturn('Test User Agent');
        $middleware = new IpAndUserAgentMiddleware();
        self::assertSame('Test User Agent', $middleware->determineUserAgent($this->request));
    }

    public function testDetermineUserAgentTrimLongStringTo255Symbols(): void
    {
        $this->request
            ->method('hasHeader')
            ->with('User-Agent')
            ->willReturn(true);
        $this->request
            ->method('getHeaderLine')
            ->with('User-Agent')
            ->willReturn(str_repeat('a', 300));
        $middleware = new IpAndUserAgentMiddleware();
        self::assertSame(str_repeat('a', 255), $middleware->determineUserAgent($this->request));
    }

    public function testDetermineUserAgentWithoutRequiredHeaders(): void
    {
        $this->request
            ->method('hasHeader')
            ->with('User-Agent')
            ->willReturn(false);
        $this->request
            ->expects(self::never())
            ->method('getHeaderLine');

        $middleware = new IpAndUserAgentMiddleware();
        self::assertNull($middleware->determineUserAgent($this->request));
    }

    public function testProcess(): void
    {
        $this->request
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '192.168.0.9']);

        $this->request
            ->method('hasHeader')
            ->willReturnCallback(
                fn($val) => match ($val) {
                    'Forwarded' => true,
                    'User-Agent' => true,
                    default => false,
                }
            );

        $this->request
            ->method('getHeaderLine')
            ->willReturnCallback(
                fn($val) => match ($val) {
                    'Forwarded' => '212.58.119.76, 91.221.6.36',
                    'User-Agent' => 'Test User Agent',
                    default => '',
                }
            );

        $this->request
            ->expects(self::exactly(3))
            ->method('withAttribute')
            ->willReturnCallback(
                /** @phpstan-ignore match.unhandled */
                fn($key, $value) => match ([$key, $value]) {
                    [IpAndUserAgentMiddleware::IP_ADDR, '192.168.0.9'],
                    [IpAndUserAgentMiddleware::USER_AGENT, 'Test User Agent'],
                    [IpAndUserAgentMiddleware::IP_VIA_PROXY_ADDR, '212.58.119.76']
                    => $this->request
                }
            );

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects(self::once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->createMock(ResponseInterface::class));
        $middleware = new IpAndUserAgentMiddleware();
        $middleware->process($this->request, $handler);
    }
}
