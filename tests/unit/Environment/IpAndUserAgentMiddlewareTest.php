<?php

declare(strict_types=1);

namespace MobicmsTest\System\Environment;

use Mobicms\System\Environment\IpAndUserAgentMiddleware;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @psalm-suppress MissingConstructor
 */
class IpAndUserAgentMiddlewareTest extends MockeryTestCase
{
    public function testDetermineIpAddress(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getServerParams')
            ->once()
            ->andReturn(['REMOTE_ADDR' => '31.23.209.1']);
        $middleware = new IpAndUserAgentMiddleware();
        $this->assertSame('31.23.209.1', $middleware->determineIpAddress($request));
    }

    public function testDetermineIpAddressWithInvalidIp(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getServerParams')
            ->once()
            ->andReturn(['REMOTE_ADDR' => '392.268.0.9']);
        $middleware = new IpAndUserAgentMiddleware();
        $this->assertNull($middleware->determineIpAddress($request));
    }

    public function testDetermineIpAddressWithoutIp(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getServerParams')
            ->once()
            ->andReturn([]);
        $middleware = new IpAndUserAgentMiddleware();
        $this->assertNull($middleware->determineIpAddress($request));
    }

    public function testDetermineIpViaProxyAddress(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')
            ->with('Forwarded')
            ->once()
            ->andReturn(true);
        $request->shouldReceive('getHeaderLine')
            ->with('Forwarded')
            ->once()
            ->andReturn('212.58.119.76, 91.221.6.36');
        $request->shouldReceive('getServerParams')
            ->andReturn(['REMOTE_ADDR' => '31.23.209.1']);
        $middleware = new IpAndUserAgentMiddleware();
        $this->assertSame('212.58.119.76', $middleware->determineIpViaProxyAddress($request));
    }

    public function testDetermineIpViaProxyAddressSkipPrivateNetworks(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')
            ->with('Forwarded')
            ->once()
            ->andReturn(true);
        $request->shouldReceive('getHeaderLine')
            ->with('Forwarded')
            ->once()
            ->andReturn('10.0.0.1, 172.16.0.1, 192.168.0.1, 212.58.119.76');
        $request->shouldReceive('getServerParams')
            ->andReturn(['REMOTE_ADDR' => '31.23.209.1']);
        $middleware = new IpAndUserAgentMiddleware();
        $this->assertSame('212.58.119.76', $middleware->determineIpViaProxyAddress($request));
    }

    public function testDetermineIpViaProxyAddressSkipSameIpAsRemote(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')
            ->with('Forwarded')
            ->once()
            ->andReturn(true);
        $request->shouldReceive('getHeaderLine')
            ->with('Forwarded')
            ->once()
            ->andReturn('31.23.209.1, 212.58.119.76');
        $request->shouldReceive('getServerParams')
            ->andReturn(['REMOTE_ADDR' => '31.23.209.1']);
        $middleware = new IpAndUserAgentMiddleware();
        $this->assertSame('212.58.119.76', $middleware->determineIpViaProxyAddress($request));
    }

    public function testDetermineIpViaProxyAddressWithoutValidIp(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')
            ->andReturn(true);
        $request->shouldReceive('getHeaderLine')
            ->andReturn('331.23.209.1, test');
        $middleware = new IpAndUserAgentMiddleware();
        $this->assertNull($middleware->determineIpViaProxyAddress($request));
    }

    public function testDetermineIpViaProxyAddressWithoutRequiredHeaders(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')
            ->andReturn(false);
        $middleware = new IpAndUserAgentMiddleware();
        $this->assertNull($middleware->determineIpViaProxyAddress($request));
    }

    public function testDetermineUserAgent(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')
            ->with('User-Agent')
            ->once()
            ->andReturn(true);
        $request->shouldReceive('getHeaderLine')
            ->with('User-Agent')
            ->once()
            ->andReturn('Test User Agent');
        $middleware = new IpAndUserAgentMiddleware();
        $this->assertSame('Test User Agent', $middleware->determineUserAgent($request));
    }

    public function testDetermineUserAgentTrimLongStringTo255Symbols(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')
            ->with('User-Agent')
            ->once()
            ->andReturn(true);
        $request->shouldReceive('getHeaderLine')
            ->with('User-Agent')
            ->once()
            ->andReturn(str_repeat('a', 300));
        $middleware = new IpAndUserAgentMiddleware();
        $this->assertSame(str_repeat('a', 255), $middleware->determineUserAgent($request));
    }

    public function testDetermineUserAgentSanitizeSpecialChars(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')
            ->with('User-Agent')
            ->once()
            ->andReturn(true);
        $request->shouldReceive('getHeaderLine')
            ->with('User-Agent')
            ->once()
            ->andReturn('&"\'<>');
        $middleware = new IpAndUserAgentMiddleware();
        $this->assertSame('&amp;&quot;&#039;&lt;&gt;', $middleware->determineUserAgent($request));
    }

    public function testDetermineUserAgentWithoutRequiredHeaders(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('hasHeader')
            ->with('User-Agent')
            ->once()
            ->andReturn(false);
        $request->shouldNotReceive('getHeaderLine');

        $middleware = new IpAndUserAgentMiddleware();
        $this->assertNull($middleware->determineUserAgent($request));
    }

    public function testProcess(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);

        // Check determime IP
        $request->shouldReceive('getServerParams')
            ->andReturn(['REMOTE_ADDR' => '192.168.0.9']);
        $request->shouldReceive('withAttribute')
            ->with(IpAndUserAgentMiddleware::IP_ADDR, '192.168.0.9')
            ->once()
            ->andReturn($request);

        // Check determine IP via Proxy
        $request->shouldReceive('hasHeader')
            ->with('Forwarded')
            ->andReturn(true);
        $request->shouldReceive('getHeaderLine')
            ->with('Forwarded')
            ->andReturn('212.58.119.76, 91.221.6.36');
        $request->shouldReceive('withAttribute')
            ->with(IpAndUserAgentMiddleware::IP_VIA_PROXY_ADDR, '212.58.119.76')
            ->once()
            ->andReturn($request);

        // Check determine User Agent
        $request->shouldReceive('hasHeader')
            ->with('User-Agent')
            ->andReturn(true);
        $request->shouldReceive('getHeaderLine')
            ->with('User-Agent')
            ->andReturn('Test User Agent');
        $request->shouldReceive('withAttribute')
            ->with(IpAndUserAgentMiddleware::USER_AGENT, 'Test User Agent')
            ->once()
            ->andReturn($request);

        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('handle')
            ->with($request)
            ->once()
            ->andReturn(Mockery::mock(ResponseInterface::class));

        $middleware = new IpAndUserAgentMiddleware();
        $middleware->process($request, $handler);
    }
}
