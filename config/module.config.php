<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

return array(
    'asset_manager' => array(
        'resolver_configs' => array(
            'paths' => array(
                __DIR__ . '/../asset',
            ),
        ),
    ),
    'router' => array(
        'routes' => array(
            'api-tools' => array(
                'type'  => 'Laminas\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/api-tools',
                ),
                'may_terminate' => false,
            ),
        ),
    ),
    'service_manager' => array(
        'invokables' => array(
            'Laminas\ApiTools\MvcAuth\UnauthenticatedListener' => 'Laminas\ApiTools\MvcAuth\UnauthenticatedListener',
            'Laminas\ApiTools\MvcAuth\UnauthorizedListener' => 'Laminas\ApiTools\MvcAuth\UnauthorizedListener',
        ),
        'abstract_factories' => array(
            'Laminas\Db\Adapter\AdapterAbstractServiceFactory', // so that db-connected works "out-of-the-box"
            'Laminas\ApiTools\DbConnectedResourceAbstractFactory',
            'Laminas\ApiTools\TableGatewayAbstractFactory',
        ),
    ),
    'api-tools' => array(
        'db-connected' => array(
        /**
         * This is sample configuration for a DB-connected service.
         * Each such service requires an adapter, a hydrator, an entity, and a
         * collection.
         *
         * The TableGateway will be called "YourDBConnectedResource\Table" should
         * you wish to retrieve it manually later.
            'YourDBConnectedResource' => array(
                'table_service'    => 'Optional; if present, this service will be used as the table gateway',
                'resource_class'   => 'Optional; if present, this class will be used as the db-connected resource',
                'table_name'       => 'Name of DB table to use',
                'identifier_name'  => 'Optional; identifier field in table; defaults to table_name_id or id',
                'adapter_name'     => 'Service Name for DB adapter to use',
                'hydrator_name'    => 'Service Name for Hydrator to use',
                'entity_class'     => 'Name of entity class to which to hydrate',
                'collection_class' => 'Name of collection class which iterates entities; should be a Paginator extension',
            ),
         */
        ),
    ),
);
