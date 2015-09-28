<?php

namespace LinkORB\Framework\Behat;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Based on sylius project's context.
 */
class FixtureContext extends DefaultContext
{
    /**
     * @Given /^there are the following ([^"]*):$/
     */
    public function thereAreFollowingResources($resource, TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $this->thereIsFollowingResource($resource, $data);
        }
    }

    /**
     * @Given /^there is the following "([^"]*)":$/
     */
    public function thereIsFollowingResource($resource, $additionalData)
    {
        if ($additionalData instanceof TableNode) {
            $additionalData = $additionalData->getHash();
        }

        $repository = $this->getRepository($resource);
        $entity = $repository->createEntity();

        if (count($additionalData) > 0) {
            $this->setDataToObject($entity, $additionalData);
        }

        return $repository->persist($entity);
    }

    /**
     * @Given /^there are no ([^"]*)$/
     */
    public function thereAreNoResources($resource)
    {
        $repository = $this->getRepository($resource);
        $resources = $repository->findAll();

        foreach ($resources as $resource) {
            $repository->remove($resource);
        }
    }

    /**
     * @Given /^there is ([^"]*) table truncated$/
     * @Given /^there is empty ([^"]*) table$/
     */
    public function thereAreTruncatedResource($resource)
    {
        $this->getRepository($resource)->truncate();
    }

    /**
     * @Given /^there are (.+) tables truncated$/
     */
    public function thereAreTruncatedResources($resources)
    {
        $resources = explode(',', $resources);
        foreach ($resources as $resource) {
            $this->thereAreTruncatedResource(trim($resource));
        }
    }

    /**
     * @Given /^([^""]*) with following data should be created:$/
     */
    public function objectWithFollowingDataShouldBeCreated($type, TableNode $table)
    {
        $accessor = new PropertyAccessor();

        $data = $table->getRowsHash();
        $type = str_replace(' ', '_', trim($type));

        $object = $this->findOneByName($type, $data['name']);
        foreach ($data as $property => $value) {
            $objectValue = $accessor->getValue($object, $property);
            if (is_array($objectValue)) {
                $objectValue = implode(',', $objectValue);
            }

            if ($objectValue !== $value) {
                throw new \Exception(sprintf(
                    '%s object::%s has "%s" value but "%s" expected',
                    $type,
                    $property,
                    $objectValue,
                    is_array($value) ? implode(',', $value) : $value)
                );
            }
        }
    }

    /**
     * @Given /^I have deleted the ([^"]*) "([^""]*)"/
     */
    public function haveDeleted($resource, $name)
    {
        $this->iDeletedResourceWithGivenParameter($resource, 'name', $name);
    }

    /**
     * @Given /^I have deleted the ([^"]*) with ([^""]*) "([^""]*)"/
     */
    public function iDeletedResourceWithGivenParameter($resource, $property, $value)
    {
        $repository = $this->getRepository($resource);
        $entity = $repository->findOneBy(array($property => $value));
        $repository->remove($entity);
    }

    /**
     * Set data to an object.
     *
     * @param $object
     * @param $data
     */
    protected function setDataToObject($object, array $data)
    {
        foreach ($data as $property => $value) {
            if (1 === preg_match('/date/', strtolower($property))) {
                $value = new \DateTime($value);
            }

            $propertyName = ucfirst($property);
            if (false !== strpos(' ', $property)) {
                $propertyName = '';
                $propertyParts = explode(' ', $property);

                foreach ($propertyParts as $part) {
                    $part = ucfirst($part);
                    $propertyName .= $part;
                }
            }

            $method = 'set'.$propertyName;
            if (method_exists($object, $method)) {
                $object->{'set'.$propertyName}($value);
            }
        }
    }
}
