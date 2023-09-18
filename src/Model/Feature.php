<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Model;

final class Feature implements FeatureInterface
{
    private string $key;
    private bool $enabled;
    private ?string $description;

    public function __construct(string $key, bool $enabled, ?string $description)
    {
        $this->key = $key;
        $this->enabled = $enabled;
        $this->description = $description;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'enabled' => $this->enabled,
            'description' => $this->description,
        ];
    }
}
