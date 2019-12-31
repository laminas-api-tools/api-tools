<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools;

use Laminas\ApiTools\ApiProblem\Exception\DomainException;
use Laminas\ApiTools\Rest\AbstractResourceListener;
use Laminas\Db\TableGateway\TableGatewayInterface as TableGateway;
use Laminas\Paginator\Adapter\DbTableGateway as TableGatewayPaginator;

class DbConnectedResource extends AbstractResourceListener
{
    /**
     * @var string Name of collection class
     */
    protected $collectionClass;

    /**
     * @var string Name of identifier field
     */
    protected $identifierName;

    /**
     * @var TableGateway
     */
    protected $table;

    /**
     * @param TableGateway $table
     * @param string $identifierName
     * @param string $collectionClass
     */
    public function __construct(TableGateway $table, $identifierName, $collectionClass)
    {
        $this->table           = $table;
        $this->identifierName  = $identifierName;
        $this->collectionClass = $collectionClass;
    }

    /**
     * Create a new resource.
     *
     * @param array|object $data Data representing the resource to create.
     * @return array|object Newly created resource.
     */
    public function create($data)
    {
        $data = $this->retrieveData($data);
        $this->table->insert($data);
        $id = $this->table->getLastInsertValue();
        return $this->fetch($id);
    }

    /**
     * Replace an existing resource.
     *
     * @param int|string $id Identifier of resource.
     * @param array|object $data Data with which to replace the resource.
     * @return array|object Updated resource.
     */
    public function update($id, $data)
    {
        $data = $this->retrieveData($data);
        $this->table->update($data, [$this->identifierName => $id]);
        return $this->fetch($id);
    }

    /**
     * Update an existing resource.
     *
     * @param int|string $id Identifier of resource.
     * @param array|object $data Data with which to update the resource.
     * @return array|object Updated resource.
     */
    public function patch($id, $data)
    {
        return $this->update($id, $data);
    }

    /**
     * Delete an existing resource.
     *
     * @param int|string $id Identifier of resource.
     * @return bool
     */
    public function delete($id)
    {
        $item = $this->table->delete([$this->identifierName => $id]);
        return ($item > 0);
    }

    /**
     * Fetch an existing resource.
     *
     * @param int|string $id Identifier of resource.
     * @return array|object Resource.
     * @throws DomainException if the resource is not found.
     */
    public function fetch($id)
    {
        $resultSet = $this->table->select([$this->identifierName => $id]);
        if (0 === $resultSet->count()) {
            throw new DomainException('Item not found', 404);
        }
        return $resultSet->current();
    }

    /**
     * Fetch a paginated set of resources.
     *
     * @param array|object $data Ignored.
     * @return \Laminas\Paginator\Paginator
     */
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
