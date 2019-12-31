<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools;

use Laminas\ApiTools\Hal\View\HalJsonModel;
use Laminas\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/',
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap($e)
    {
        $app      = $e->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();

        $events->attach(
            MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            $services->get('Laminas\ApiTools\MvcAuth\UnauthenticatedListener'),
            100
        );
        $events->attach(
            MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            $services->get('Laminas\ApiTools\MvcAuth\UnauthorizedListener'),
            100
        );
        $events->attach(MvcEvent::EVENT_RENDER, array($this, 'onRender'), 400);
    }

    /**
     * Attach the ApiProblem render.error listener if a JSON response is detected
     *
     * @param mixed $e
     */
    public function onRender($e)
    {
        $result = $e->getResult();
        if (! $result instanceof HalJsonModel
            && ! $result instanceof JsonModel
        ) {
            return;
        }

        $app      = $e->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();
        $events->attach($services->get('Laminas\ApiTools\ApiProblem\RenderErrorListener'));
    }
}
