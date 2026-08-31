<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TwentytwoLabs\FeatureFlagBundle\Manager\ChainedFeatureManager;
use TwentytwoLabs\FeatureFlagBundle\Model\FeatureInterface;

#[AsCommand(name: 'twentytwo-labs:feature-flag:list', description: 'List all features with their state')]
final class ListFeatureCommand extends Command
{
    private ChainedFeatureManager $manager;

    public function __construct(ChainedFeatureManager $manager, ?string $name = null)
    {
        parent::__construct($name);
        $this->manager = $manager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        foreach ($this->manager->getManagers() as $manager) {
            $symfonyStyle->section($manager->getName());

            $features = $manager->all();
            if (empty($features)) {
                $symfonyStyle->warning('No feature declared.');
                continue;
            }

            $table = $symfonyStyle->createTable();
            $table->setHeaders(['Name', 'Enabled', 'Expression', 'Description']);
            /** @var FeatureInterface $feature */
            foreach ($features as $feature) {
                $table->addRow([
                    $feature->getKey(),
                    $feature->isEnabled() ? 'Yes' : 'No',
                    $feature->getExpression(),
                    $feature->getDescription(),
                ]);
            }
            $table->render();

            $symfonyStyle->writeln('');
        }

        return Command::SUCCESS;
    }
}
