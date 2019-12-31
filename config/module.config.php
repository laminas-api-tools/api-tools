<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools;

use Laminas\Db\Adapter\AdapterAbstractServiceFactory as DbAdapterAbstractServiceFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'asset_manager' => [
        'resolver_configs' => [
            'paths' => [
                __DIR__ . '/../asset',
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'api-tools' => [
                'type' => 'literal',
                'options' => [
                    'route' => '/api-tools',
                ],
                'may_terminate' => false,
            ],
        ],
    ],
    'service_manager' => [
        // Legacy Zend Framework aliases
        'aliases' => [
            \ZF\Apigility\MvcAuth\UnauthenticatedListener::class => MvcAuth\UnauthenticatedListener::class,
            \ZF\Apigility\MvcAuth\UnauthorizedListener::class => MvcAuth\UnauthorizedListener::class,
        ],
        'factories' => [
            MvcAuth\UnauthenticatedListener::class => InvokableFactory::class,
            MvcAuth\UnauthorizedListener::class => InvokableFactory::class,
        ],
        'abstract_factories' => [
            DbAdapterAbstractServiceFactory::class, // so that db-connected works "out-of-the-box"
            DbConnectedResourceAbstractFactory::class,
            TableGatewayAbstractFactory::class,
        ],
    ],
    'api-tools' => [
        'db-connected' => [
        // @codingStandardsIgnoreStart
        /*
         * This is sample configuration for a DB-connected service.
         * Each such service requires an adapter, a hydrator, an entity, and a
         * collection.
         *
         * The TableGateway will be called "YourDBConnectedResource\Table" should
         * you wish to retrieve it manually later.
            'YourDBConnectedResource' => [
                'table_service'    => 'Optional; if present, this service will be used as the table gateway',
                'resource_class'   => 'Optional; if present, this class will be used as the db-connected resource',
                'table_name'       => 'Name of DB table to use',
                'identifier_name'  => 'Optional; identifier field in table; defaults to table_name_id or id',
                'adapter_name'     => 'Service Name for DB adapter to use',
                'hydrator_name'    => 'Service Name for Hydrator to use',
                'entity_class'     => 'Name of entity class to which to hydrate',
                'collection_class' => 'Name of collection class which iterates entities; should be a Paginator extension',
            ],
         */
        // @codingStandardsIgnoreEnd
        ],
    ],
];
