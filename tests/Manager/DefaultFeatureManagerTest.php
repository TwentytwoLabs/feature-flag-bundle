<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Tests\Manager;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use TwentytwoLabs\FeatureFlagBundle\Checker\ExpressionLanguageChecker;
use TwentytwoLabs\FeatureFlagBundle\Manager\DefaultFeatureManager;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\FeatureFlagBundle\Model\FeatureInterface;
use TwentytwoLabs\FeatureFlagBundle\Storage\StorageInterface;

#[AllowMockObjectsWithoutExpectations]
final class DefaultFeatureManagerTest extends TestCase
{
    private StorageInterface|MockObject $storage;
    private ExpressionLanguageChecker|MockObject $expressionLanguageChecker;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);
        $this->expressionLanguageChecker = $this->createMock(ExpressionLanguageChecker::class);
    }

    public function testShouldValidateName(): void
    {
        $manager = $this->getManager();
        $this->assertSame('foo', $manager->getName());
    }

    public function testShouldRecoverAllFunctionality(): void
    {
        $this->storage->expects($this->once())->method('all')->willReturn([]);
        $this->expressionLanguageChecker->expects($this->never())->method('isGranted');

        $manager = $this->getManager();
        $this->assertSame([], $manager->all());
    }

    public function testShouldRetrieveStatusOfFeatureWhenFeatureNotExist(): void
    {
        $this->storage->expects($this->exactly(2))->method('get')->with('feature_1')->willReturn(null);
        $this->expressionLanguageChecker->expects($this->never())->method('isGranted');

        $manager = $this->getManager();
        $this->assertFalse($manager->isEnabled('feature_1'));
        $this->assertTrue($manager->isDisabled('feature_1'));
    }

    public function testShouldRetrieveStatusOfFeatureWhenFeatureIsEnableWithOutExpression(): void
    {
        $feature = $this->createMock(FeatureInterface::class);
        $feature->expects($this->exactly(2))->method('isEnabled')->willReturn(true);
        $feature->expects($this->exactly(2))->method('getExpression')->willReturn(null);

        $this->storage->expects($this->exactly(2))->method('get')->with('feature_1')->willReturn($feature);
        $this->expressionLanguageChecker->expects($this->never())->method('isGranted');

        $manager = $this->getManager();
        $this->assertTrue($manager->isEnabled('feature_1'));
        $this->assertFalse($manager->isDisabled('feature_1'));
    }

    public function testShouldRetrieveStatusOfFeatureWhenFeatureIsEnableWithExpression(): void
    {
        $feature = $this->createMock(FeatureInterface::class);
        $feature->expects($this->exactly(2))->method('isEnabled')->willReturn(true);
        $feature->expects($this->exactly(4))->method('getExpression')->willReturn('is_granted(\'ROLE_ADMIN\')');

        $this->storage->expects($this->exactly(2))->method('get')->with('feature_1')->willReturn($feature);
        $this->expressionLanguageChecker
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->with('is_granted(\'ROLE_ADMIN\')')
            ->willReturn(true)
        ;

        $manager = $this->getManager();
        $this->assertTrue($manager->isEnabled('feature_1'));
        $this->assertFalse($manager->isDisabled('feature_1'));
    }

    public function testShouldRetrieveStatusOfFeatureWhenFeatureIsDisableWithOutExpression(): void
    {
        $feature = $this->createMock(FeatureInterface::class);
        $feature->expects($this->exactly(2))->method('isEnabled')->willReturn(false);
        $feature->expects($this->never())->method('getExpression')->willReturn(null);

        $this->storage->expects($this->exactly(2))->method('get')->with('feature_1')->willReturn($feature);
        $this->expressionLanguageChecker->expects($this->never())->method('isGranted');

        $manager = $this->getManager();
        $this->assertFalse($manager->isEnabled('feature_1'));
        $this->assertTrue($manager->isDisabled('feature_1'));
    }

    private function getManager(): DefaultFeatureManager
    {
        return new DefaultFeatureManager('foo', $this->storage, $this->expressionLanguageChecker);
    }
}
