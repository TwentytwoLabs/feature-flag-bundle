<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Tests\Twig\Extension;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\FeatureFlagBundle\Manager\ChainedFeatureManager;
use TwentytwoLabs\FeatureFlagBundle\Twig\Extension\FeatureFlagExtension;
use Twig\TwigFunction;

#[AllowMockObjectsWithoutExpectations]
final class FeatureFlagExtensionTest extends TestCase
{
    private ChainedFeatureManager|MockObject $manager;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(ChainedFeatureManager::class);
    }

    public function testShouldValidateName(): void
    {
        $extension = $this->getExtension();

        $this->assertSame('feature_flag_extension', $extension->getName());
    }

    public function testShouldValidateTwigFunctions(): void
    {
        $extension = $this->getExtension();
        $functions = $extension->getFunctions();

        $this->assertCount(2, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('isFeatureEnabled', $functions[0]->getName());
        $this->assertInstanceOf(TwigFunction::class, $functions[1]);
        $this->assertSame('isFeatureDisabled', $functions[1]->getName());
    }

    #[DataProvider('getFeatures')]
    public function testIsFeatureEnabledReturnFeatureState(string $feature, bool $isEnabled): void
    {
        $this->manager->expects($this->once())->method('isEnabled')->with($feature)->willReturn($isEnabled);
        $this->manager->expects($this->never())->method('isDisabled');

        $extension = $this->getExtension();
        $twigFunctionCallable = $this->getTwigFunctionCallable($extension, 'isFeatureEnabled');

        $this->assertSame($isEnabled, $twigFunctionCallable($feature));
    }

    #[DataProvider('getFeatures')]
    public function testIsDisabledMethod(string $feature, bool $isEnabled): void
    {
        $this->manager->expects($this->never())->method('isEnabled');
        $this->manager->expects($this->once())->method('isDisabled')->with($feature)->willReturn(!$isEnabled);

        $extension = $this->getExtension();
        $twigFunctionCallable = $this->getTwigFunctionCallable($extension, 'isFeatureDisabled');

        $this->assertNotSame($isEnabled, $twigFunctionCallable($feature));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function getFeatures(): array
    {
        return [
            'existing feature' => ['foo', true],
            'non existing feature' => ['bar', false]
        ];
    }

    private function getTwigFunctionCallable(FeatureFlagExtension $extension, string $functionName): callable
    {
        foreach ($extension->getFunctions() as $twigFunction) {
            if ($twigFunction->getName() === $functionName) {
                return $twigFunction->getCallable();
            }
        }

        $this->fail(sprintf('No \'%s\' Twig function.', $functionName));
    }

    private function getExtension(): FeatureFlagExtension
    {
        return new FeatureFlagExtension($this->manager);
    }
}
