<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Tests\Storage;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use TwentytwoLabs\FeatureFlagBundle\Model\FeatureInterface;
use TwentytwoLabs\FeatureFlagBundle\Storage\ArrayStorage;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class ArrayStorageTest extends TestCase
{
    public function testAllReturnEmptyArrayIfNoFeatureDefined(): void
    {
        $storage = $this->getStorage();
        $this->assertSame([], $storage->all());
    }

    public function testAllReturnDefinedFeatures(): void
    {
        $features = [
            'foo' => ['name' => 'foo', 'enabled' => false],
            'bar' => ['name' => 'bar', 'enabled' => true, 'description' => 'Feature bar description'],
        ];

        $storage = $this->getStorage($features);
        $features = $storage->all();

        $this->assertCount(2, $features);

        $this->assertArrayHasKey('foo', $features);
        $this->assertInstanceOf(FeatureInterface::class, $features['foo']);
        $this->assertSame('foo', $features['foo']->getKey());
        $this->assertFalse($features['foo']->isEnabled());
        $this->assertNull($features['foo']->getDescription());
        $this->assertSame(
            ['key' => 'foo', 'enabled' => false, 'description' => null],
            $features['foo']->toArray()
        );

        $this->assertArrayHasKey('bar', $features);
        $this->assertInstanceOf(FeatureInterface::class, $features['bar']);
        $this->assertSame('bar', $features['bar']->getKey());
        $this->assertTrue($features['bar']->isEnabled());
        $this->assertSame('Feature bar description', $features['bar']->getDescription());
        $this->assertSame(
            ['key' => 'bar', 'enabled' => true, 'description' => 'Feature bar description'],
            $features['bar']->toArray()
        );
    }

    public function testShouldReturnFeatureWhenItIsNotExist(): void
    {
        $storage = $this->getStorage();
        $this->assertNull($storage->get('unknown-feature'));
    }

    public function testShouldReturnFeature(): void
    {
        $features = [
            'feature_1' => ['name' => 'feature_1', 'enabled' => true, 'description' => 'Feature feature_1 description'],
        ];

        $storage = $this->getStorage($features);
        $feature = $storage->get('feature_1');
        $this->assertInstanceOf(FeatureInterface::class, $feature);
        $this->assertSame('feature_1', $feature->getKey());
        $this->assertTrue($feature->isEnabled());
        $this->assertSame('Feature feature_1 description', $feature->getDescription());
        $this->assertSame(
            ['key' => 'feature_1', 'enabled' => true, 'description' => 'Feature feature_1 description'],
            $feature->toArray()
        );
    }

    /**
     * @param array<string, array<string, mixed>> $features
     */
    private function getStorage(array $features = []): ArrayStorage
    {
        return new ArrayStorage(['features' => $features]);
    }
}
