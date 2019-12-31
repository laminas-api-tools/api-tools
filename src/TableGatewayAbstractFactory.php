<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools;

use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\ServiceManager\AbstractFactoryInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceLocatorInterface;

class TableGatewayAbstractFactory implements AbstractFactoryInterface
{
    public function canCreateServiceWithName(ServiceLocatorInterface $services, $name, $requestedName)
    {
        if (7 > strlen($requestedName)
            || substr($requestedName, -6) !== '\Table'
        ) {
            return false;
        }

        if (!$services->has('Config')) {
            return false;
        }

        $config = $services->get('Config');
        if (!isset($config['api-tools'])
            || !isset($config['api-tools']['db-connected'])
        ) {
            return false;
        }

        $config      = $config['api-tools']['db-connected'];
        $gatewayName = substr($requestedName, 0, strlen($requestedName) - 6);
        if (!isset($config[$gatewayName])
            || !is_array($config[$gatewayName])
            || !$this->isValidConfig($config[$gatewayName], $services)
        ) {
            return false;
        }

        return true;
    }

    public function createServiceWithName(ServiceLocatorInterface $services, $name, $requestedName)
    {
        $gatewayName       = substr($requestedName, 0, strlen($requestedName) - 6);
        $config            = $services->get('Config');
        $dbConnectedConfig = $config['api-tools']['db-connected'][$gatewayName];

        $restConfig = $dbConnectedConfig;
        if (isset($config['api-tools-rest'])
            && isset($dbConnectedConfig['controller_service_name'])
            && isset($config['api-tools-rest'][$dbConnectedConfig['controller_service_name']])
        ) {
            $restConfig = $config['api-tools-rest'][$dbConnectedConfig['controller_service_name']];
        }

        $table      = $dbConnectedConfig['table_name'];
        $adapter    = $this->getAdapterFromConfig($dbConnectedConfig, $services);
        $hydrator   = $this->getHydratorFromConfig($dbConnectedConfig, $services);
        $entity     = $this->getEntityFromConfig($restConfig, $requestedName);

        $resultSetPrototype = new HydratingResultSet($hydrator, new $entity());
        return new TableGateway($table, $adapter, null, $resultSetPrototype);
    }

    protected function isValidConfig(array $config, ServiceLocatorInterface $services)
    {
        if (!isset($config['table_name'])) {
            return false;
        }

        if (isset($config['adapter_name'])
            && $services->has($config['adapter_name'])
        ) {
            return true;
        }

        if (!isset($config['adapter_name'])
            && $services->has('Laminas\Db\Adapter\Adapter')
        ) {
            return true;
        }

        return false;
    }

    protected function getAdapterFromConfig(array $config, ServiceLocatorInterface $services)
    {
        if (isset($config['adapter_name'])
            && $services->has($config['adapter_name'])
        ) {
            return $services->get($config['adapter_name']);
        }

        return $services->get('Laminas\Db\Adapter\Adapter');
    }

    protected function getHydratorFromConfig(array $config, ServiceLocatorInterface $services)
    {
        $hydratorName = isset($config['hydrator_name']) ? $config['hydrator_name'] : 'ArraySerializable';
        $hydrators    = $services->get('HydratorManager');
        return $hydrators->get($hydratorName);
    }

    protected function getEntityFromConfig(array $config, $requestedName)
    {
        $entity = isset($config['entity_class']) ? $config['entity_class'] : 'stdClass';
        if (!class_exists($entity)) {
            throw new ServiceNotCreatedException(sprintf(
                'Unable to create instance for service "%s"; entity class "%s" cannot be found',
                $requestedName,
                $entity
            ));
        }
        return $entity;
    }
}
