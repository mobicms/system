<?php

declare(strict_types=1);

namespace Mobicms\System;

use Mezzio\Template\TemplateRendererInterface;
use Mobicms\Render\Engine;
use Mobicms\System\Db\PdoFactory;
use Mobicms\System\View;
use PDO;

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
                PDO::class => PdoFactory::class,
                Engine::class                    => View\EngineFactory::class,
                TemplateRendererInterface::class => View\FakeTemplateRenderer::class,
            ],
        ];
    }
}
