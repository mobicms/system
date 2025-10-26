<?php

declare(strict_types=1);

namespace Mobicms\System\ErrorHandler;

use HttpSoft\ErrorHandler\ErrorResponseGeneratorInterface;
use Mobicms\Render\Engine;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @psalm-api
 */
final class NotFoundHandler implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private Engine $template;
    private string $view;
    private bool $debug;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        Engine $template,
        string $view,
        bool $debug = false
    ) {
        $this->responseFactory = $responseFactory;
        $this->template = $template;
        $this->view = $view;
        $this->debug = $debug;
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(ErrorResponseGeneratorInterface::STATUS_NOT_FOUND);

        $response->getBody()->write(
            $this->template->render($this->view, [
                'debug'   => $this->debug,
                'request' => $request,
            ])
        );

        return $response;
    }
}
