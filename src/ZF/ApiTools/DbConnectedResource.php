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
        if ($this->getInputFilter()) {
            $filter = $this->getInputFilter();
            $data   = $filter->getValues();
        } else {
            $data = (array) $data;
        }

        $this->table->insert($data);
        $id = $this->table->getLastInsertValue();
        return $this->fetch($id);
    }

    public function update($id, $data)
    {
        $data = (array) $data;

        $this->table->update($data, array($this->identifierName => $id));
        return $this->fetch($id);
    }

    public function patch($id, $data)
    {
        return $this->update($id, $data);
    }

    public function delete($id)
    {
        $item = $this->table->delete(array($this->identifierName => $id));
        return ($item > 0);
    }

    public function fetch($id)
    {
        $resultSet = $this->table->select(array($this->identifierName => $id));
        if (0 === $resultSet->count()) {
            throw new \Exception('Item not found', 404);
        }
        return $resultSet->current();
    }

    public function fetchAll($data = array())
    {
        $adapter = new TableGatewayPaginator($this->table);
        return new $this->collectionClass($adapter);
    }
}
