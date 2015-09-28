<?php

namespace LinkORB\Framework\Repository;

use LinkORB\Framework\Model\ModelInterface;
use Exception;

/**
 * This class provide support for legacy methods.
 */
abstract class BaseLegacyRepository extends BaseRepository
{
    /**
     * On legacy code we shouldn't get exceptions when nothing found.
     *
     * {@inheritdoc}
     */
    public function find($id)
    {
        return $this->findOneOrNullBy(array('id'=>$id));
    }

    /**
     * @deprecated
     */
    public function getById($id)
    {
        // trigger_error("Method 'getById' is deprecated. Use 'find' instead.", E_USER_DEPRECATED);
        return $this->find($id);
    }

    /**
     * @deprecated
     */
    public function getAll()
    {
        // trigger_error("Method 'getAll' is deprecated. Use 'findAll' instead.", E_USER_DEPRECATED);
        return $this->findAll();
    }

    /**
     * @deprecated
     */
    public function add(ModelInterface $entity)
    {
        // trigger_error("Method 'add' is deprecated. Use 'persist' instead.", E_USER_DEPRECATED);
        return $this->persist($entity);
    }

    /**
     * @deprecated
     */
    public function update(ModelInterface $entity)
    {
        // trigger_error("Method 'update' is deprecated. Use 'persist' instead.", E_USER_DEPRECATED);
        $this->persist($entity);
    }

    public function delete(ModelInterface $entity)
    {
        // trigger_error("Method 'delete' is deprecated. Use 'remove' instead.", E_USER_DEPRECATED);
        return $this->remove($entity);
    }

}
