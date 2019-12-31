<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\TestAsset;

use Laminas\ServiceManager\ServiceLocatorInterface;
use RuntimeException;

class ServiceManager implements ServiceLocatorInterface
{
    protected $services = array();

    public function get($name)
    {
        if (!$this->has($name)) {
            throw new RuntimeException(sprintf(
                'No service by name of "%s" found',
                $name
            ));
        }

        return $this->services[$name];
    }

    public function has($name)
    {
        return isset($this->services[$name]);
    }

    public function set($name, $service)
    {
        $this->services[$name] = $service;
    }
}
