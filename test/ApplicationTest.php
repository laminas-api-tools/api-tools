<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools;

use Exception;
use Laminas\ApiTools\Application;
use Laminas\EventManager\EventInterface;
use Laminas\EventManager\EventManager;
use Laminas\Http\PhpEnvironment;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use ReflectionProperty;

class ApplicationTest extends TestCase
{
    protected function setUp()
    {
        $events = new EventManager();

        $request  = $this->prophesize(PhpEnvironment\Request::class);
        $response = $this->prophesize(PhpEnvironment\Response::class);

        $this->services = $this->setUpServices(
            $this->prophesize(ServiceManager::class),
            $events,
            $request,
            $response
        );

        $this->app = $this->setUpMvcEvent(
            $this->createApplication(
                $this->services->reveal(),
                $events,
                $request->reveal(),
                $response->reveal()
            ),
            $request,
            $response
        );
    }

    /**
     * Create and return an Application instance.
     *
     * Checks to see which version of laminas-mvc is present, and uses that to
     * determine how to construct the instance.
     *
     * @param ServiceManager $services
     * @param EventManager $events
     * @param PhpEnvironment\Request $request
     * @param PhpEnvironment\Response $response
     * @return Application
     */
    public function createApplication($services, $events, $request, $response)
    {
        $r = new ReflectionMethod(Application::class, '__construct');
        if ($r->getNumberOfRequiredParameters() === 2) {
            // v2
            return new Application([], $services, $events, $request, $response);
        }

        // v3
        return new Application($services, $events, $request, $response);
    }

    /**
     * @param ObjectProphecy&ServiceManager $services
     * @param ObjectProphecy&PhpEnvironment\Request $request
     * @param ObjectProphecy&PhpEnvironment\Response $response
     * @return ObjectProphecy&ServiceManager
     */
    public function setUpServices($services, EventManager $events, $request, $response)
    {
        $services->get('config')->willReturn([]);
        $services->get('EventManager')->willReturn($events);
        $services->get('Request')->willReturn($request->reveal());
        $services->get('Response')->willReturn($response->reveal());
        return $services;
    }

    /**
     * @param ObjectProphecy&PhpEnvironment\Request $request
     * @param ObjectProphecy&PhpEnvironment\Response $response
     */
    public function setUpMvcEvent(Application $app, $request, $response): Application
    {
        $event = new MvcEvent();
        $event->setTarget($app);
        $event->setApplication($app)
            ->setRequest($request->reveal())
            ->setResponse($response->reveal());
        $r = new ReflectionProperty($app, 'event');
        $r->setAccessible(true);
        $r->setValue($app, $event);
        return $app;
    }

    public function testRouteListenerRaisingExceptionTriggersDispatchErrorAndSkipsDispatch()
    {
        $events   = $this->app->getEventManager();
        $response = $this->prophesize(PhpEnvironment\Response::class)->reveal();

        $events->attach('route', function ($e) {
            throw new Exception();
        });

        $events->attach('dispatch.error', function ($e) use ($response) {
            $this->assertNotEmpty($e->getError());
            return $response;
        });

        $events->attach('dispatch', function ($e) {
            $this->fail('dispatch event triggered when it should not be');
        });

        $events->attach('render', function ($e) {
            $this->fail('render event triggered when it should not be');
        });

        $finishTriggered = false;
        $events->attach('finish', function ($e) use (&$finishTriggered) {
            $finishTriggered = true;
        });

        $this->app->run();
        $this->assertTrue($finishTriggered);
        $this->assertSame($response, $this->app->getResponse());
    }

    public function testStopPropagationFromPrevEventShouldBeCleared()
    {
        $events   = $this->app->getEventManager();
        $response = $this->prophesize(PhpEnvironment\Response::class)->reveal();

        $events->attach('route', function (EventInterface $e) use ($response) {
            $e->stopPropagation(true);
            return $response;
        });

        $isStopPropagation = null;
        $events->attach('finish', function (EventInterface $e) use (&$isStopPropagation) {
            $isStopPropagation = $e->propagationIsStopped();
        });

        $this->app->run();

        $this->assertFalse($isStopPropagation);
    }
}
