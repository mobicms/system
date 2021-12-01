<?php

declare(strict_types=1);

namespace Mobicms\System;

use Mezzio\Template\TemplateRendererInterface;
use Mobicms\Render\Engine;
use Mobicms\System\View;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            'factories' => [
                Engine::class                    => View\EngineFactory::class,
                TemplateRendererInterface::class => View\FakeTemplateRenderer::class,
            ],
        ];
    }
}
