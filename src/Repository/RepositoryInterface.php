<?php

namespace Radvance\Repository;

use Radvance\Model\ModelInterface;

interface RepositoryInterface extends \Minerva\Orm\RepositoryInterface
{
    public function createEntity();

    /**
     * Return table name.
     *
     * @return string
     */
    public function getTable();

    /**
     * Find one record by ID.
     *
     * @param int $id
     *
     * @return ModelInterface
     */
    public function find($id);

    /**
     * @param int $id
     *
     * @return ModelInterface
     */
    public function findOrCreate($id);

    /**
     * Find one record by some conditions.
     *
     * @param array $where
     *
     * @return ModelInterface
     *
     * @throws Exception If record not found
     */
    public function findOneBy($where);

    /**
     * Find one record by some conditions.
     * Returns null if record not found.
     *
     * @param array $where
     *
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
     * @param array $where
     *
     * @return ModelInterface[]
     */
    public function findBy($where);
}
