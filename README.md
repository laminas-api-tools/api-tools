Laminas API Tools
============

[![Build Status](https://travis-ci.org/laminas-api-tools/api-tools.png)](https://travis-ci.org/laminas-api-tools/api-tools)

Introduction
------------

Meta- Laminas module combining features from:

- api-tools-api-problem
- api-tools-content-negotiation
- api-tools-hal
- api-tools-rest
- api-tools-rpc
- api-tools-versioning

in order to provide a cohesive solution for exposing web-based APIs.

Also features database-connected REST resources.

Requirements
------------
  
Please see the [composer.json](composer.json) file.

Installation
------------

Run the following `composer` command:

```console
$ composer require "laminas-api-tools/api-tools:~1.0-dev"
```

Alternately, manually add the following to your `composer.json`, in the `require` section:

```javascript
"require": {
    "laminas-api-tools/api-tools": "~1.0-dev"
}
```

And then run `composer update` to ensure the module is installed.

Finally, add the module name to your project's `config/application.config.php` under the `modules`
key:

```php
return array(
    /* ... */
    'modules' => array(
        /* ... */
        'Laminas\ApiTools',
    ),
    /* ... */
);
```

Configuration
=============

### User Configuration

The top-level configuration key for user configuration of this module is `api-tools-api-problem`.

#### `db-connected`

`db-connected` is an array of resources that can be built via the
[TableGatewayAbstractFactory](#apitoolstablegatewayabstractfactory) and the
[DbConnectedResourceAbstractFactory](#apitoolsdbconnectedresourceabstractfactory) when required
to fulfill the use case of database table-driven resource use cases. The following example
enumerates all of the required and optional configuration necessary to enable this.

Example:

```php
'db-connected' => array(
    /**
     * This is sample configuration for a DB-connected service.
     * Each such service requires an adapter, a hydrator, an entity, and a
     * collection.
     *
     * The TableGateway will be called "YourDBConnectedResource\Table" should
     * you wish to retrieve it manually later.
     */
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
),
```

### System Configuration

The following configuration is required to ensure the proper functioning of this module in Laminas
Framework 2 applications, and is provided by the module:

```php
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
```

Laminas Events
==========

### Listeners

#### `Laminas\ApiTools\MvcAuth\UnauthenticatedListener`

This listener is attached to `MvcAuthEvent::EVENT_AUTHENTICATION_POST` at priority `100`.  The
primary purpose fo this listener is to override the `api-tools-mvc-auth` _unauthenticated_ listener in
order to be able to respond with an API-Problem response (vs. a standard HTTP response) on
authentication failure.

#### `Laminas\ApiTools\MvcAuth\UnauthorizedListener`

This listener is attached to `MvcAuthEvent::EVENT_AUTHORIZATION_POST` at priority `100`.  The
primary purpose of this listener is to override the `api-tools-mvc-auth` _unauthorized_ listener in order
to be able to respond with an API-Problem response (vs a standard HTTP response) on authorization
failure.

#### `Laminas\ApiTools\Module`

This listener is attached to `MvcEvent::EVENT_RENDER` at priority `400`.  Its purpose is to
conditionally attach `Laminas\ApiTools\ApiProblem\RenderErrorListener` when an `MvcEvent`'s result is a
`HalJsonModel` or `JsonModel`, ensuring `api-tools-api-problem` can render a response in situations where
a rendering error occurs.

Laminas Services
============

### Factories

#### `Laminas\ApiTools\DbConnectedResourceAbstractFactory`

This factory uses the requested name in addition to the `api-tools.db-connected` configuration
in order to produce `Laminas\ApiTools\DbConnectedResource` based resources.

#### `Laminas\ApiTools\TableGatewayAbstractFactory`

This factory uses the requested name in addition to the `api-tools.db-connected` configuration
in order to produce correctly configured `Laminas\Db\TableGateway\TableGateway` instances.  These
instances of `TableGateway`s are configured to use the proper `HydratingResultSet` and produce
the configured entities with each row returned when iterated.

### Models

#### `Laminas\ApiTools\DbConnectedResource`

This instance serves as the base class for database connected REST resource classes.  This
implementation is an extension of `Laminas\ApiTools\Rest\AbstractResourceListener` and can be routed to by
Laminas API Tools as a RESTful resource.
