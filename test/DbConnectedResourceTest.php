<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools;

use Laminas\ApiTools\DbConnectedResource;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionObject;

class DbConnectedResourceTest extends TestCase
{
    public function setUp()
    {
        $this->table = $this->getMockBuilder('Laminas\Db\TableGateway\TableGateway')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resource = new DbConnectedResource($this->table, 'id', 'ArrayObject');
    }

    protected function setInputFilter($resource, $inputFilter)
    {
        $r = new ReflectionObject($resource);
        $p = $r->getProperty('inputFilter');
        $p->setAccessible(true);
        $p->setValue($resource, $inputFilter);
    }

    public function testCreatePullsDataFromComposedInputFilterWhenPresent()
    {
        $filtered = array(
            'foo' => 'BAR',
            'baz' => 'QUZ',
        );

        $filter = $this->getMock('Laminas\InputFilter\InputFilter');
        $filter->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue($filtered));

        $this->setInputFilter($this->resource, $filter);

        $this->table->expects($this->once())
            ->method('insert')
            ->with($this->equalTo($filtered));

        $this->table->expects($this->once())
            ->method('getLastInsertValue')
            ->will($this->returnValue('foo'));

        $resultSet = $this->getMock('Laminas\Db\ResultSet\AbstractResultSet');

        $this->table->expects($this->once())
            ->method('select')
            ->with($this->equalTo(array('id' => 'foo')))
            ->will($this->returnValue($resultSet));

        $resultSet->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1));

        $resultSet->expects($this->once())
            ->method('current')
            ->will($this->returnValue($filtered));

        $this->assertEquals($filtered, $this->resource->create(array('foo' => 'bar')));
    }

    public function testUpdatePullsDataFromComposedInputFilterWhenPresent()
    {
        $filtered = array(
            'foo' => 'BAR',
            'baz' => 'QUZ',
        );

        $filter = $this->getMock('Laminas\InputFilter\InputFilter');
        $filter->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue($filtered));

        $this->setInputFilter($this->resource, $filter);

        $this->table->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo($filtered),
                array('id' => 'foo')
            );

        $resultSet = $this->getMock('Laminas\Db\ResultSet\AbstractResultSet');

        $this->table->expects($this->once())
            ->method('select')
            ->with($this->equalTo(array('id' => 'foo')))
            ->will($this->returnValue($resultSet));

        $resultSet->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1));

        $resultSet->expects($this->once())
            ->method('current')
            ->will($this->returnValue($filtered));

        $this->assertEquals($filtered, $this->resource->update('foo', array('foo' => 'bar')));
    }

    public function testPatchPullsDataFromComposedInputFilterWhenPresent()
    {
        $filtered = array(
            'foo' => 'BAR',
            'baz' => 'QUZ',
        );

        $filter = $this->getMock('Laminas\InputFilter\InputFilter');
        $filter->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue($filtered));

        $this->setInputFilter($this->resource, $filter);

        $this->table->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo($filtered),
                array('id' => 'foo')
            );

        $resultSet = $this->getMock('Laminas\Db\ResultSet\AbstractResultSet');

        $this->table->expects($this->once())
            ->method('select')
            ->with($this->equalTo(array('id' => 'foo')))
            ->will($this->returnValue($resultSet));

        $resultSet->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1));

        $resultSet->expects($this->once())
            ->method('current')
            ->will($this->returnValue($filtered));

        $this->assertEquals($filtered, $this->resource->patch('foo', array('foo' => 'bar')));
    }
}
