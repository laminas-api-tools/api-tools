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
        $this->services->set('Config', []);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfApiToolsConfigIsNotAnArray()
    {
        $this->services->set('Config', ['api-tools' => 'invalid']);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfApiToolsConfigDoesNotHaveDbConnectedSegment()
    {
        $this->services->set('Config', ['api-tools' => ['foo' => 'bar']]);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function testWillNotCreateServiceIfDbConnectedSegmentDoesNotHaveRequestedName()
    {
        $this->services->set('Config', ['api-tools' => [
            'db-connected' => [
                'bar' => 'baz',
            ],
        ]]);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function invalidConfig()
    {
        return [
            'invalid_table_service' => [['table_service' => 'non_existent']],
            'invalid_virtual_table' => [[]],
        ];
    }

    /**
     * @dataProvider invalidConfig
     */
    public function testWillNotCreateServiceIfDbConnectedSegmentIsInvalidConfiguration($configForDbConnected)
    {
        $config = ['api-tools' => [
            'db-connected' => [
                'Foo' => $configForDbConnected,
            ],
        ]];
        $this->services->set('Config', $config);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'foo', 'Foo'));
    }

    public function validConfig()
    {
        return [
            'table_service' => [['table_service' => 'foobartable'], 'foobartable'],
            'virtual_table' => [[], 'Foo\Table'],
        ];
    }

    /**
     * @dataProvider validConfig
     */
    public function testWillCreateServiceIfDbConnectedSegmentIsValid($configForDbConnected, $tableServiceName)
    {
        $config = ['api-tools' => [
            'db-connected' => [
                'Foo' => $configForDbConnected,
            ],
        ]];
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

        $config = ['api-tools' => [
            'db-connected' => [
                'Foo' => $configForDbConnected,
            ],
        ]];
        $this->services->set('Config', $config);

        $resource = $this->factory->createServiceWithName($this->services, 'foo', 'Foo');
        $this->assertInstanceOf('Laminas\ApiTools\DbConnectedResource', $resource);
    }
}
