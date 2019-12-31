<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools;

use Laminas\ApiTools\Rest\AbstractResourceListener;
use Laminas\Db\TableGateway\TableGatewayInterface as TableGateway;
use Laminas\Paginator\Adapter\DbTableGateway as TableGatewayPaginator;

class DbConnectedResource extends AbstractResourceListener
{
    protected $collectionClass;

    protected $identifierName;

    protected $table;

    public function __construct(TableGateway $table, $identifierName, $collectionClass)
    {
        $this->table           = $table;
        $this->identifierName  = $identifierName;
        $this->collectionClass = $collectionClass;
    }

    public function create($data)
    {
        $data = $this->retrieveData($data);
        $this->table->insert($data);
        $id = $this->table->getLastInsertValue();
        return $this->fetch($id);
    }

    public function update($id, $data)
    {
        $data = $this->retrieveData($data);
        $this->table->update($data, [$this->identifierName => $id]);
        return $this->fetch($id);
    }

    public function patch($id, $data)
    {
        return $this->update($id, $data);
    }

    public function delete($id)
    {
        $item = $this->table->delete([$this->identifierName => $id]);
        return ($item > 0);
    }

    public function fetch($id)
    {
        $resultSet = $this->table->select([$this->identifierName => $id]);
        if (0 === $resultSet->count()) {
            throw new \Exception('Item not found', 404);
        }
        return $resultSet->current();
    }

    public function fetchAll($data = [])
    {
        $adapter = new TableGatewayPaginator($this->table);
        return new $this->collectionClass($adapter);
    }

    /**
     * Retrieve data
     *
     * Retrieve data from composed input filter, if any; if none, cast the data
     * passed to the method to an array.
     *
     * @param mixed $data
     * @return array
     */
    protected function retrieveData($data)
    {
        $filter = $this->getInputFilter();
        if (null !== $filter) {
            return $filter->getValues();
        }

        return (array) $data;
    }
}
