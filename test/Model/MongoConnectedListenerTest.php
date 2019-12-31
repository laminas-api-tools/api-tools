<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Model;

use Laminas\ApiTools\Model\MongoConnectedListener;
use MongoClient;
use MongoCollection;
use MongoDB;
use PHPUnit\Framework\TestCase;

class MongoConnectedListenerTest extends TestCase
{
    protected static $mongoDb;

    protected function setUp()
    {
        if (! (extension_loaded('mongodb') || extension_loaded('mongo'))
            || ! class_exists(MongoClient::class)
            || version_compare(MongoClient::VERSION, '1.4.1', '<')
        ) {
            $this->markTestSkipped(
                'ext/mongo or ext/mongodb + alcaeus/mongo-php-adapter is not available'
            );
        }

        $m  = new MongoClient();
        static::$mongoDb = $m->selectDB("test_laminas_api-tools_mongoconnected");
        $collection = new MongoCollection(static::$mongoDb, 'test');

        $this->mongoListener = new MongoConnectedListener($collection);
    }

    public static function tearDownAfterClass()
    {
        if (static::$mongoDb instanceof MongoDB) {
            static::$mongoDb->drop();
        }
    }

    public function testCreate()
    {
        $data = [ 'foo' => 'bar' ];
        $result = $this->mongoListener->create($data);
        $this->assertTrue(isset($result['_id']));
        return $result['_id'];
    }

    /**
     * @depends testCreate
     */
    public function testFetch($lastId)
    {
        if (empty($lastId)) {
            $this->markTestIncomplete(
                'This test cannot be executed; no identifier returned by testCreate().'
            );
        }

        $result = $this->mongoListener->fetch($lastId);
        $this->assertTrue(! empty($result));
        $this->assertEquals($lastId, $result['_id']);
        return $lastId;
    }

    /**
     * @depends testFetch
     */
    public function testPatch($lastId)
    {
        if (empty($lastId)) {
            $this->markTestIncomplete(
                'This test cannot be executed; no identifier returned by testFetch(), or testFetch() not executed.'
            );
        }
        $data = [ 'foo' => 'baz' ];
        $this->assertTrue($this->mongoListener->patch($lastId, $data));
        return $lastId;
    }

    /**
     * @depends testPatch
     */
    public function testDelete($lastId)
    {
        if (empty($lastId)) {
            $this->markTestIncomplete(
                'This test cannot be executed; no identifier returned by testPatch(), or testPatch() not executed.'
            );
        }
        $result = $this->mongoListener->delete($lastId);
        $this->assertTrue($result);
    }

    public function testFetchAll()
    {
        $num = 3;
        for ($i = 0; $i < $num; $i++) {
            $this->mongoListener->create([
                'foo'   => 'bau',
                'count' => $i
            ]);
        }
        $data = [ 'foo' => 'bau' ];
        $result = $this->mongoListener->fetchAll($data);
        $this->assertTrue(! empty($result));
        $this->assertTrue(is_array($result));
        $this->assertEquals($num, count($result));
    }
}
