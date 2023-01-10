<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\DbConnectedResource;
use Laminas\ApiTools\DbConnectedResourceAbstractFactory;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class DbConnectedResourceAbstractFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @var ContainerInterface|ObjectProphecy */
    protected $services;

    /** @var DbConnectedResourceAbstractFactory */
    protected $factory;

    protected function setUp(): void
    {
        $this->services = $this->prophesize(ContainerInterface::class);
        $this->factory  = new DbConnectedResourceAbstractFactory();
    }

    public function testWillNotCreateServiceIfConfigServiceMissing(): void
    {
        $this->services->has('config')->willReturn(false);
        /** @var ContainerInterface $services */
        $services = $this->services->reveal();
        $this->assertFalse($this->factory->canCreate($services, 'Foo'));
    }

    public function testWillNotCreateServiceIfApiToolsConfigMissing(): void
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn([]);
        /** @var ContainerInterface $services */
        $services = $this->services->reveal();
        $this->assertFalse($this->factory->canCreate($services, 'Foo'));
    }

    public function testWillNotCreateServiceIfApiToolsConfigIsNotAnArray(): void
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['api-tools' => 'invalid']);
        /** @var ContainerInterface $services */
        $services = $this->services->reveal();
        $this->assertFalse($this->factory->canCreate($services, 'Foo'));
    }

    public function testWillNotCreateServiceIfApiToolsConfigDoesNotHaveDbConnectedSegment(): void
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn(['api-tools' => ['foo' => 'bar']]);
        /** @var ContainerInterface $services */
        $services = $this->services->reveal();
        $this->assertFalse($this->factory->canCreate($services, 'Foo'));
    }

    public function testWillNotCreateServiceIfDbConnectedSegmentDoesNotHaveRequestedName(): void
    {
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')
           ->willReturn([
               'api-tools' => [
                   'db-connected' => [
                       'bar' => 'baz',
                   ],
               ],
           ]);
        $this->services->has('Foo\Table')->willReturn(false);
        /** @var ContainerInterface $services */
        $services = $this->services->reveal();
        $this->assertFalse($this->factory->canCreate($services, 'Foo'));
    }

    public function invalidConfig(): array
    {
        return [
            'invalid_table_service' => [['table_service' => 'non_existent']],
            'invalid_virtual_table' => [[]],
        ];
    }

    /**
     * @param array<string,string> $configForDbConnected
     * @dataProvider invalidConfig
     */
    public function testWillNotCreateServiceIfDbConnectedSegmentIsInvalidConfiguration(
        array $configForDbConnected
    ): void {
        $config = [
            'api-tools' => [
                'db-connected' => [
                    'Foo' => $configForDbConnected,
                ],
            ],
        ];
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn($config);

        if (isset($configForDbConnected['table_service'])) {
            $this->services->has($configForDbConnected['table_service'])->willReturn(false);
        } else {
            $this->services->has('Foo\Table')->willReturn(false);
        }

        /** @var ContainerInterface $services */
        $services = $this->services->reveal();
        $this->assertFalse($this->factory->canCreate($services, 'Foo'));
    }

    /** @psalm-return array<string, array{0: array<string, string>, 1: string}> */
    public function validConfig(): array
    {
        return [
            'table_service' => [['table_service' => 'foobartable'], 'foobartable'],
            'virtual_table' => [[], 'Foo\Table'],
        ];
    }

    /**
     * @dataProvider validConfig
     */
    public function testWillCreateServiceIfDbConnectedSegmentIsValid(
        array $configForDbConnected,
        string $tableServiceName
    ): void {
        $config = [
            'api-tools' => [
                'db-connected' => [
                    'Foo' => $configForDbConnected,
                ],
            ],
        ];
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn($config);
        $this->services->has($tableServiceName)->willReturn(true);

        /** @var ContainerInterface $services */
        $services = $this->services->reveal();
        $this->assertTrue($this->factory->canCreate($services, 'Foo'));
    }

    /**
     * @param array $configForDbConnected
     * @dataProvider validConfig
     */
    public function testFactoryReturnsResourceBasedOnConfiguration(
        array $configForDbConnected,
        string $tableServiceName
    ): void {
        $tableGateway = $this->prophesize(TableGateway::class)->reveal();
        $this->services->has($tableServiceName)->willReturn(true);
        $this->services->get($tableServiceName)->willReturn($tableGateway);

        $config = [
            'api-tools' => [
                'db-connected' => [
                    'Foo' => $configForDbConnected,
                ],
            ],
        ];
        $this->services->has('config')->willReturn(true);
        $this->services->get('config')->willReturn($config);

        /** @var ContainerInterface $services */
        $services = $this->services->reveal();
        /** @var DbConnectedResource $resource */
        $resource = $this->factory->__invoke($services, 'Foo');
        $this->assertInstanceOf(DbConnectedResource::class, $resource);
    }
}
