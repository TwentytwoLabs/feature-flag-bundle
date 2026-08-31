<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Tests\Factory;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\FeatureFlagBundle\Exception\ConfigurationException;
use TwentytwoLabs\FeatureFlagBundle\Factory\ArrayStorageFactory;
use TwentytwoLabs\FeatureFlagBundle\Model\FeatureInterface;
use TwentytwoLabs\FeatureFlagBundle\Storage\ArrayStorage;

#[AllowMockObjectsWithoutExpectations]
final class ArrayStorageFactoryTest extends TestCase
{
    public function testShouldThrowExceptionBecauseFeaturesIsNotDefined(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Error while configure storage foo. Verify your configuration at "twenty-two-labs.feature-flags.storages.foo.options". The required option "features" is missing.');

        $factory = $this->getFactory();
        $factory->createStorage('foo');
    }
    public function testShouldThrowExceptionBecauseFeaturesIsNotAnArray(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Error while configure storage foo. Verify your configuration at "twenty-two-labs.feature-flags.storages.foo.options". The option "features" with value "Lorem Ipsum" is expected to be of type "array", but is of type "string"');

        $options = [
            'features' => 'Lorem Ipsum',
        ];

        $factory = $this->getFactory();
        $factory->createStorage('foo', $options);
    }

    public function testShouldCreateStorage(): void
    {
        $options = [
            'features' => [
                'my_feature_1' => [],
                'my_feature_2' => null,
                'my_feature_3' => false,
                'my_feature_4' => [
                    'enabled' => true,
                    'description' => 'Lorem Ipsum',
                    'expression' => 'is_granted(\'ROLE_ADMIN\')',
                ],
                'my_feature_5' => ['enabled' => true, 'expression' => 'is_granted(\'ROLE_ADMIN\')'],
            ],
        ];

        $factory = $this->getFactory();
        $storage = $factory->createStorage('foo', $options);
        $this->assertInstanceOf(ArrayStorage::class, $storage);
        $features = $storage->all();
        $this->assertCount(5, $features);

        $this->assertArrayHasKey('my_feature_1', $features);
        $this->assertArrayHasKey('my_feature_2', $features);
        $this->assertArrayHasKey('my_feature_3', $features);
        $this->assertArrayHasKey('my_feature_4', $features);
        $this->assertArrayHasKey('my_feature_5', $features);

        $this->assertInstanceOf(FeatureInterface::class, $features['my_feature_1']);
        $this->assertSame('my_feature_1', $features['my_feature_1']->getKey());
        $this->assertTrue($features['my_feature_1']->isEnabled());
        $this->assertNull($features['my_feature_1']->getExpression());
        $this->assertNull($features['my_feature_1']->getDescription());
        $this->assertSame(
            [
                'key' => 'my_feature_1',
                'enabled' => true,
                'description' => null,
            ],
            $features['my_feature_1']->toArray()
        );

        $this->assertInstanceOf(FeatureInterface::class, $features['my_feature_2']);
        $this->assertSame('my_feature_2', $features['my_feature_2']->getKey());
        $this->assertTrue($features['my_feature_2']->isEnabled());
        $this->assertNull($features['my_feature_2']->getExpression());
        $this->assertNull($features['my_feature_2']->getDescription());
        $this->assertSame(
            [
                'key' => 'my_feature_2',
                'enabled' => true,
                'description' => null,
            ],
            $features['my_feature_2']->toArray()
        );

        $this->assertInstanceOf(FeatureInterface::class, $features['my_feature_3']);
        $this->assertSame('my_feature_3', $features['my_feature_3']->getKey());
        $this->assertFalse($features['my_feature_3']->isEnabled());
        $this->assertNull($features['my_feature_3']->getExpression());
        $this->assertNull($features['my_feature_3']->getDescription());
        $this->assertSame(
            [
                'key' => 'my_feature_3',
                'enabled' => false,
                'description' => null,
            ],
            $features['my_feature_3']->toArray()
        );

        $this->assertInstanceOf(FeatureInterface::class, $features['my_feature_4']);
        $this->assertSame('my_feature_4', $features['my_feature_4']->getKey());
        $this->assertTrue($features['my_feature_4']->isEnabled());
        $this->assertSame('is_granted(\'ROLE_ADMIN\')', $features['my_feature_4']->getExpression());
        $this->assertSame('Lorem Ipsum', $features['my_feature_4']->getDescription());
        $this->assertSame(
            [
                'key' => 'my_feature_4',
                'enabled' => true,
                'description' => 'Lorem Ipsum',
            ],
            $features['my_feature_4']->toArray()
        );

        $this->assertInstanceOf(FeatureInterface::class, $features['my_feature_5']);
        $this->assertSame('my_feature_5', $features['my_feature_5']->getKey());
        $this->assertTrue($features['my_feature_5']->isEnabled());
        $this->assertSame('is_granted(\'ROLE_ADMIN\')', $features['my_feature_5']->getExpression());
        $this->assertNull($features['my_feature_5']->getDescription());
        $this->assertSame(
            [
                'key' => 'my_feature_5',
                'enabled' => true,
                'description' => null,
            ],
            $features['my_feature_5']->toArray()
        );
    }

    private function getFactory(): ArrayStorageFactory
    {
        return new ArrayStorageFactory();
    }
}
