<?php

namespace Radvance\Behat;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Mink\Element\NodeElement;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Silex\Application;

/**
 * Based on sylius project's context.
 */
abstract class DefaultContext extends RawMinkContext implements Context
{
    /**
     * Actions.
     *
     * @var array
     */
    protected $actions = array(
        'viewing'  => 'view',
        'creation' => 'add',
        'editing'  => 'edit'
    );

    /**
     * @var Application
     */
    protected static $app;

    /**
     * @static
     * @BeforeSuite
     */
    public static function bootstrapSilex()
    {
        if (!self::$app) {
            self::$app = require __DIR__.'/../../../../../app/bootstrap.php';
        }
        return self::$app;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplication()
    {
        return self::$app;
    }

    /**
     * Find one resource by name.
     *
     * @param string $type
     * @param string $name
     *
     * @return object
     */
    protected function findOneByName($resource, $name)
    {
        return $this->findOneBy($resource, array('name' => trim($name)));
    }

    /**
     * Find one resource by criteria.
     *
     * @param string $type
     * @param array  $criteria
     *
     * @return object
     *
     * @throws \InvalidArgumentException
     */
    protected function findOneBy($resource, array $criteria)
    {
        $entity = $this
            ->getRepository($resource)
            ->findOneBy($criteria)
        ;

        if (null === $entity) {
            throw new \InvalidArgumentException(sprintf(
                '%s for criteria "%s" was not found.',
                str_replace('_', ' ', ucfirst($type)),
                serialize($criteria)
            ));
        }

        return $entity;
    }

    /**
     * Get repository by resource name.
     *
     * @param string $resource
     *
     * @return RepositoryInterface
     */
    protected function getRepository($resource)
    {
        $resource = preg_replace('/\s/', '_', $resource);
        return $this->getApplication()->getRepository($resource);
    }

    protected function getUrlGenerator()
    {
        return $this->getApplication()['url_generator'];
    }

    /**
     * Get current user instance.
     *
     * @return null|UserInterface
     *
     * @throws \Exception
     */
    protected function getUser()
    {
        $token = $this->getSecurityContext()->getToken();

        if (null === $token) {
            throw new \Exception('No token found in security context.');
        }

        return $token->getUser();
    }

    /**
     * Get security context.
     *
     * @return SecurityContextInterface
     */
    // protected function getSecurityContext()
    // {
    //     return $this->getApplication()['security.context'];
    // }

    /**
     * Generate url.
     *
     * @param string  $route
     * @param array   $parameters
     * @param Boolean $absolute
     *
     * @return string
     */
    protected function generateUrl($route, array $parameters = array(), $absolute = false)
    {
        return $this->locatePath($this->getUrlGenerator()->generate($route, $parameters, $absolute));
    }

    /**
     * Presses button with specified id|name|title|alt|value.
     */
    protected function pressButton($button)
    {
        $this->getSession()->getPage()->pressButton($this->fixStepArgument($button));
    }

    /**
     * Clicks link with specified id|title|alt|text.
     */
    protected function clickLink($link)
    {
        $this->getSession()->getPage()->clickLink($this->fixStepArgument($link));
    }

    /**
     * Fills in form field with specified id|name|label|value.
     */
    protected function fillField($field, $value)
    {
        $this->getSession()->getPage()->fillField($this->fixStepArgument($field), $this->fixStepArgument($value));
    }

    /**
     * Selects option in select field with specified id|name|label|value.
     */
    public function selectOption($select, $option)
    {
        $this->getSession()->getPage()->selectFieldOption($this->fixStepArgument($select), $this->fixStepArgument($option));
    }


    /**
     * Returns fixed step argument (with \\" replaced back to ").
     *
     * @param string $argument
     *
     * @return string
     */
    protected function fixStepArgument($argument)
    {
        return str_replace('\\"', '"', $argument);
    }

    /**
     * @param NodeElement $table
     * @param string $columnName
     *
     * @return integer
     *
     * @throws \Exception If column was not found
     */
    protected function getColumnIndex(NodeElement $table, $columnName)
    {
        $rows = $table->findAll('css', 'tr');

        if (!isset($rows[0])) {
            throw new \Exception("There are no rows!");
        }

        /** @var NodeElement $firstRow */
        $firstRow = $rows[0];
        $columns = $firstRow->findAll('css', 'th,td');
        foreach ($columns as $index => $column) {
            /** @var NodeElement $column */
            if (0 === stripos($column->getText(), $columnName)) {
                return $index;
            }
        }

        throw new \Exception(sprintf('Column with name "%s" not found!', $columnName));
    }

    /**
     * @param NodeElement $table
     * @param array $fields
     *
     * @return NodeElement|null
     *
     * @throws \Exception If column was not found
     */
    protected function getRowWithFields(NodeElement $table, array $fields)
    {
        $foundRows = $this->getRowsWithFields($table, $fields, true);

        if (empty($foundRows)) {
            return null;
        }

        return current($foundRows);
    }

    /**
     * @param NodeElement $table
     * @param array $fields
     * @param boolean $onlyFirstOccurence
     *
     * @return NodeElement[]
     *
     * @throws \Exception If columns or rows were not found
     */
    protected function getRowsWithFields(NodeElement $table, array $fields, $onlyFirstOccurence = false)
    {
        $rows = $table->findAll('css', 'tr');

        if (!isset($rows[0])) {
            throw new \Exception("There are no rows!");
        }

        $fields = $this->replaceColumnNamesWithColumnIds($table, $fields);

        $foundRows = array();

        /** @var NodeElement[] $rows */
        $rows = $table->findAll('css', 'tr');
        foreach ($rows as $row) {
            $found = true;

            /** @var NodeElement[] $columns */
            $columns = $row->findAll('css', 'th,td');
            foreach ($fields as $index => $searchedValue) {
                if (!isset($columns[$index])) {
                    throw new \InvalidArgumentException(sprintf('There is no column with index %d', $index));
                }

                $containing = false;
                $searchedValue = trim($searchedValue);
                if (0 === strpos($searchedValue, '%') && (strlen($searchedValue) - 1) === strrpos($searchedValue, '%')) {
                    $searchedValue = substr($searchedValue, 1, strlen($searchedValue) - 2);
                    $containing = true;
                }

                $position = stripos(trim($columns[$index]->getText()), $searchedValue);
                if (($containing && false === $position) || (!$containing && 0 !== $position)) {
                    $found = false;

                    break;
                }
            }

            if ($found) {
                $foundRows[] = $row;

                if ($onlyFirstOccurence) {
                    break;
                }
            }
        }

        return $foundRows;
    }

    /**
     * @param NodeElement $table
     * @param string[] $fields
     *
     * @return string[]
     *
     * @throws \Exception
     */
    protected function replaceColumnNamesWithColumnIds(NodeElement $table, array $fields)
    {
        $replacedFields = array();
        foreach ($fields as $columnName => $expectedValue) {
            $columnIndex = $this->getColumnIndex($table, $columnName);

            $replacedFields[$columnIndex] = $expectedValue;
        }

        return $replacedFields;
    }

    protected function buildRoute($resource, $action)
    {
        if (isset($this->actions[$action])) {
            $action = $this->actions[$action];
        }

        $resource = str_replace(' ', '_', $resource);
        return sprintf('%s_%s', $resource, $action);
    }
}
