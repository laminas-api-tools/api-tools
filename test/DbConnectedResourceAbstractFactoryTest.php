<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools;

use Laminas\ApiTools\DbConnectedResourceAbstractFactory;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

class DbConnectedResourceAbstractFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new TestAsset\ServiceManager();
        $this->factory  = new DbConnectedResourceAbstractFactory();
    }

    public function testWillNotCreateServiceIfConfigServiceMissing()
    {
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfApiToolsConfigMissing()
    {
        $this->services->set('Config', array());
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfApiToolsConfigIsNotAnArray()
    {
        $this->services->set('Config', array('api-tools' => 'invalid'));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfApiToolsConfigDoesNotHaveDbConnectedSegment()
    {
        $this->services->set('Config', array('api-tools' => array('foo' => 'bar')));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfDbConnectedSegmentDoesNotHaveRequestedName()
    {
        $this->services->set('Config', array('api-tools' => array(
            'db-connected' => array(
                'bar' => 'baz',
            ),
        )));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function invalidConfig()
    {
        return array(
            'invalid_table_service' => array(array('table_service' => 'non_existent')),
            'invalid_virtual_table' => array(array()),
        );
    }

    /**
     * @dataProvider invalidConfig
     */
    public function testWillNotCreateServiceIfDbConnectedSegmentIsInvalidConfiguration($configForDbConnected)
    {
        $config = array('api-tools' => array(
            'db-connected' => array(
                'Foo' => $configForDbConnected,
            ),
        ));
        $this->services->set('Config', $config);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function validConfig()
    {
        return array(
            'table_service' => array(array('table_service' => 'foobartable'), 'foobartable'),
            'virtual_table' => array(array(), 'Foo\Table'),
        );
    }

    /**
     * @dataProvider validConfig
     */
    public function testWillCreateServiceIfDbConnectedSegmentIsValid($configForDbConnected, $tableServiceName)
    {
        $config = array('api-tools' => array(
            'db-connected' => array(
                'Foo' => $configForDbConnected,
            ),
        ));
        $this->services->set('Config', $config);
        $this->services->set($tableServiceName, new stdClass());
        $this->assertTrue($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    /**
     * @dataProvider validConfig
     */
    public function testFactoryReturnsResourceBasedOnConfiguration($configForDbConnected, $tableServiceName)
    {
        $tableGateway = $this->getMockBuilder('Laminas\Db\TableGateway\TableGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $this->services->set($tableServiceName, $tableGateway);

        $config = array('api-tools' => array(
            'db-connected' => array(
                'Foo' => $configForDbConnected,
            ),
        ));
        $this->services->set('Config', $config);

        $resource = $this->factory->createServiceWithName($this->services, 'foo', 'Foo');
        $this->assertInstanceOf('Laminas\ApiTools\DbConnectedResource', $resource);
    }
}
