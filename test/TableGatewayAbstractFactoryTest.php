<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\TableGatewayAbstractFactory;
use Laminas\Db\Adapter\Adapter as DbAdapter;
use Laminas\Db\Adapter\AdapterInterface as DbAdapterInterface;
use Laminas\Db\Adapter\Platform\PlatformInterface as DbPlatformInterface;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Hydrator\ClassMethods;
use Laminas\Hydrator\ClassMethodsHydrator;
use Laminas\Hydrator\HydratorPluginManager;
use PHPUnit\Framework\TestCase;

class TableGatewayAbstractFactoryTest extends TestCase
{
    protected function setUp()
    {
        $this->services = $this->prophesize(ContainerInterface::class);
        $this->factory  = new TableGatewayAbstractFactory();
    }

    public function testWillNotCreateServiceWithoutAppropriateSuffix()
    {
        $this->services->has('config')->shouldNotBeCalled();
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    public function testWillNotCreateServiceIfConfigServiceIsMissing()
    {
        $this->services->has('config')->willReturn(false);
        $this->services->get('config')->shouldNotBeCalled();
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingApiToolsConfig()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn([]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingDbConnectedConfigSegment()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['api-tools' => []]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingServiceSubSegment()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['api-tools' => ['db-connected' => []]]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentIsInvalid()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['api-tools' => ['db-connected' => ['Foo' => 'invalid']]]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainTableName()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['api-tools' => ['db-connected' => ['Foo' => []]]]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainAdapterInformation()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')
            ->willReturn([
                'api-tools' => [
                    'db-connected' => [
                        'Foo' => [
                            'table_name' => 'test',
                        ],
                    ],
                ],
            ]);
        $this->services->has(DbAdapterInterface::class)->willReturn(false);

        $this->services->has(\Zend\Db\Adapter\AdapterInterface::class)->willReturn(false);
        $this->services->has(DbAdapter::class)->willReturn(false);
        $this->services->has(\Zend\Db\Adapter\Adapter::class)->willReturn(false);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillCreateServiceIfConfigContainsValidTableNameAndAdapterName()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')
            ->willReturn([
                'api-tools' => [
                    'db-connected' => [
                        'Foo' => [
                            'table_name'   => 'test',
                            'adapter_name' => 'FooAdapter',
                        ],
                    ],
                ],
            ]);

        $this->services->has('FooAdapter')->willReturn(true);
        $this->assertTrue($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillCreateServiceIfConfigContainsValidTableNameNoAdapterNameAndServicesContainDefaultAdapter()
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')
            ->willReturn([
                'api-tools' => [
                    'db-connected' => [
                        'Foo' => [
                            'table_name' => 'test',
                        ],
                    ],
                ],
            ]);

        $this->services->has(DbAdapterInterface::class)->willReturn(false);

        $this->services->has(\Zend\Db\Adapter\AdapterInterface::class)->willReturn(false);
        $this->services->has(DbAdapter::class)->willReturn(true);
        $this->assertTrue($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function validConfig()
    {
        return [
            'named_adapter'   => ['Db\NamedAdapter'],
            'default_adapter' => [DbAdapter::class],
        ];
    }

    /**
     * @dataProvider validConfig
     */
    public function testFactoryReturnsTableGatewayInstanceBasedOnConfiguration($adapterServiceName)
    {
        $hydrator = $this->prophesize($this->getClassMethodsHydratorClassName())->reveal();

        $hydrators = $this->prophesize(HydratorPluginManager::class);
        $hydrators->get('ClassMethods')->willReturn($hydrator);
        $this->services->get('HydratorManager')->willReturn($hydrators->reveal());

        $platform = $this->prophesize(DbPlatformInterface::class);
        $platform->getName()->willReturn('sqlite');

        $adapter = $this->prophesize(DbAdapter::class);
        $adapter->getPlatform()->willReturn($platform->reveal());

        $this->services->has($adapterServiceName)->willReturn(true);
        $this->services->get($adapterServiceName)->willReturn($adapter->reveal());

        $config = [
            'api-tools' => [
                'db-connected' => [
                    'Foo' => [
                        'controller_service_name' => 'Foo\Controller',
                        'table_name'              => 'foo',
                        'hydrator_name'           => 'ClassMethods',
                    ],
                ],
            ],
            'api-tools-rest' => [
                'Foo\Controller' => [
                    'entity_class' => TestAsset\Foo::class,
                ],
            ],
        ];
        if ($adapterServiceName !== DbAdapter::class) {
            $config['api-tools']['db-connected']['Foo']['adapter_name'] = $adapterServiceName;
        }
        $this->services->get('config')->willReturn($config);

        $gateway = $this->factory->__invoke($this->services->reveal(), 'Foo\Table');
        $this->assertInstanceOf(TableGateway::class, $gateway);
        $this->assertEquals('foo', $gateway->getTable());
        $this->assertSame($adapter->reveal(), $gateway->getAdapter());
        $resultSet = $gateway->getResultSetPrototype();
        $this->assertInstanceOf(HydratingResultSet::class, $resultSet);
        $this->assertSame($hydrator, $resultSet->getHydrator());
        $this->assertAttributeInstanceOf(TestAsset\Foo::class, 'objectPrototype', $resultSet);
    }

    /**
     * @dataProvider validConfig
     */
    public function testFactoryReturnsTableGatewayInstanceBasedOnConfigurationWithoutLaminasRest($adapterServiceName)
    {
        $hydrator = $this->prophesize($this->getClassMethodsHydratorClassName())->reveal();

        $hydrators = $this->prophesize(HydratorPluginManager::class);
        $hydrators->get('ClassMethods')->willReturn($hydrator);
        $this->services->get('HydratorManager')->willReturn($hydrators->reveal());

        $platform = $this->prophesize(DbPlatformInterface::class);
        $platform->getName()->willReturn('sqlite');

        $adapter = $this->prophesize(DbAdapter::class);
        $adapter->getPlatform()->willReturn($platform->reveal());

        $this->services->has($adapterServiceName)->willReturn(true);
        $this->services->get($adapterServiceName)->willReturn($adapter);

        $config = [
            'api-tools' => [
                'db-connected' => [
                    'Foo' => [
                        'controller_service_name' => 'Foo\Controller',
                        'table_name'              => 'foo',
                        'hydrator_name'           => 'ClassMethods',
                        'entity_class'            => TestAsset\Bar::class,
                    ],
                ],
            ],
        ];
        if ($adapterServiceName !== DbAdapter::class) {
            $config['api-tools']['db-connected']['Foo']['adapter_name'] = $adapterServiceName;
        }
        $this->services->get('config')->willReturn($config);

        $gateway = $this->factory->__invoke($this->services->reveal(), 'Foo\Table');
        $this->assertInstanceOf(TableGateway::class, $gateway);
        $this->assertEquals('foo', $gateway->getTable());
        $this->assertSame($adapter->reveal(), $gateway->getAdapter());
        $resultSet = $gateway->getResultSetPrototype();
        $this->assertInstanceOf(HydratingResultSet::class, $resultSet);
        $this->assertInstanceOf($this->getClassMethodsHydratorClassName(), $resultSet->getHydrator());
        $this->assertAttributeInstanceOf(TestAsset\Bar::class, 'objectPrototype', $resultSet);
    }

    /**
     * Simple check whether we should use ClassMethodsHydrator from laminas-hydrator 3
     * as ClassMethods from < 3.0.0 is deprecated and triggers an E_USER_DEPRECATED error
     *
     * @return string
     */
    private function getClassMethodsHydratorClassName()
    {
        if (class_exists(ClassMethodsHydrator::class)) {
            return ClassMethodsHydrator::class;
        }

        return ClassMethods::class;
    }
}
