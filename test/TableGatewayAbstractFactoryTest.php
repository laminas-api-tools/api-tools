<?php

declare(strict_types=1);

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
use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionException;
use ReflectionProperty;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\AdapterInterface;

use function class_exists;

class TableGatewayAbstractFactoryTest extends TestCase
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        $this->services = $this->prophesize(ContainerInterface::class);
        $this->factory  = new TableGatewayAbstractFactory();
    }

    public function testWillNotCreateServiceWithoutAppropriateSuffix(): void
    {
        $this->services->has('config')->shouldNotBeCalled();
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo'));
    }

    public function testWillNotCreateServiceIfConfigServiceIsMissing(): void
    {
        $this->services->has('config')->willReturn(false);
        $this->services->get('config')->shouldNotBeCalled();
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingApiToolsConfig(): void
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn([]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingDbConnectedConfigSegment(): void
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['api-tools' => []]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfMissingServiceSubSegment(): void
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['api-tools' => ['db-connected' => []]]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentIsInvalid(): void
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['api-tools' => ['db-connected' => ['Foo' => 'invalid']]]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainTableName(): void
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['api-tools' => ['db-connected' => ['Foo' => []]]]);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillNotCreateServiceIfServiceSubSegmentDoesNotContainAdapterInformation(): void
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

        $this->services->has(AdapterInterface::class)->willReturn(false);
        $this->services->has(DbAdapter::class)->willReturn(false);
        $this->services->has(Adapter::class)->willReturn(false);
        $this->assertFalse($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    public function testWillCreateServiceIfConfigContainsValidTableNameAndAdapterName(): void
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

    // phpcs:ignore Generic.Files.LineLength.TooLong
    public function testWillCreateServiceIfConfigContainsValidTableNameNoAdapterNameAndServicesContainDefaultAdapter(): void
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

        $this->services->has(AdapterInterface::class)->willReturn(false);
        $this->services->has(DbAdapter::class)->willReturn(true);
        $this->assertTrue($this->factory->canCreate($this->services->reveal(), 'Foo\Table'));
    }

    /** @psalm-return array<string, array{0: class-string}> */
    public function validConfig(): array
    {
        return [
            'named_adapter'   => ['Db\NamedAdapter'],
            'default_adapter' => [DbAdapter::class],
        ];
    }

    /**
     * @dataProvider validConfig
     */
    public function testFactoryReturnsTableGatewayInstanceBasedOnConfiguration(string $adapterServiceName): void
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
            'api-tools'      => [
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

        $this->assertObjectPrototypeProperty($resultSet, TestAsset\Foo::class);
    }

    /**
     * @dataProvider validConfig
     */
    public function testFactoryReturnsTableGatewayInstanceBasedOnConfigurationWithoutLaminasRest(
        string $adapterServiceName
    ): void {
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

        $this->assertObjectPrototypeProperty($resultSet, TestAsset\Bar::class);
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

    /**
     * @throws ReflectionException
     */
    private function assertObjectPrototypeProperty(HydratingResultSet $resultSet, string $expectedClassName): void
    {
        $objectPrototypeProperty = new ReflectionProperty($resultSet, 'objectPrototype');
        $objectPrototypeProperty->setAccessible(true);
        $this->assertInstanceOf($expectedClassName, $objectPrototypeProperty->getValue($resultSet));
    }
}
