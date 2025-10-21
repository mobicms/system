<?php

declare(strict_types=1);

namespace MobicmsTest\View;

use Mobicms\Contract\ConfigInterface;
use Mobicms\Render\Engine;
use Mobicms\System\View\EngineFactory;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class EngineFactoryTest extends TestCase
{
    private ContainerInterface $container;

    public function setUp(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->method('has')
            ->with('templates')
            ->willReturn(true);
        $config
            ->method('get')
            ->with(['templates', 'paths'])
            ->willReturn(
                [
                    'test' => ['path1'],
                    'p2'   => ['path2'],
                ]
            );

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with(ConfigInterface::class)
            ->willReturn($config);
        $this->container = $container;
    }

    public function testFactoryReturnsInstanceOfEngine(): Engine
    {
        $engine = (new EngineFactory())($this->container);
        /** @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertInstanceOf(Engine::class, $engine);
        return $engine;
    }

    #[Depends('testFactoryReturnsInstanceOfEngine')]
    public function testEngineHasConfiguredFolder(Engine $engine): void
    {
        $result = $engine->getPath('test');
        self::assertEquals('path1', $result[0]);
    }
}
