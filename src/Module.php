<?php

declare(strict_types=1);

namespace Laminas\ApiTools;

use Laminas\ApiTools\ApiProblem\Listener\RenderErrorListener;
use Laminas\ApiTools\Hal\View\HalJsonModel;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;

class Module
{
    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Listen to application bootstrap event.
     *
     * - Attaches UnauthenticatedListener to authentication.post event.
     * - Attaches UnauthorizedListener to authorization.post event.
     * - Attaches module render listener to render event.
     *
     * @return void
     */
    public function onBootstrap(MvcEvent $e)
    {
        $app      = $e->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();

        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            $services->get(MvcAuth\UnauthenticatedListener::class),
            100
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            $services->get(MvcAuth\UnauthorizedListener::class),
            100
        );
        $events->attach(MvcEvent::EVENT_RENDER, [$this, 'onRender'], 400);
    }

    /**
     * Attach the ApiProblem render.error listener if a JSON response is detected.
     *
     * @return void
     */
    public function onRender(MvcEvent $e)
    {
        $result = $e->getResult();
        if (
            ! $result instanceof HalJsonModel
            && ! $result instanceof JsonModel
        ) {
            return;
        }

        $app      = $e->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();
        $services->get(RenderErrorListener::class)->attach($events);
    }
}
