<?php

namespace Radvance\Repository;

use Minerva\Orm\BasePdoRepository;
use PDO;

class BaseRepository extends BasePdoRepository
{
    public function getTable()
    {
        return $this->getTableName();
    }

    protected $spaceForeignKey;

    public function getSpaceForeignKey()
    {
        return $this->spaceForeignKey;
    }

    public function setSpaceForeignKey($spaceForeignKey)
    {
        $this->spaceForeignKey = $spaceForeignKey;

        return $this;
    }

    public function findBy($where)
    {
        $rows = $this->fetchRows($where);

        return $this->rowsToObjects($rows);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneOrNullBy($where)
    {
        $rows = $this->fetchRows($where, 1);
        if (!$rows) {
            return null;
        }

        return $this->rowToObject($rows[0]);
    }

    public function findOneBy($where)
    {
        $entity = $this->findOneOrNullBy($where);

        if (is_null($entity)) {
            throw new \Exception(sprintf(
                "Entity '%s' with %s not found",
                $this->getTable(),
                $this->describeWhereFields($where)
            ));
        }

        return $entity;
    }

    public function getByLibraryId($libraryId)
    {
        throw new \RuntimeException(
            'getByLibraryId should not be in the BaseRepository. Implement it in subclass if you need it.'
        );
    }

    public function findByIds($ids)
    {
        if (!$ids) {
            return;
        }
        $in = str_repeat('?,', count($ids) - 1).'?';

        $sql = 'SELECT *  FROM  `'.$this->getTable().'` WHERE id IN ( '.$in.' )  ORDER By id ASC ';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($ids);

        return $this->rowsToObjects($statement->fetchAll(PDO::FETCH_ASSOC));
    }
}
