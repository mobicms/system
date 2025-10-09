<?php

declare(strict_types=1);

namespace Mobicms\System\Session;

use Mobicms\Contract\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @psalm-api
 */
final class SessionMiddleware implements MiddlewareInterface
{
    private SessionHandler $session;

    public function __construct(SessionHandler $session)
    {
        $this->session = $session;
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->session->startSession($request);
        $response = $handler->handle($request->withAttribute(SessionInterface::class, $this->session));

        return $this->session->persistSession($response);
    }
}
