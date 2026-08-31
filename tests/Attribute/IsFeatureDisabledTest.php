<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Tests\Attribute;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use TwentytwoLabs\FeatureFlagBundle\Attribute\IsFeatureDisabled;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class IsFeatureDisabledTest extends TestCase
{
    public function testShouldValidateToArrayResult(): void
    {
        $feature = new IsFeatureDisabled('foo');

        $this->assertSame('foo', $feature->getName());
        $this->assertSame(['feature' => 'foo', 'enabled' => false], $feature->toArray());
    }
}
