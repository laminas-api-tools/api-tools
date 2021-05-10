<?php

namespace Laminas\ApiTools\Model;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\Rest\AbstractResourceListener;
use Laminas\ApiTools\Rest\Exception\CreationException;
use MongoCollection;
use MongoException;
use MongoId;

class MongoConnectedListener extends AbstractResourceListener
{
    /**
     * @var MongoCollection
     */
    protected $collection;

    /**
     * @param MongoCollection $collection
     */
    public function __construct(MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Create a new document in the MongoCollection
     *
     * @param  array|object $data
     * @return array
     * @throws CreationException
     */
    public function create($data)
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        try {
            $this->collection->insert($data);
        } catch (MongoException $e) {
            throw new CreationException('MongoDB error: ' . $e->getMessage());
        }
        $data['_id'] = (string) $data['_id'];
        return $data;
    }

    /**
     * Update of a document specified by id
     *
     * @param  string $id
     * @param  array $data
     * @return bool
     */
    public function patch($id, $data)
    {
        $result = $this->collection->update(
            [ '_id' => new MongoId($id) ],
            [ '$set' => $data ]
        );

        if (isset($result['ok']) && $result['ok']) {
            return true;
        }
        return $result === true;
    }

    /**
     * Fetch data in a collection using the id
     *
     * @param  string $id
     * @return array|ApiProblem
     */
    public function fetch($id)
    {
        $result = $this->collection->findOne([
            '_id' => new MongoId($id)
        ]);

        if (null === $result) {
            return new ApiProblem(404, 'Document not found in the collection');
        }
        $result['_id'] = (string) $result['_id'];
        return $result;
    }

    /**
     * Fetch all data in a collection
     *
     * @param  array $params
     * @return array
     */
    public function fetchAll($params = [])
    {
        // @todo How to handle the pagination?
        $rows = $this->collection->find($params);
        $result = [];
        foreach ($rows as $id => $collection) {
            unset($collection['_id']);
            $result[$id] = $collection;
        }
        return $result;
    }

    /**
     * Delete a document in a collection
     *
     * @param  string $id
     * @return bool
     */
    public function delete($id)
    {
        $result = $this->collection->remove([
            '_id' => new MongoId($id)
        ]);
        if (isset($result['ok']) && $result['ok']) {
            return true;
        }
        return $result === true;
    }
}
