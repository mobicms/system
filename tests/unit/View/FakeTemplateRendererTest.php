<?php

declare(strict_types=1);

namespace MobicmsTest\System\View;

use Mezzio\Template\TemplateRendererInterface;
use Mobicms\Render\Engine;
use Mobicms\System\View\FakeTemplateRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class FakeTemplateRendererTest extends TestCase
{
    public function testInvoke(): void
    {
        $engine = $this->createMock(Engine::class);
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with(Engine::class)
            ->willReturn($engine);
        $renderer = (new FakeTemplateRenderer())($container);
        $this->assertInstanceOf(TemplateRendererInterface::class, $renderer);
    }

    public function testRender(): void
    {
        $engine = $this->createMock(Engine::class);
        $engine
            ->expects($this->once())
            ->method('render')
            ->with('test::test', []);
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with(Engine::class)
            ->willReturn($engine);
        $renderer = (new FakeTemplateRenderer())($container);
        $renderer->render('test::test');
    }
}
