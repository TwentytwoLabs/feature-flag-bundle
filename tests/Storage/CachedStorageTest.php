<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Tests\Storage;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use TwentytwoLabs\FeatureFlagBundle\Model\FeatureInterface;
use TwentytwoLabs\FeatureFlagBundle\Storage\CachedStorage;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\FeatureFlagBundle\Storage\StorageInterface;

#[AllowMockObjectsWithoutExpectations]
final class CachedStorageTest extends TestCase
{
    private StorageInterface|MockObject $store;
    private CacheItemPoolInterface|MockObject $cache;

    protected function setUp(): void
    {
        $this->store = $this->createMock(StorageInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
    }
    public function testAllReturnEmptyArrayIfNoFeatureDefined(): void
    {
        $this->store->expects($this->once())->method('all')->willReturn([]);

        $storage = $this->getStorage();
        $this->assertSame([], $storage->all());
    }

    public function testAllReturnDefinedFeatures(): void
    {
        $features = [
            $this->createMock(FeatureInterface::class),
            $this->createMock(FeatureInterface::class),
            $this->createMock(FeatureInterface::class),
        ];

        $this->store->expects($this->once())->method('all')->willReturn($features);

        $storage = $this->getStorage();
        $this->assertSame($features, $storage->all());
    }

    public function testShouldReturnFeatureWhenItIsNotExist(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(false);
        $cacheItem->expects($this->never())->method('get');
        $cacheItem->expects($this->once())->method('set')->with(null)->willReturnSelf();
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600)->willReturnSelf();

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->with('unknown-feature')
            ->willReturn($cacheItem)
        ;
        $this->cache->expects($this->once())->method('save')->with($cacheItem)->willReturn(true);

        $this->store->expects($this->once())->method('get')->with('unknown-feature')->willReturn(null);

        $storage = $this->getStorage();
        $this->assertNull($storage->get('unknown-feature'));
    }

    public function testShouldReturnFeatureWithOutCache(): void
    {
        $feature = $this->createMock(FeatureInterface::class);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(false);
        $cacheItem->expects($this->never())->method('get');
        $cacheItem->expects($this->once())->method('set')->with($feature)->willReturnSelf();
        $cacheItem->expects($this->once())->method('expiresAfter')->with(3600)->willReturnSelf();

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willReturn($cacheItem)
        ;
        $this->cache->expects($this->once())->method('save')->with($cacheItem)->willReturn(true);

        $this->store->expects($this->once())->method('get')->with('foo')->willReturn($feature);

        $storage = $this->getStorage();
        $this->assertSame($feature, $storage->get('foo'));
    }

    public function testShouldReturnFeatureWithCache(): void
    {
        $feature = $this->createMock(FeatureInterface::class);

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(true);
        $cacheItem->expects($this->once())->method('get')->willReturn($feature);
        $cacheItem->expects($this->never())->method('set');
        $cacheItem->expects($this->never())->method('expiresAfter');

        $this->cache
            ->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willReturn($cacheItem)
        ;
        $this->cache->expects($this->never())->method('save');

        $this->store->expects($this->never())->method('get');

        $storage = $this->getStorage();
        $this->assertSame($feature, $storage->get('foo'));
    }

    private function getStorage(): CachedStorage
    {
        return new CachedStorage($this->store, $this->cache, ['expiresAfter' => 3600]);
    }
}
