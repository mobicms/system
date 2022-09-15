<?php

declare(strict_types=1);

namespace Mobicms\System\Session;

use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    private PDO $pdo;
    private array $options;
    private bool $gc;

    public function __construct(PDO $pdo, array $options, bool $gc)
    {
        $this->pdo = $pdo;
        $this->options = $options;
        $this->gc = $gc;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = new SessionHandler($this->pdo, $this->options);
        $session->startSession($request);

        if ($this->gc) {
            $session->garbageCollector();
        }

        $response = $handler->handle($request->withAttribute(SessionInterface::class, $session));

        return $session->persistSession($response);
    }
}
