<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Tests\Command;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TwentytwoLabs\FeatureFlagBundle\Command\ListFeatureCommand;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\FeatureFlagBundle\Manager\ChainedFeatureManager;
use TwentytwoLabs\FeatureFlagBundle\Manager\FeatureManagerInterface;
use TwentytwoLabs\FeatureFlagBundle\Model\Feature;

#[AllowMockObjectsWithoutExpectations]
final class ListFeatureCommandTest extends TestCase
{
    /**
     * @param array<string, mixed> $features
     */
    #[DataProvider('featuresProvider')]
    public function testConfiguredFeaturesAreDisplayedInAskedFormat(array $features, string $expectedOutput): void
    {
        $commandTester = $this->createCommandTester($features);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertSame($expectedOutput, trim(preg_replace('!\s+!', ' ', $commandTester->getDisplay())));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function featuresProvider(): array
    {
        return [
            'empty-features' => [
                [
                    'foo' => [
                        'options' => [
                            'features' => [],
                        ]
                    ],
                    'bar' => [
                        'options' => [
                            'features' => [],
                        ]
                    ],
                ],
                "foo --- [WARNING] No feature declared. bar --- [WARNING] No feature declared.",
            ],
            'with-features' => [
                [
                    'bar' => [
                        'options' => [
                            'features' => [],
                        ],
                    ],
                    'foo' => [
                        'options' => [
                            'features' => [
                                'feature1' => [
                                    'name' => 'feature1',
                                    'enabled' => true,
                                    'description' => 'Feature 1 description',
                                ],
                                'feature2' => [
                                    'name' => 'feature2',
                                    'enabled' => false,
                                    'description' => 'Feature 2 description',
                                ],
                            ],
                        ],
                    ],
                ],
                "bar --- [WARNING] No feature declared. foo --- ---------- --------- ------------ ----------------------- Name Enabled Expression Description ---------- --------- ------------ ----------------------- feature1 Yes Feature 1 description feature2 No Feature 2 description ---------- --------- ------------ -----------------------",
            ],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $managersDefinition
     * @throws Exception
     */
    private function createCommandTester(array $managersDefinition = []): CommandTester
    {
        $managers = [];
        foreach ($managersDefinition as $managerName => $featuresDefinition) {
            $manager = $this->createMock(FeatureManagerInterface::class);
            $manager->expects($this->once())->method('getName')->willReturn($managerName);
            $manager->expects($this->once())->method('all')->willReturn(array_map(function (array $feature) {
                return new Feature(
                    key: $feature['name'],
                    enabled: $feature['enabled'],
                    expression: $feature['expression'] ?? null,
                    description: $feature['description'] ?? null
                );
            }, $featuresDefinition['options']['features']));

            $managers[] = $manager;
        }

        $chainedFeatureManager = $this->createMock(ChainedFeatureManager::class);
        $chainedFeatureManager->expects($this->once())->method('getManagers')->willReturn($managers);

        $command = new ListFeatureCommand($chainedFeatureManager);

        return new CommandTester($command);
    }
}
