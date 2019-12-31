<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools;

use Interop\Container\ContainerInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\ServiceManager\AbstractFactoryInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use stdClass;

class TableGatewayAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Can this factory create the requested table gateway?
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        if (7 > strlen($requestedName)
            || substr($requestedName, -6) !== '\Table'
        ) {
            return false;
        }

        if (! $container->has('config')) {
            return false;
        }

        $config = $container->get('config');
        if (! isset($config['api-tools']['db-connected'])) {
            return false;
        }

        $config      = $config['api-tools']['db-connected'];
        $gatewayName = substr($requestedName, 0, strlen($requestedName) - 6);
        if (! isset($config[$gatewayName])
            || ! is_array($config[$gatewayName])
            || ! $this->isValidConfig($config[$gatewayName], $container)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Can this factory create the requested table gateway? (v2)
     *
     * Provided for backwards compatibility; proxies to canCreate().
     *
     * @param ServiceLocatorInterface $container
     * @param string $name
     * @param string $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
        return $this->canCreate($container, $requestedName);
    }

    /**
     * Create and return the requested table gateway instance.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return TableGateway
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $gatewayName       = substr($requestedName, 0, strlen($requestedName) - 6);
        $config            = $container->get('config');
        $dbConnectedConfig = $config['api-tools']['db-connected'][$gatewayName];

        $restConfig = $dbConnectedConfig;
        if (isset($config['api-tools-rest'])
            && isset($dbConnectedConfig['controller_service_name'])
            && isset($config['api-tools-rest'][$dbConnectedConfig['controller_service_name']])
        ) {
            $restConfig = $config['api-tools-rest'][$dbConnectedConfig['controller_service_name']];
        }

        $table    = $dbConnectedConfig['table_name'];
        $adapter  = $this->getAdapterFromConfig($dbConnectedConfig, $container);
        $hydrator = $this->getHydratorFromConfig($dbConnectedConfig, $container);
        $entity   = $this->getEntityFromConfig($restConfig, $requestedName);

        $resultSetPrototype = new HydratingResultSet($hydrator, new $entity());
        return new TableGateway($table, $adapter, null, $resultSetPrototype);
    }

    /**
     * Create and return the requested table gateway instance (v2).
     *
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @param string $name
     * @param string $requestedName
     * @return TableGateway
     */
    public function createServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
        return $this($container, $requestedName);
    }

    /**
     * Is the configuration valid?
     *
     * @param array $config
     * @param ContainerInterface $container
     * @return bool
     */
    protected function isValidConfig(array $config, ContainerInterface $container)
    {
        if (! isset($config['table_name'])) {
            return false;
        }

        if (isset($config['adapter_name'])
            && $container->has($config['adapter_name'])
        ) {
            return true;
        }

        if (! isset($config['adapter_name'])
            && ($container->has(AdapterInterface::class) || $container->has(Adapter::class))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve a laminas-db adapter via provided configuration.
     *
     * If the configuration defines an `adapter_name` and a matching service
     * is discovered, that will be returned.
     *
     * If the Adapter service is present, that will be returned (laminas-mvc v2).
     *
     * Otherwise, the AdapterInterface service is returned.
     *
     * @param array $config
     * @param ContainerInterface $container
     * @return AdapterInterface
     */
    protected function getAdapterFromConfig(array $config, ContainerInterface $container)
    {
        if (isset($config['adapter_name'])
            && $container->has($config['adapter_name'])
        ) {
            return $container->get($config['adapter_name']);
        }

        if ($container->has(Adapter::class)) {
            // v2 usage
            return $container->get(Adapter::class);
        }

        // v3 usage
        return $container->get(AdapterInterface::class);
    }

    /**
     * Retrieve the configured hydrator.
     *
     * If configuration defines a `hydrator_name`, that service will be
     * retrieved from the HydratorManager; otherwise ArraySerializable
     * will be retrieved.
     *
     * @param array $config
     * @param ContainerInterface $container
     * @return \Laminas\Hydrator\HydratorInterface
     */
    protected function getHydratorFromConfig(array $config, ContainerInterface $container)
    {
        $hydratorName = isset($config['hydrator_name']) ? $config['hydrator_name'] : 'ArraySerializable';
        $hydrators    = $container->get('HydratorManager');
        return $hydrators->get($hydratorName);
    }

    /**
     * Retrieve the configured entity.
     *
     * If configuration defines an `entity_class`, and the class exists, that
     * value is returned; if no configuration is provided, stdClass is returned.
     *
     * @param array $config
     * @param string $requestedName
     * @return string Class name of entity
     * @throws ServiceNotCreatedException if the entity class cannot be autoloaded.
     */
    protected function getEntityFromConfig(array $config, $requestedName)
    {
        $entity = isset($config['entity_class']) ? $config['entity_class'] : stdClass::class;
        if (! class_exists($entity)) {
            throw new ServiceNotCreatedException(sprintf(
                'Unable to create instance for service "%s"; entity class "%s" cannot be found',
                $requestedName,
                $entity
            ));
        }
        return $entity;
    }
}
