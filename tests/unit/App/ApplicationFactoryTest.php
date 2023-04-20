<?php

declare(strict_types=1);

namespace MobicmsTest\App;

use HttpSoft\Basis\Application;
use HttpSoft\Basis\Response\CustomResponseFactory;
use HttpSoft\Emitter\EmitterInterface;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Runner\MiddlewarePipeline;
use HttpSoft\Runner\MiddlewarePipelineInterface;
use HttpSoft\Runner\MiddlewareResolver;
use HttpSoft\Runner\MiddlewareResolverInterface;
use Mobicms\App\ApplicationFactory;
use Mobicms\Container\Container;
use Mobicms\Config\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;

class ApplicationFactoryTest extends TestCase
{
    /**
     * @dataProvider debugDataProvider
     */
    public function testCreate(bool $debug): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->method('get')
            ->with('debug')
            ->willReturn($debug);

        $app = (new ApplicationFactory())(
            new Container(
                [
                    'services'    => [
                        ConfigInterface::class => $config,
                    ],
                    'definitions' => [
                        EmitterInterface::class            => SapiEmitter::class,
                        MiddlewarePipelineInterface::class => MiddlewarePipeline::class,
                        MiddlewareResolverInterface::class => MiddlewareResolver::class,
                        ResponseFactoryInterface::class    => CustomResponseFactory::class,
                    ],
                ]
            )
        );
        $this->assertInstanceOf(Application::class, $app);
    }

    public static function debugDataProvider(): array
    {
        return [
            'debug-true'  => [true],
            'debug-false' => [false],
        ];
    }
}
