<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools;

use Laminas\ApiTools\Autoloader;
use PHPUnit_Framework_TestCase as TestCase;

class AutoloaderTest extends TestCase
{
    public function classesToAutoload()
    {
        return array(
            'Foo_Bar'         => array('LaminasTest\ApiTools\TestAsset\Foo_Bar'),
            'Foo_Bar\Baz_Bat' => array('LaminasTest\ApiTools\TestAsset\Foo_Bar\Baz_Bat'),
        );
    }

    /**
     * @dataProvider classesToAutoload
     */
    public function testAutoloaderDoesNotTransformUnderscoresToDirectorySeparators($className)
    {
        $autoloader = new Autoloader(array(
            'namespaces' => array(
                'LaminasTest\ApiTools\TestAsset' => __DIR__ . '/TestAsset',
            ),
        ));
        $result = $autoloader->autoload($className);
        $this->assertFalse(false === $result);
        $this->assertTrue(class_exists($className, false));
    }
}
