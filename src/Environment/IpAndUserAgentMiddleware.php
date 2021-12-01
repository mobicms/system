<?php

declare(strict_types=1);

namespace Mobicms\System\Environment;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IpAndUserAgentMiddleware implements MiddlewareInterface
{
    public const IP_ADDR = 'ip_address';
    public const IP_VIA_PROXY_ADDR = 'ip_via_proxy_address';
    public const USER_AGENT = 'http_user_agent';

    /** @var array<string> */
    private array $headersToInspect = [
        'Forwarded',
        'X-Forwarded-For',
        'X-Forwarded',
        'X-Cluster-Client-Ip',
        'Client-Ip',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (null !== ($ip = $this->determineIpAddress($request))) {
            $request = $request->withAttribute(self::IP_ADDR, $ip);
        }

        if (null !== ($ipVia = $this->determineIpViaProxyAddress($request))) {
            $request = $request->withAttribute(self::IP_VIA_PROXY_ADDR, $ipVia);
        }

        if (null !== ($ua = $this->determineUserAgent($request))) {
            $request = $request->withAttribute(self::USER_AGENT, $ua);
        }

        return $handler->handle($request);
    }

    public function determineIpAddress(ServerRequestInterface $request): ?string
    {
        $server = $request->getServerParams();

        if (isset($server['REMOTE_ADDR']) && $this->isValidIp((string) $server['REMOTE_ADDR'])) {
            return (string) $server['REMOTE_ADDR'];
        }

        return null;
    }

    public function determineIpViaProxyAddress(ServerRequestInterface $request): ?string
    {
        foreach ($this->headersToInspect as $header) {
            if (
                $request->hasHeader($header)
                && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $request->getHeaderLine($header), $vars)
                && null !== ($ip = $this->extractIp($request, $vars))
            ) {
                return $ip;
            }
        }

        return null;
    }

    private function extractIp(ServerRequestInterface $request, array $vars): ?string
    {
        /** @var array<array-key, array<array-key, string>> $vars */
        foreach ($vars[0] as $ip) {
            if (
                $this->isValidIp($ip)
                && ! preg_match('#^(10|172\.16|192\.168)\.#', $ip)
                && $ip !== $this->determineIpAddress($request)
            ) {
                return $ip;
            }
        }

        return null;
    }

    public function determineUserAgent(ServerRequestInterface $request): ?string
    {
        if ($request->hasHeader('User-Agent')) {
            $userAgent = mb_substr($request->getHeaderLine('User-Agent'), 0, 255);
            return htmlspecialchars($userAgent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return null;
    }

    public function isValidIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }
}
