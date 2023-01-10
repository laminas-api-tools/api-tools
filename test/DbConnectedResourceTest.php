<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools;

use ArrayObject;
use Laminas\ApiTools\DbConnectedResource;
use Laminas\Db\ResultSet\AbstractResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\InputFilter\InputFilter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionException;
use ReflectionObject;

class DbConnectedResourceTest extends TestCase
{
    use ProphecyTrait;

    /** @var TableGateway|ObjectProphecy */
    protected $table;

    /** @var DbConnectedResource */
    protected $resource;

    protected function setUp(): void
    {
        $this->table    = $this->prophesize(TableGateway::class);
        $this->resource = new DbConnectedResource($this->table->reveal(), 'id', ArrayObject::class);
    }

    /**
     * @throws ReflectionException
     */
    protected function setInputFilter(
        DbConnectedResource $resource,
        InputFilter $inputFilter
    ): void {
        $r = new ReflectionObject($resource);
        $p = $r->getProperty('inputFilter');
        $p->setAccessible(true);
        $p->setValue($resource, $inputFilter);
    }

    public function testCreatePullsDataFromComposedInputFilterWhenPresent(): void
    {
        $filtered = [
            'foo' => 'BAR',
            'baz' => 'QUZ',
        ];

        $filter = $this->prophesize(InputFilter::class);
        $filter->getValues()->willReturn($filtered);
        $this->setInputFilter($this->resource, $filter->reveal());

        $this->table->insert($filtered)->shouldBeCalled();
        $this->table->getLastInsertValue()->willReturn('foo');

        $resultSet = $this->prophesize(AbstractResultSet::class);
        $resultSet->count()->willReturn(1);
        $resultSet->current()->willReturn($filtered);

        $this->table->select(['id' => 'foo'])->willReturn($resultSet->reveal());

        $this->assertEquals($filtered, $this->resource->create(['foo' => 'bar']));
    }

    public function testUpdatePullsDataFromComposedInputFilterWhenPresent(): void
    {
        $filtered = [
            'foo' => 'BAR',
            'baz' => 'QUZ',
        ];

        $filter = $this->prophesize(InputFilter::class);
        $filter->getValues()->willReturn($filtered);
        $this->setInputFilter($this->resource, $filter->reveal());

        $this->table->update($filtered, ['id' => 'foo'])->shouldBeCalled();

        $resultSet = $this->prophesize(AbstractResultSet::class);
        $resultSet->count()->willReturn(1);
        $resultSet->current()->willReturn($filtered);

        $this->table->select(['id' => 'foo'])->willReturn($resultSet->reveal());

        $this->assertEquals($filtered, $this->resource->update('foo', ['foo' => 'bar']));
    }

    public function testPatchPullsDataFromComposedInputFilterWhenPresent(): void
    {
        $filtered = [
            'foo' => 'BAR',
            'baz' => 'QUZ',
        ];

        $filter = $this->prophesize(InputFilter::class);
        $filter->getValues()->willReturn($filtered);
        $this->setInputFilter($this->resource, $filter->reveal());

        $this->table->update($filtered, ['id' => 'foo'])->shouldBeCalled();

        $resultSet = $this->prophesize(AbstractResultSet::class);
        $resultSet->count()->willReturn(1);
        $resultSet->current()->willReturn($filtered);

        $this->table->select(['id' => 'foo'])->willReturn($resultSet->reveal());

        $this->assertEquals($filtered, $this->resource->patch('foo', ['foo' => 'bar']));
    }
}
