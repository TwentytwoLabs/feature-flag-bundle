<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Tests\DataCollector;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TwentytwoLabs\FeatureFlagBundle\DataCollector\FeatureCollector;
use TwentytwoLabs\FeatureFlagBundle\Manager\ChainedFeatureManager;
use TwentytwoLabs\FeatureFlagBundle\Manager\FeatureManagerInterface;
use TwentytwoLabs\FeatureFlagBundle\Model\FeatureInterface;

/**
 * @codingStandardsIgnoreFile
 *
 * @SuppressWarnings(PHPMD)
 */
class FeatureCollectorTest extends TestCase
{
    private FeatureManagerInterface $emptyManager;
    private FeatureManagerInterface $fooManager;
    private FeatureManagerInterface $barManager;

    protected function setUp(): void
    {
        $this->emptyManager = $this->createMock(FeatureManagerInterface::class);
        $this->fooManager = $this->createMock(FeatureManagerInterface::class);
        $this->barManager = $this->createMock(FeatureManagerInterface::class);
    }

    public function testShouldCollectData(): void
    {
        $feature1 = $this->createMock(FeatureInterface::class);
        $feature1
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['key' => 'feature1', 'enabled' => false, 'description' => ''])
        ;
        $feature1->expects($this->once())->method('isEnabled')->willReturn(false);

        $feature2 = $this->createMock(FeatureInterface::class);
        $feature2
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['key' => 'feature2', 'enabled' => true, 'description' => ''])
        ;
        $feature2->expects($this->once())->method('isEnabled')->willReturn(true);

        $feature3 = $this->createMock(FeatureInterface::class);
        $feature3
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['key' => 'feature3', 'enabled' => true, 'description' => ''])
        ;
        $feature3->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->emptyManager->expects($this->exactly(1))->method('getName')->willReturn('baz');
        $this->emptyManager->expects($this->once())->method('all')->willReturn([]);

        $this->fooManager->expects($this->exactly(3))->method('getName')->willReturn('foo');
        $this->fooManager->expects($this->once())->method('all')->willReturn([$feature1, $feature2]);

        $this->barManager->expects($this->exactly(2))->method('getName')->willReturn('bar');
        $this->barManager->expects($this->once())->method('all')->willReturn([$feature3]);

        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $collector = $this->getCollector();
        $this->assertSame('twenty-two-labs.feature-flags.collector', $collector->getName());
        $collector->reset();
        $collector->collect($request, $response);
        $this->assertSame(3, $collector->getFeatureCount());
        $this->assertSame(2, $collector->getActiveFeatureCount());
        $this->assertSame(
            [
                'baz' => [],
                'foo' => [
                    ['key' => 'feature1', 'enabled' => false, 'description' => ''],
                    ['key' => 'feature2', 'enabled' => true, 'description' => ''],
                ],
                'bar' => [
                    ['key' => 'feature3', 'enabled' => true, 'description' => ''],
                ],
            ],
            $collector->getFeatures()
        );
    }

    private function getCollector(): FeatureCollector
    {
        $chainedFeatureManager = $this->createMock(ChainedFeatureManager::class);
        $chainedFeatureManager
            ->expects($this->once())
            ->method('getManagers')
            ->willReturn([$this->emptyManager, $this->fooManager, $this->barManager])
        ;

        return new FeatureCollector($chainedFeatureManager);
    }
}
