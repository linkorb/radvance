<?php

namespace LinkORB\Framework\Repository;

use LinkORB\Framework\Model\ModelInterface;

interface RepositoryInterface
{
    public function createEntity();

    /**
     * Return table name.
     * @return string
     */
    public function getTable();

    /**
     * Find one record by ID.
     *
     * @param  integer $id
     * @return ModelInterface
     */
    public function find($id);

    /**
     * @param  integer $id
     * @return ModelInterface
     */
    public function findOrCreate($id);

    /**
     * Find one record by some conditions.
     *
     * @param  array $where
     * @return ModelInterface
     * @throws Exception If record not found
     */
    public function findOneBy($where);

    /**
     * Find one record by some conditions.
     * Returns null if record not found.
     *
     * @param  array $where
     * @return ModelInterface|null
     */
    public function findOneOrNullBy($where);

    /**
     * Find all records.
     *
     * @return ModelInterface[]
     */
    public function findAll();

    /**
     * Find records by some conditions.
     *
     * @param  array $where
     * @return ModelInterface[]
     */
    public function findBy($where);

    /**
     * Insert or update record.
     *
     * @param  ModelInterface $entity
     * @return ModelInterface
     */
    public function persist(ModelInterface $entity);

    /**
     * Remove record from database.
     *
     * @param  ModelInterface $entity
     */
    public function remove(ModelInterface $entity);
}
