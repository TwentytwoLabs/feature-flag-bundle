<?php

namespace TwentytwoLabs\FeatureFlagBundle\Storage;

use Psr\Cache\CacheItemPoolInterface;
use TwentytwoLabs\FeatureFlagBundle\Model\FeatureInterface;

final class CachedStorage implements StorageInterface
{
    private StorageInterface $store;
    private CacheItemPoolInterface $cache;
    /** @var array<string, mixed> */
    private array $options;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(StorageInterface $store, CacheItemPoolInterface $cache, array $options)
    {
        $this->store = $store;
        $this->cache = $cache;
        $this->options = $options;
    }

    public function all(): array
    {
        return $this->store->all();
    }

    public function get(string $key): ?FeatureInterface
    {
        $item = $this->cache->getItem($key);

        if ($item->isHit()) {
            $feature = $item->get();
        } else {
            $feature = $this->store->get($key);

            $item
                ->set($feature)
                ->expiresAfter($this->options['expiresAfter'])
            ;

            $this->cache->save($item);
        }

        return $feature;
    }
}
