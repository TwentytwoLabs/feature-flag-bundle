<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use TwentytwoLabs\FeatureFlagBundle\Attribute\IsFeatureDisabled;
use TwentytwoLabs\FeatureFlagBundle\Attribute\IsFeatureEnabled;

class ControllerListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();
        if (is_object($controller) && method_exists($controller, '__invoke')) {
            $controller = [$controller, '__invoke'];
        }

        $className = $controller[0]::class;
        $class = new \ReflectionClass($className);
        $method = $class->getMethod($controller[1]);

        $features = [];
        foreach ($this->resolveFeatures($class, $method) as $key => $feature) {
            if (isset($features[$key])) {
                throw new \UnexpectedValueException(sprintf('Feature "%s" is defined more than once in %s::%s', $key, $className, $controller[1]));
            }

            $features[$key] = $feature;
        }

        $request = $event->getRequest();
        $request->attributes->set('_features', array_merge($request->attributes->get('_features', []), $features));
    }

    /**
     * @param \ReflectionClass<object> $class
     *
     * @return iterable<string, array<string, mixed>>
     */
    private function resolveFeatures(\ReflectionClass $class, \ReflectionMethod $method): iterable
    {
        $attributes = [
            ...$class->getAttributes(IsFeatureEnabled::class),
            ...$class->getAttributes(IsFeatureDisabled::class),
            ...$method->getAttributes(IsFeatureEnabled::class),
            ...$method->getAttributes(IsFeatureDisabled::class),
        ];

        foreach ($attributes as $attribute) {
            /** @var IsFeatureEnabled|IsFeatureDisabled $feature */
            $feature = $attribute->newInstance();

            yield $feature->getName() => $feature->toArray();
        }
    }
}
