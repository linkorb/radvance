<?php

namespace Radvance\Repository;

use Radvance\Model\ModelInterface;
use Exception;
use PDO;

abstract class BaseRepository
{
    private $table;
    protected $pdo;

    public function __construct(PDO $pdo, $table = null)
    {
        if (is_null($table)) {
            $table = get_class($this);
            $table = explode('\\', $table);
            $table = end($table);
            $table = substr($table, strlen('Pdo'), -strlen('Repository'));

            // CamelCase to underscored
            $table = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($table)));
        }

        $this->table = $table;
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        return $this->findOneBy(array('id'=>$id));
    }

    /**
     * {@inheritdoc}
     */
    public function findOrCreate($id)
    {
        $entity = $this->findOneOrNullBy(array('id'=>$id));
        if (!$entity) {
            $entity = $this->createEntity();
        }
        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy($where)
    {
        $entity = $this->findOneOrNullBy($where);

        if (is_null($entity)) {
            throw new Exception(sprintf(
                "Entity '%s' with %s not found",
                $this->getTable(),
                $this->describeWhereFields($where)
            ));
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneOrNullBy($where)
    {
        $statement = $this->pdo->prepare(sprintf(
            "SELECT * FROM `%s` WHERE %s LIMIT 1",
            $this->getTable(),
            $this->buildKeyValuePairs($where, ' and ')
        ));
        $statement->execute($where);
        return $this->rowToObject($statement->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        $statement = $this->pdo->prepare(sprintf(
            "SELECT * FROM %s",
            $this->getTable()
        ));
        $statement->execute();
        return $this->rowsToObjects($statement->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * {@inheritdoc}
     */
    public function findBy($where)
    {
        $statement = $this->pdo->prepare(sprintf(
            "SELECT * FROM `%s` WHERE %s",
            $this->getTable(),
            $this->buildKeyValuePairs($where, ' and ')
        ));
        $statement->execute($where);
        return $this->rowsToObjects($statement->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * {@inheritdoc}
     */
    public function persist(ModelInterface $entity)
    {
        $fields = $entity->toArray();
        unset($fields['id']);

        if ($entity->getId()) {
            $where = array(
                'id'=>$entity->getId()
            );
            $sql = $this->buildUpdateSql($fields, $where);
            $statement = $this->pdo->prepare($sql);
            $res = $statement->execute($this->prepareFieldsValues($fields + $where));
        } else {
            $sql = $this->buildInsertSql($fields);
            $this->pdo->prepare($sql)->execute($this->prepareFieldsValues($fields));
            $entity->setId($this->pdo->lastInsertId());
        }

        return true;
    }

    /**
     * @param  array $fields
     * @return array
     */
    protected function prepareFieldsValues($fields)
    {
        return array_map(function($value){
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d H:i:s');
            }
            return $value;
        }, $fields);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(ModelInterface $entity)
    {
        $statement = $this->pdo->prepare(sprintf(
            "DELETE FROM `%s` WHERE id=:id",
            $this->getTable()
        ));
        $statement->execute(array('id' => $entity->getId()));
    }

    /**
     * {@inheritdoc}
     */
    public function truncate()
    {
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
        $this->pdo->exec(sprintf(
            "TRUNCATE `%s`",
            $this->getTable()
        ));
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
    }

    /**
     * Convert array to entity.
     *
     * @param  array $row
     * @return ModelInterface
     */
    protected function rowToObject($row)
    {
        if ($row) {
            return $this
                ->createEntity()
                ->loadFromArray($row)
            ;
        }

        return null;
    }

    /**
     * Convert array to array of entities.
     *
     * @param  array $rows
     * @return ModelInterface[]
     */
    protected function rowsToObjects($rows)
    {
        return array_map(function ($row) {
            return $this->rowToObject($row);
        }, $rows);
    }

    /**
     * @return string
     */
    protected function buildUpdateSql($fields, $where)
    {
        return sprintf(
            "UPDATE `%s` SET %s WHERE %s",
            $this->getTable(),
            $this->buildKeyValuePairs($fields, ','),
            $this->buildKeyValuePairs($where, ' and ')
        );
    }

    /**
     * @param  array $where
     * @return string
     */
    protected function buildKeyValuePairs($where, $delimiter)
    {
        return implode(array_map(function ($field) {
            return sprintf('`%s`=:%s', $field, $field);
        }, array_keys($where)), $delimiter);
    }

    /**
     * @return string
     */
    protected function buildInsertSql($fields)
    {
        $fields_names = array_keys($fields);
        return sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $this->getTable(),
            "`" . implode($fields_names, "`, `") . "`",
            ":" . implode($fields_names, ", :")
        );
    }

    /**
     * Return human-readable representation
     * of where array keys and values.
     * Used in Exceptions.
     *
     * @param  array $where
     * @return string
     */
    private function describeWhereFields($where)
    {
        return implode(', ', array_map(function ($v, $k) {
            return sprintf("%s='%s'", $k, $v);
        }, $where, array_keys($where)));
    }
}
