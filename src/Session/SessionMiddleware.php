<?php

declare(strict_types=1);

namespace Mobicms\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    private SessionHandler $session;

    public function __construct(SessionHandler $session)
    {
        $this->session = $session;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->session->startSession($request);
        $response = $handler->handle($request->withAttribute(SessionInterface::class, $this->session));

        return $this->session->persistSession($response);
    }
}
