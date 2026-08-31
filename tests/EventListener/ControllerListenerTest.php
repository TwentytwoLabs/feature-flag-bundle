<?php

declare(strict_types=1);

namespace TwentytwoLabs\FeatureFlagBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use TwentytwoLabs\FeatureFlagBundle\Tests\Fixtures\Controller\FooController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use TwentytwoLabs\FeatureFlagBundle\EventListener\ControllerListener;
use TwentytwoLabs\FeatureFlagBundle\Tests\Fixtures\Controller\DefaultController;

#[AllowMockObjectsWithoutExpectations]
final class ControllerListenerTest extends TestCase
{
    public function testShouldValidateEvent(): void
    {
        $this->assertSame(
            ['kernel.controller' => 'onKernelController'],
            ControllerListener::getSubscribedEvents()
        );
    }

    public function testShouldNotResolveFeatureBecauseFeatureNotExist(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageIsOrContains('Feature "foo" is defined more than once in TwentytwoLabs\FeatureFlagBundle\Tests\Fixtures\Controller\DefaultController::attributeFooError');

        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = new Request();
        $request->attributes = new ParameterBag();

        $listener = $this->getListener();
        $listener->onKernelController(
            new ControllerEvent(
                $kernel,
                [new DefaultController(), 'attributeFooError'],
                $request,
                null
            )
        );

        $this->assertSame([], $request->attributes->all());
    }

    public function testShouldResolveFeatureWithClass(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = new Request();
        $request->attributes = new ParameterBag(['_features' => ['bar' => ['feature' => 'bar', 'enabled' => true]]]);

        $listener = $this->getListener();
        $listener->onKernelController(
            new ControllerEvent(
                $kernel,
                new FooController(),
                $request,
                null
            )
        );

        $this->assertSame(
            [
                '_features' => [
                    'bar' => ['feature' => 'bar', 'enabled' => false],
                    'foo' => ['feature' => 'foo', 'enabled' => true],
                ],
            ],
            $request->attributes->all()
        );
    }

    public function testShouldResolveFeatureWithMethod(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = new Request();
        $request->attributes = new ParameterBag();

        $listener = $this->getListener();
        $listener->onKernelController(
            new ControllerEvent(
                $kernel,
                [new DefaultController(), 'attributeFooEnabled'],
                $request,
                null
            )
        );

        $this->assertSame(
            [
                '_features' => [
                    'foo' => ['feature' => 'foo', 'enabled' => true],
                ],
            ],
            $request->attributes->all()
        );
    }

    private function getListener(): ControllerListener
    {
        return new ControllerListener();
    }
}
