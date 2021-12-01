<?php

declare(strict_types=1);

namespace Mobicms\System\View;

use Mezzio\Template\ArrayParametersTrait;
use Mezzio\Template\TemplateRendererInterface;
use Mobicms\Render\Engine;
use Psr\Container\ContainerInterface;

/**
 * @psalm-suppress MissingConstructor
 */
class FakeTemplateRenderer implements TemplateRendererInterface
{
    use ArrayParametersTrait;

    private Engine $engine;

    public function __invoke(ContainerInterface $container): TemplateRendererInterface
    {
        /** @var Engine $engine */
        $engine = $container->get(Engine::class);
        $this->engine = $engine;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $name, $params = []): string
    {
        return $this->engine->render($name, $this->normalizeParams($params));
    }

    // @codeCoverageIgnoreStart
    public function addPath(string $path, ?string $namespace = null): void
    {
    }

    public function getPaths(): array
    {
        return [];
    }

    public function addDefaultParam(?string $templateName, string $param, $value): void
    {
    }
    // @codeCoverageIgnoreEnd
}
