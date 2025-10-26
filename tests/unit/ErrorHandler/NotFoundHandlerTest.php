<?php

declare(strict_types=1);

namespace MobicmsTest\ErrorHandler;

use HttpSoft\Basis\Response\CustomResponseFactory;
use Mobicms\Render\Engine;
use Mobicms\System\ErrorHandler\NotFoundHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

use function trim;

class NotFoundHandlerTest extends TestCase
{
    private ServerRequestInterface $request;
    private Engine $renderer;

    /**
     * @var ResponseFactoryInterface
     */
    private ResponseFactoryInterface $responseFactory;

    public function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->renderer = new Engine();
        $this->renderer->addPath(__DIR__);
        $this->responseFactory = new CustomResponseFactory();
    }

    public function testHandleWithoutDebugMode(): void
    {
        $handler = new NotFoundHandler($this->responseFactory, $this->renderer, 'template-not-found', false);
        $response = $handler->handle($this->request);
        self::assertSame('404 Not Found', trim((string) $response->getBody()));
    }

    public function testHandleWithDebugMode(): void
    {
        $handler = new NotFoundHandler($this->responseFactory, $this->renderer, 'template-not-found', true);
        $response = $handler->handle($this->request);
        self::assertSame('DEBUG: 404', trim((string) $response->getBody()));
    }
}
