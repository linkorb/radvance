<?php

namespace Radvance\Repository;

use Radvance\Model\ModelInterface;
use Exception;
use PDO;
use Doctrine\Common\Inflector\Inflector;
use Xuid\Xuid;

abstract class BaseRepository
{
    private $table;
    protected $pdo;
    protected $filter;
    protected $modelClassName;

    public function __construct(PDO $pdo, $table = null)
    {
        if (is_null($table)) {
            $table = get_class($this);
            $table = explode('\\', $table);
            $table = end($table);
            $table = substr($table, strlen('Pdo'), -strlen('Repository'));

            $table = Inflector::tableize($table);
        }

        $this->table = $table;
        $this->pdo = $pdo;
    }
    
    public function createEntity()
    {
        $name = $this->getModelClassName();
        return $name::createNew();
    }
    
    public function getModelClassName()
    {
        if (!$this->modelClassName) {
            // If the modelClassName is not explicitly defined in the subclass,
            // make an educated guess based on the repository class name
            $name = get_class($this);
            $name = str_replace('\\Repository\\', '\\Model\\', $name);
            
            $name = str_replace('\\Pdo', '\\', $name);
            $name = substr($name, 0, -strlen('Repository'));
            $this->modelClassName = $name;
        }
        return $this->modelClassName;
    }
    
    public function setPdo(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function setFilter($filter)
    {
        // Only add filters for properties that are present on this repository's model class.
        $res = [];
        foreach ($filter as $key => $value) {
            if (property_exists($this->getModelClassName(), $key)) {
                $res[$key] = $value;
            }
        }
        if (count($res)>0) {
            $this->filter = $res;
        }
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
        return $this->findOneBy(array('id' => $id));
    }

    /**
     * {@inheritdoc}
     */
    public function findOrCreate($id)
    {
        $entity = $this->findOneOrNullBy(array('id' => $id));
        if (!$entity) {
            $entity = $this->createEntity();
            if ($this->filter) {
                foreach ($this->filter as $key => $value) {
                    if (property_exists($entity, $key)) {
                        $propertyName = Inflector::camelize($key);
                        $setter = sprintf('set%s', ucfirst($propertyName));
                        $entity->$setter($value);
                    }
                }
            }
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
    public function findAll()
    {
        return $this->findBy([]);
    }
    
    public function fetchRows($where, $limit = null)
    {
        if ($this->filter) {
            $where = array_merge($where, $this->filter);
        }
        $sql = sprintf('SELECT * FROM `%s` WHERE true', $this->getTable());
        
        if (count($where)>0) {
            $sql .= sprintf(' AND %s', $this->buildKeyValuePairs($where, ' and '));
        };
        
        if ($this->getSoftDeleteColumnName()) {
            $sql .= sprintf(' AND %s is null', $this->getSoftDeleteColumnName());
        }
        
        if ($limit>0) {
            $sql .= sprintf(' LIMIT %d', $limit);
        }

        $statement = $this->pdo->prepare($sql);
        $where = $this->flattenValues($where);
        $statement->execute($where);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    /**
     * {@inheritdoc}
     */
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


    /**
     * {@inheritdoc}
     */
    public function persist(ModelInterface $entity)
    {
        // XUID
        if (property_exists($entity, 'xuid')) {
            $xuid = $entity->getXuid();
            if (!$xuid) {
                $xuid = new Xuid();
                $entity->setXuid($xuid->getXuid());
            }
        }
        $fields = $entity->toArray();
        unset($fields['id']);

        if ($entity->getId()) {
            $where = array(
                'id' => $entity->getId(),
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
     * @param array $fields
     *
     * @return array
     */
    protected function prepareFieldsValues($fields)
    {
        return array_map(function ($value) {
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d H:i:s');
            }

            return $value;
        }, $fields);
    }
    
    protected function getSoftDeleteColumnName()
    {
        if (property_exists($this->getModelClassName(), 'deleted_at')) {
            return 'deleted_at';
        }
        return null; // no soft-delete field
    }

    /**
     * {@inheritdoc}
     */
    public function remove(ModelInterface $entity)
    {
        if ($this->getSoftDeleteColumnName()) {
            // Yes, use soft-delete
            $propertyName = Inflector::camelize($this->getSoftDeleteColumnName());
            $setter = sprintf('set%s', ucfirst($propertyName));
            $entity->$setter(date('Y-m-d H:i:s'));
            $this->persist($entity);
        } else {
            // No, use hard-delete
            $statement = $this->pdo->prepare(sprintf(
                'DELETE FROM `%s` WHERE id=:id',
                $this->getTable()
            ));
            $statement->execute(array('id' => $entity->getId()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function truncate()
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
        $this->pdo->exec(sprintf(
            'TRUNCATE `%s`',
            $this->getTable()
        ));
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Convert array to entity.
     *
     * @param array $row
     *
     * @return ModelInterface
     */
    protected function rowToObject($row)
    {
        if ($this->returnDataType == 'array') {
            return $row;
        }

        if ($row) {
            return $this
                ->createEntity()
                ->loadFromArray($row)
            ;
        }

        return;
    }

    /**
     * Convert array to array of entities.
     *
     * @param array $rows
     *
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
            'UPDATE `%s` SET %s WHERE %s',
            $this->getTable(),
            $this->buildKeyValuePairs($fields, ',', false),
            $this->buildKeyValuePairs($where, ' and ', false)
        );
    }

    /**
     * @param array $where
     *
     * @return string
     */
    protected function buildKeyValuePairs($where, $delimiter, $isNullPatch = true)
    {
        return implode(array_map(function ($field, $value) use ($isNullPatch) {
            if (is_array($value)) {
                # Transform [field=>[0=>a,1=>b,2=>c]] to 'field in (:field_0, :field_1, :field_2)'
                return sprintf('`%s` in (%s)', $field, implode(array_map(function ($index) use ($field) {
                    return sprintf(':%s_%s', $field, $index);
                }, array_keys($value)), ', '));
            }

            // return sprintf('`%s`=:%s', $field, $field);
            // IS NULL
            if ($isNullPatch && null === $value) {
                return sprintf('`%s` IS NULL', $field);
            } else {
                return sprintf('`%s`=:%s', $field, $field);
            }
        }, array_keys($where), $where), $delimiter);
    }

    protected function flattenValues($fields)
    {
        $result = array();
        array_walk($fields, function ($value, $field) use (&$result) {
            // IS NULL
            if (null === $value) {
                return;
            }

            if (is_array($value)) {
                # Transform [field=>[0=>a,1=>b,2=>c]] to [field_0=>a, field_1=>b, field_2=>c)
                foreach ($value as $index => $index_value) {
                    $index_key = sprintf('%s_%s', $field, $index);
                    $result[$index_key] = $index_value;
                }
            } else {
                $result[$field] = $value;
            }
        });

        return $result;
    }

    /**
     * @return string
     */
    protected function buildInsertSql($fields)
    {
        $fields_names = array_keys($fields);

        return sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $this->getTable(),
            '`'.implode($fields_names, '`, `').'`',
            ':'.implode($fields_names, ', :')
        );
    }

    /**
     * Return human-readable representation
     * of where array keys and values.
     * Used in Exceptions.
     *
     * @param array $where
     *
     * @return string
     */
    private function describeWhereFields($where)
    {
        return implode(', ', array_map(function ($v, $k) {
            return sprintf("%s='%s'", $k, $v);
        }, $where, array_keys($where)));
    }

    
    public function getByLibraryId($libraryId)
    {
        throw new RuntimeException(
            "getByLibraryId should not be in the BaseRepository. Implement it in subclass if you need it."
        );
    }

    protected $returnDataType = 'object';

    public function getReturnDataType()
    {
        return $this->returnDataType;
    }

    public function setReturnDataType($returnDataType)
    {
        $this->returnDataType = $returnDataType;

        return $this;
    }
}
