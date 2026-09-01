<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Storage;

use TwentytwoLabs\FeatureFlagBundle\Model\Feature;
use TwentytwoLabs\FeatureFlagBundle\Model\FeatureInterface;

final class ArrayStorage implements StorageInterface
{
    /** @var FeatureInterface[] */
    private array $features;

    /**
     * @param array<string, array<string, mixed>> $options
     */
    public function __construct(array $options = [])
    {
        $this->features = array_map(function (array $feature) {
            return new Feature(
                key: $feature['name'],
                enabled: $feature['enabled'],
                expression: $feature['expression'] ?? null,
                description: $feature['description'] ?? null
            );
        }, $options['features']);
    }

    public function all(): array
    {
        return $this->features;
    }

    public function get(string $key): ?FeatureInterface
    {
        foreach ($this->features as $feature) {
            if ($feature->getKey() === $key) {
                return $feature;
            }
        }

        return null;
    }
}
