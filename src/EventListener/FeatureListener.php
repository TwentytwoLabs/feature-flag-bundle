<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use TwentytwoLabs\FeatureFlagBundle\Manager\ChainedFeatureManager;

class FeatureListener implements EventSubscriberInterface
{
    private ChainedFeatureManager $manager;

    public function __construct(ChainedFeatureManager $manager)
    {
        $this->manager = $manager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        foreach ($request->attributes->get('_features', []) as $featureConfiguration) {
            $isEnabled = $this->manager->isEnabled($featureConfiguration['feature']);
            if ($isEnabled !== ($featureConfiguration['enabled'] ?? true)) {
                throw new GoneHttpException();
            }
        }
    }
}
