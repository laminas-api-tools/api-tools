<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools;

use Laminas\ApiTools\TableGatewayAbstractFactory;
use Laminas\Stdlib\Hydrator\HydratorPluginManager;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

class TableGatewayAbstractFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new TestAsset\ServiceManager();
        $this->factory  = new TableGatewayAbstractFactory();
    }

    public function testWillNotCreateServiceWithoutAppropriateSuffix()
    {
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfConfigServiceIsMissing()
    {
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingApiToolsConfig()
    {
        $this->services->set('Config', array());
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingDbConnectedConfigSegment()
    {
        $this->services->set('Config', array('api-tools' => array()));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingServiceSubSegment()
    {
        $this->services->set('Config', array('api-tools' => array('db-connected' => array())));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentIsInvalid()
    {
        $this->services->set('Config', array('api-tools' => array('db-connected' => array('Foo' => 'invalid'))));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainTableName()
    {
        $this->services->set('Config', array('api-tools' => array('db-connected' => array('Foo' => array()))));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainAdapterInformation()
    {
        $this->services->set('Config', array('api-tools' => array('db-connected' => array('Foo' => array('table_name' => 'test')))));
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillCreateServiceIfConfigContainsValidTableNameAndAdapterName()
    {
        $this->services->set('Config', array('api-tools' => array('db-connected' => array('Foo' => array(
            'table_name'   => 'test',
            'adapter_name' => 'FooAdapter',
        )))));

        $this->services->set('FooAdapter', new stdClass());
        $this->assertTrue($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function testWillCreateServiceIfConfigContainsValidTableNameNoAdapterNameAndServicesContainDefaultAdapter()
    {
        $this->services->set('Config', array('api-tools' => array('db-connected' => array('Foo' => array(
            'table_name'   => 'test',
        )))));

        $this->services->set('Laminas\Db\Adapter\Adapter', new stdClass());
        $this->assertTrue($this->factory->canCreateServiceWithName($this->services, 'footable', 'Foo\Table'));
    }

    public function validConfig()
    {
        return array(
            'named_adapter'   => array('Db\NamedAdapter'),
            'default_adapter' => array('Laminas\Db\Adapter\Adapter'),
        );
    }

    /**
     * @dataProvider validConfig
     */
    public function testFactoryReturnsTableGatewayInstanceBasedOnConfiguration($adapterServiceName)
    {
        $this->services->set('HydratorManager', new HydratorPluginManager());

        $platform = $this->getMockBuilder('Laminas\Db\Adapter\Platform\PlatformInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $platform->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('sqlite'));

        $adapter = $this->getMockBuilder('Laminas\Db\Adapter\Adapter')
            ->disableOriginalConstructor()
            ->getMock();
        $adapter->expects($this->any())
            ->method('getPlatform')
            ->will($this->returnValue($platform));

        $this->services->set($adapterServiceName, $adapter);

        $config = array(
            'api-tools' => array(
                'db-connected' => array(
                    'Foo' => array(
                        'controller_service_name' => 'Foo\Controller',
                        'table_name'              => 'foo',
                        'hydrator_name'           => 'ClassMethods',
                    ),
                ),
            ),
            'api-tools-rest' => array(
                'Foo\Controller' => array(
                    'entity_class' => 'LaminasTest\ApiTools\TestAsset\Foo',
                ),
            ),
        );
        if ($adapterServiceName !== 'Laminas\Db\Adapter\Adapter') {
            $config['api-tools']['db-connected']['Foo']['adapter_name'] = $adapterServiceName;
        }
        $this->services->set('Config', $config);

        $gateway = $this->factory->createServiceWithName($this->services, 'footable', 'Foo\Table');
        $this->assertInstanceOf('Laminas\Db\TableGateway\TableGateway', $gateway);
        $this->assertEquals('foo', $gateway->getTable());
        $this->assertSame($adapter, $gateway->getAdapter());
        $resultSet = $gateway->getResultSetPrototype();
        $this->assertInstanceOf('Laminas\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertInstanceOf('Laminas\Stdlib\Hydrator\ClassMethods', $resultSet->getHydrator());
        $this->assertAttributeInstanceOf('LaminasTest\ApiTools\TestAsset\Foo', 'objectPrototype', $resultSet);
    }
}
