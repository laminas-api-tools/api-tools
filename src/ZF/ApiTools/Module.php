<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools;

use Laminas\ApiTools\MvcAuth\MvcAuthEvent;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/../../../config/module.config.php';
    }

    public function onBootstrap($e)
    {
        $app      = $e->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();

        $events->attach(MvcAuthEvent::EVENT_AUTHENTICATION_POST, $services->get('Laminas\ApiTools\MvcAuth\UnauthenticatedListener'), 100);
        $events->attach(MvcAuthEvent::EVENT_AUTHORIZATION_POST, $services->get('Laminas\ApiTools\MvcAuth\UnauthorizedListener'), 100);
    }
}
