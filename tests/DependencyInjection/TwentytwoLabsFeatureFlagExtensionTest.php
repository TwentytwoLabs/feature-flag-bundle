<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\DependencyInjection\Reference;
use TwentytwoLabs\FeatureFlagBundle\Command\ListFeatureCommand;
use TwentytwoLabs\FeatureFlagBundle\DataCollector\FeatureCollector;
use TwentytwoLabs\FeatureFlagBundle\DependencyInjection\TwentytwoLabsFeatureFlagExtension;
use TwentytwoLabs\FeatureFlagBundle\Storage\CachedStorage;
use TwentytwoLabs\FeatureFlagBundle\Twig\Extension\FeatureFlagExtension;

#[AllowMockObjectsWithoutExpectations]
final class TwentytwoLabsFeatureFlagExtensionTest extends AbstractExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setParameter('kernel.debug', true);
    }

    protected function getContainerExtensions(): array
    {
        return [new TwentytwoLabsFeatureFlagExtension()];
    }

    public function testShouldLoadWithDefaultConfig(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(FeatureCollector::class);
        $this->assertContainerBuilderHasService(FeatureFlagExtension::class);
        $this->assertContainerBuilderHasService(ListFeatureCommand::class);
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.factory.array');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.manager');
        $this->assertContainerBuilderNotHasService('twenty-two-labs.feature-flags.manager.manager_foo');
        $this->assertContainerBuilderNotHasService('twenty-two-labs.feature-flags.storage.manager_foo');
        $this->assertContainerBuilderNotHasService('twenty-two-labs.feature-flags.manager.manager_bar');
        $this->assertContainerBuilderNotHasService('twenty-two-labs.feature-flags.storage.manager_bar');
    }

    public function testShouldLoadWithFullConfigWithOutCacheBecauseItIsNotEnable(): void
    {
        $expectedConfig = [
            'cache' => [],
            'managers' => [
                'manager_foo' => [
                    'factory' => 'twenty-two-labs.feature-flags.factory.array',
                    'options' => [
                        'features' => [
                            'my_feature_1' => null,
                            'my_feature_2' => true,
                            'my_feature_3' => ['enabled' => false],
                            'my_feature_4' => ['enabled' => true, 'description' => 'MyFeature4 description text'],
                        ],
                    ],
                ],
                'manager_bar' => [
                    'factory' => 'twenty-two-labs.feature-flags.factory.array',
                    'options' => [
                        'features' => [
                            'my_feature_5' => false,
                            'my_feature_6' => [],
                            'my_feature_7' => false,
                        ],
                    ],
                ],
            ],
        ];
        $this->load($expectedConfig);

        $this->assertContainerBuilderHasService(FeatureCollector::class);
        $this->assertContainerBuilderHasService(FeatureFlagExtension::class);
        $this->assertContainerBuilderHasService(ListFeatureCommand::class);
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.factory.array');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.manager');
        $this->assertContainerBuilderNotHasService('twenty-two-labs.feature-flags.storage.cached_manager_foo');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.manager.manager_foo');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.storage.manager_foo');

        $managerFoo = $this->container->findDefinition('twenty-two-labs.feature-flags.manager.manager_foo');
        $this->assertFalse($managerFoo->isPublic());
        $managerFooArgs = $managerFoo->getArguments();
        $this->assertCount(3, $managerFooArgs);
        $this->assertSame('manager_foo', $managerFooArgs[0]);
        $this->assertInstanceOf(Reference::class, $managerFooArgs[1]);
        $this->assertSame('twenty-two-labs.feature-flags.storage.manager_foo', (string) $managerFooArgs[1]);
        $this->assertInstanceOf(Reference::class, $managerFooArgs[2]);
        $this->assertSame('twenty-two-labs.feature-flags.checker.expression_language', (string) $managerFooArgs[2]);

        $storageFoo = $this->container->findDefinition('twenty-two-labs.feature-flags.storage.manager_foo');
        $this->assertFalse($storageFoo->isPublic());
        $factory = $storageFoo->getFactory();
        $this->assertIsArray($factory);
        $this->assertCount(2, $factory);
        $this->assertArrayHasKey(0, $factory);
        $this->assertInstanceOf(Reference::class, $factory[0]);
        $this->assertSame('twenty-two-labs.feature-flags.factory.array', (string) $factory[0]);
        $this->assertArrayHasKey(1, $factory);
        $this->assertSame('createStorage', $factory[1]);

        $this->assertContainerBuilderNotHasService('twenty-two-labs.feature-flags.storage.cached_manager_bar');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.manager.manager_bar');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.storage.manager_bar');

        $managerBar = $this->container->findDefinition('twenty-two-labs.feature-flags.manager.manager_bar');
        $this->assertFalse($managerBar->isPublic());
        $managerBarArgs = $managerBar->getArguments();
        $this->assertCount(3, $managerBarArgs);
        $this->assertSame('manager_bar', $managerBarArgs[0]);
        $this->assertInstanceOf(Reference::class, $managerBarArgs[1]);
        $this->assertSame('twenty-two-labs.feature-flags.storage.manager_bar', (string) $managerBarArgs[1]);
        $this->assertInstanceOf(Reference::class, $managerBarArgs[2]);
        $this->assertSame('twenty-two-labs.feature-flags.checker.expression_language', (string) $managerBarArgs[2]);

        $storageBar = $this->container->findDefinition('twenty-two-labs.feature-flags.storage.manager_bar');
        $this->assertFalse($storageBar->isPublic());
        $factory = $storageBar->getFactory();
        $this->assertIsArray($factory);
        $this->assertCount(2, $factory);
        $this->assertArrayHasKey(0, $factory);
        $this->assertInstanceOf(Reference::class, $factory[0]);
        $this->assertSame('twenty-two-labs.feature-flags.factory.array', (string) $factory[0]);
        $this->assertArrayHasKey(1, $factory);
        $this->assertSame('createStorage', $factory[1]);
    }

    public function testShouldLoadWithFullConfigWithOutCacheBecauseProviderIsNotSet(): void
    {
        $expectedConfig = [
            'cache' => [
                'enabled' => true,
            ],
            'managers' => [
                'manager_foo' => [
                    'factory' => 'twenty-two-labs.feature-flags.factory.array',
                    'options' => [
                        'features' => [
                            'my_feature_1' => null,
                            'my_feature_2' => true,
                            'my_feature_3' => ['enabled' => false],
                            'my_feature_4' => ['enabled' => true, 'description' => 'MyFeature4 description text'],
                        ],
                    ],
                ],
                'manager_bar' => [
                    'factory' => 'twenty-two-labs.feature-flags.factory.array',
                    'options' => [
                        'features' => [
                            'my_feature_5' => false,
                            'my_feature_6' => [],
                            'my_feature_7' => false,
                        ],
                    ],
                ],
            ],
        ];
        $this->load($expectedConfig);

        $this->assertContainerBuilderHasService(FeatureCollector::class);
        $this->assertContainerBuilderHasService(FeatureFlagExtension::class);
        $this->assertContainerBuilderHasService(ListFeatureCommand::class);
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.factory.array');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.manager');
        $this->assertContainerBuilderNotHasService('twenty-two-labs.feature-flags.storage.cached_manager_foo');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.manager.manager_foo');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.storage.manager_foo');

        $managerFoo = $this->container->findDefinition('twenty-two-labs.feature-flags.manager.manager_foo');
        $this->assertFalse($managerFoo->isPublic());
        $managerFooArgs = $managerFoo->getArguments();
        $this->assertCount(3, $managerFooArgs);
        $this->assertSame('manager_foo', $managerFooArgs[0]);
        $this->assertInstanceOf(Reference::class, $managerFooArgs[1]);
        $this->assertSame('twenty-two-labs.feature-flags.storage.manager_foo', (string) $managerFooArgs[1]);
        $this->assertInstanceOf(Reference::class, $managerFooArgs[2]);
        $this->assertSame('twenty-two-labs.feature-flags.checker.expression_language', (string) $managerFooArgs[2]);

        $storageFoo = $this->container->findDefinition('twenty-two-labs.feature-flags.storage.manager_foo');
        $this->assertFalse($storageFoo->isPublic());
        $factory = $storageFoo->getFactory();
        $this->assertIsArray($factory);
        $this->assertCount(2, $factory);
        $this->assertArrayHasKey(0, $factory);
        $this->assertInstanceOf(Reference::class, $factory[0]);
        $this->assertSame('twenty-two-labs.feature-flags.factory.array', (string) $factory[0]);
        $this->assertArrayHasKey(1, $factory);
        $this->assertSame('createStorage', $factory[1]);

        $this->assertContainerBuilderNotHasService('twenty-two-labs.feature-flags.storage.cached_manager_bar');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.manager.manager_bar');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.storage.manager_bar');

        $managerBar = $this->container->findDefinition('twenty-two-labs.feature-flags.manager.manager_bar');
        $this->assertFalse($managerBar->isPublic());
        $managerBarArgs = $managerBar->getArguments();
        $this->assertCount(3, $managerBarArgs);
        $this->assertSame('manager_bar', $managerBarArgs[0]);
        $this->assertInstanceOf(Reference::class, $managerBarArgs[1]);
        $this->assertSame('twenty-two-labs.feature-flags.storage.manager_bar', (string) $managerBarArgs[1]);
        $this->assertInstanceOf(Reference::class, $managerBarArgs[2]);
        $this->assertSame('twenty-two-labs.feature-flags.checker.expression_language', (string) $managerBarArgs[2]);

        $storageBar = $this->container->findDefinition('twenty-two-labs.feature-flags.storage.manager_bar');
        $this->assertFalse($storageBar->isPublic());
        $factory = $storageBar->getFactory();
        $this->assertIsArray($factory);
        $this->assertCount(2, $factory);
        $this->assertArrayHasKey(0, $factory);
        $this->assertInstanceOf(Reference::class, $factory[0]);
        $this->assertSame('twenty-two-labs.feature-flags.factory.array', (string) $factory[0]);
        $this->assertArrayHasKey(1, $factory);
        $this->assertSame('createStorage', $factory[1]);
    }

    public function testShouldLoadWithFullConfigWithService(): void
    {
        $expectedConfig = [
            'managers' => [
                'manager_foo' => [
                    'factory' => 'twenty-two-labs.feature-flags.factory.api-service',
                    'options' => [
                        'client' => '@api_service.default',
                        'collection' => ['operationId' => 'getFeatureCollection'],
                        'item' => [
                            'operationId' => 'getFeatureItem',
                            'mapper' => ['identifier' => 'key'],
                        ],
                    ],
                ],
                'manager_bar' => [
                    'factory' => 'twenty-two-labs.feature-flags.factory.api-service',
                    'options' => [
                        'client' => '@api_service.bar',
                        'collection' => ['operationId' => 'getFeatureCollection'],
                        'item' => [
                            'operationId' => 'getFeatureItem',
                            'mapper' => ['identifier' => 'key'],
                        ],
                    ],
                ],
            ],
        ];
        $this->load($expectedConfig);

        $this->assertContainerBuilderHasService(FeatureCollector::class);
        $this->assertContainerBuilderHasService(FeatureFlagExtension::class);
        $this->assertContainerBuilderHasService(ListFeatureCommand::class);
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.factory.array');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.manager');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.manager.manager_foo');
        $this->assertContainerBuilderNotHasService('twenty-two-labs.feature-flags.storage.cached_manager_foo');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.storage.manager_foo');

        $managerFoo = $this->container->findDefinition('twenty-two-labs.feature-flags.manager.manager_foo');
        $this->assertFalse($managerFoo->isPublic());
        $managerFooArgs = $managerFoo->getArguments();
        $this->assertCount(3, $managerFooArgs);
        $this->assertSame('manager_foo', $managerFooArgs[0]);
        $this->assertInstanceOf(Reference::class, $managerFooArgs[1]);
        $this->assertSame('twenty-two-labs.feature-flags.storage.manager_foo', (string) $managerFooArgs[1]);
        $this->assertInstanceOf(Reference::class, $managerFooArgs[2]);
        $this->assertSame('twenty-two-labs.feature-flags.checker.expression_language', (string) $managerFooArgs[2]);

        $storageFoo = $this->container->findDefinition('twenty-two-labs.feature-flags.storage.manager_foo');
        $this->assertFalse($storageFoo->isPublic());
        $factory = $storageFoo->getFactory();
        $this->assertIsArray($factory);
        $this->assertCount(2, $factory);
        $this->assertArrayHasKey(0, $factory);
        $this->assertInstanceOf(Reference::class, $factory[0]);
        $this->assertSame('twenty-two-labs.feature-flags.factory.api-service', (string) $factory[0]);
        $this->assertArrayHasKey(1, $factory);
        $this->assertSame('createStorage', $factory[1]);
        $args = $storageFoo->getArguments();
        $this->assertCount(2, $args);
        $this->assertArrayHasKey(0, $args);
        $this->assertArrayHasKey(1, $args);
        $this->assertSame('manager_foo', $args[0]);
        $this->assertIsArray($args[1]);
        $this->assertCount(3, $args[1]);
        $this->assertArrayHasKey('client', $args[1]);
        $this->assertArrayHasKey('collection', $args[1]);
        $this->assertArrayHasKey('item', $args[1]);
        $this->assertInstanceOf(Reference::class, $args[1]['client']);
        $this->assertSame('api_service.default', (string) $args[1]['client']);
        $this->assertSame(['operationId' => 'getFeatureCollection'], $args[1]['collection']);
        $this->assertSame(['operationId' => 'getFeatureItem', 'mapper' => ['identifier' => 'key']], $args[1]['item']);

        $this->assertContainerBuilderNotHasService('twenty-two-labs.feature-flags.storage.cached_manager_bar');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.manager.manager_bar');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.storage.manager_bar');

        $managerBar = $this->container->findDefinition('twenty-two-labs.feature-flags.manager.manager_bar');
        $this->assertFalse($managerBar->isPublic());
        $managerBarArgs = $managerBar->getArguments();
        $this->assertCount(3, $managerBarArgs);
        $this->assertSame('manager_bar', $managerBarArgs[0]);
        $this->assertInstanceOf(Reference::class, $managerBarArgs[1]);
        $this->assertSame('twenty-two-labs.feature-flags.storage.manager_bar', (string) $managerBarArgs[1]);
        $this->assertInstanceOf(Reference::class, $managerBarArgs[2]);
        $this->assertSame('twenty-two-labs.feature-flags.checker.expression_language', (string) $managerBarArgs[2]);

        $storageBar = $this->container->findDefinition('twenty-two-labs.feature-flags.storage.manager_bar');
        $this->assertFalse($storageBar->isPublic());
        $factory = $storageBar->getFactory();
        $this->assertIsArray($factory);
        $this->assertCount(2, $factory);
        $this->assertArrayHasKey(0, $factory);
        $this->assertInstanceOf(Reference::class, $factory[0]);
        $this->assertSame('twenty-two-labs.feature-flags.factory.api-service', (string) $factory[0]);
        $this->assertArrayHasKey(1, $factory);
        $this->assertSame('createStorage', $factory[1]);
    }

    public function testShouldLoadWithFullConfigWithCache(): void
    {
        $expectedConfig = [
            'cache' => [
                'enabled' => true,
                'provider' => 'cache_feature_flags',
            ],
            'managers' => [
                'manager_foo' => [
                    'factory' => 'twenty-two-labs.feature-flags.factory.array',
                    'options' => [
                        'features' => [
                            'my_feature_1' => null,
                            'my_feature_2' => true,
                            'my_feature_3' => ['enabled' => false],
                            'my_feature_4' => ['enabled' => true, 'description' => 'MyFeature4 description text'],
                        ],
                    ],
                ],
                'manager_bar' => [
                    'factory' => 'twenty-two-labs.feature-flags.factory.array',
                    'options' => [
                        'features' => [
                            'my_feature_5' => false,
                            'my_feature_6' => [],
                            'my_feature_7' => false,
                        ],
                    ],
                ],
            ],
        ];
        $this->load($expectedConfig);

        $this->assertContainerBuilderHasService(FeatureCollector::class);
        $this->assertContainerBuilderHasService(FeatureFlagExtension::class);
        $this->assertContainerBuilderHasService(ListFeatureCommand::class);
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.factory.array');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.manager');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.manager.manager_foo');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.storage.cached_manager_foo');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.storage.manager_foo');

        $managerFooCached = $this->container->findDefinition('twenty-two-labs.feature-flags.storage.cached_manager_foo');
        $this->assertFalse($managerFooCached->isPublic());
        $this->assertSame(CachedStorage::class, $managerFooCached->getClass());
        $managerFooCachedArgs = $managerFooCached->getArguments();
        $this->assertCount(3, $managerFooCachedArgs);
        $this->assertInstanceOf(Reference::class, $managerFooCachedArgs[0]);
        $this->assertSame('twenty-two-labs.feature-flags.storage.manager_foo', (string) $managerFooCachedArgs[0]);
        $this->assertInstanceOf(Reference::class, $managerFooCachedArgs[1]);
        $this->assertSame('cache_feature_flags', (string) $managerFooCachedArgs[1]);
        $this->assertSame(['expiresAfter' => 3600], $managerFooCachedArgs[2]);

        $managerFoo = $this->container->findDefinition('twenty-two-labs.feature-flags.manager.manager_foo');
        $this->assertFalse($managerFoo->isPublic());
        $managerFooArgs = $managerFoo->getArguments();
        $this->assertCount(3, $managerFooArgs);
        $this->assertSame('manager_foo', $managerFooArgs[0]);
        $this->assertInstanceOf(Reference::class, $managerFooArgs[1]);
        $this->assertSame('twenty-two-labs.feature-flags.storage.cached_manager_foo', (string) $managerFooArgs[1]);
        $this->assertInstanceOf(Reference::class, $managerFooArgs[2]);
        $this->assertSame('twenty-two-labs.feature-flags.checker.expression_language', (string) $managerFooArgs[2]);

        $storageFoo = $this->container->findDefinition('twenty-two-labs.feature-flags.storage.manager_foo');
        $this->assertFalse($storageFoo->isPublic());
        $factory = $storageFoo->getFactory();
        $this->assertIsArray($factory);
        $this->assertCount(2, $factory);
        $this->assertArrayHasKey(0, $factory);
        $this->assertInstanceOf(Reference::class, $factory[0]);
        $this->assertSame('twenty-two-labs.feature-flags.factory.array', (string) $factory[0]);
        $this->assertArrayHasKey(1, $factory);
        $this->assertSame('createStorage', $factory[1]);

        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.storage.cached_manager_bar');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.manager.manager_bar');
        $this->assertContainerBuilderHasService('twenty-two-labs.feature-flags.storage.manager_bar');

        $managerBarCached = $this->container->findDefinition('twenty-two-labs.feature-flags.storage.cached_manager_bar');
        $this->assertFalse($managerBarCached->isPublic());
        $this->assertSame(CachedStorage::class, $managerBarCached->getClass());
        $managerBarCachedArgs = $managerBarCached->getArguments();
        $this->assertCount(3, $managerBarCachedArgs);
        $this->assertInstanceOf(Reference::class, $managerBarCachedArgs[0]);
        $this->assertSame('twenty-two-labs.feature-flags.storage.manager_bar', (string) $managerBarCachedArgs[0]);
        $this->assertInstanceOf(Reference::class, $managerBarCachedArgs[1]);
        $this->assertSame('cache_feature_flags', (string) $managerBarCachedArgs[1]);
        $this->assertSame(['expiresAfter' => 3600], $managerBarCachedArgs[2]);

        $managerBar = $this->container->findDefinition('twenty-two-labs.feature-flags.manager.manager_bar');
        $this->assertFalse($managerBar->isPublic());
        $managerBarArgs = $managerBar->getArguments();
        $this->assertCount(3, $managerBarArgs);
        $this->assertSame('manager_bar', $managerBarArgs[0]);
        $this->assertInstanceOf(Reference::class, $managerBarArgs[1]);
        $this->assertSame('twenty-two-labs.feature-flags.storage.cached_manager_bar', (string) $managerBarArgs[1]);
        $this->assertInstanceOf(Reference::class, $managerBarArgs[2]);
        $this->assertSame('twenty-two-labs.feature-flags.checker.expression_language', (string) $managerBarArgs[2]);

        $storageBar = $this->container->findDefinition('twenty-two-labs.feature-flags.storage.manager_bar');
        $this->assertFalse($storageBar->isPublic());
        $factory = $storageBar->getFactory();
        $this->assertIsArray($factory);
        $this->assertCount(2, $factory);
        $this->assertArrayHasKey(0, $factory);
        $this->assertInstanceOf(Reference::class, $factory[0]);
        $this->assertSame('twenty-two-labs.feature-flags.factory.array', (string) $factory[0]);
        $this->assertArrayHasKey(1, $factory);
        $this->assertSame('createStorage', $factory[1]);
    }
}
