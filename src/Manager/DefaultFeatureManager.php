<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Manager;

use TwentytwoLabs\FeatureFlagBundle\Checker\ExpressionLanguageChecker;
use TwentytwoLabs\FeatureFlagBundle\Storage\StorageInterface;

final class DefaultFeatureManager implements FeatureManagerInterface
{
    private string $name;
    private StorageInterface $storage;
    private ExpressionLanguageChecker $expressionLanguageChecker;

    public function __construct(
        string $name,
        StorageInterface $storage,
        ExpressionLanguageChecker $expressionLanguageChecker,
    ) {
        $this->name = $name;
        $this->storage = $storage;
        $this->expressionLanguageChecker = $expressionLanguageChecker;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function all(): iterable
    {
        return $this->storage->all();
    }

    public function isEnabled(string $key): bool
    {
        $feature = $this->storage->get($key);
        if (null === $feature) {
            return false;
        }

        if ($feature->isEnabled()) {
            if (!empty($feature->getExpression())) {
                return $this->expressionLanguageChecker->isGranted($feature->getExpression());
            }

            return true;
        }

        return false;
    }

    public function isDisabled(string $key): bool
    {
        return false === $this->isEnabled($key);
    }
}
