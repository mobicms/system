<?php

declare(strict_types=1);

namespace MobicmsTest\System\View;

use Mezzio\Helper\ServerUrlHelper;
use Mezzio\Helper\UrlHelper;
use Mobicms\Render\Engine;
use Mobicms\System\View\EngineFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class EngineFactoryTest extends TestCase
{
    private ContainerInterface $container;

    public function setUp(): void
    {
        $serverUrlHelper = $this->createMock(ServerUrlHelper::class);
        $urlHelper = $this->createMock(UrlHelper::class);
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->withConsecutive(
                [UrlHelper::class],
                [ServerUrlHelper::class],
                ['config']
            )
            ->willReturn(
                $urlHelper,
                $serverUrlHelper,
                ['templates' => ['paths' => ['test' => [__DIR__],],],]
            );
        $this->container = $container;
    }

    public function testFactoryReturnsInstanceOfEngine(): Engine
    {
        $engine = (new EngineFactory())($this->container);
        $this->assertInstanceOf(Engine::class, $engine);
        return $engine;
    }

    /**
     * @depends testFactoryReturnsInstanceOfEngine
     */
    public function testUrlExtensionIsRegisteredByDefault(Engine $engine): void
    {
        $this->assertTrue($engine->doesFunctionExist('url'));
        $this->assertTrue($engine->doesFunctionExist('serverurl'));
    }

    /**
     * @depends testFactoryReturnsInstanceOfEngine
     */
    public function testEngineHasConfiguredFolder(Engine $engine): void
    {
        $result = $engine->getPath('test');
        $this->assertEquals(__DIR__, $result[0]);
    }
}
