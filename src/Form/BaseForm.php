<?php

namespace Radvance\Form;

use Radvance\Model\BaseModel;
use Radvance\Framework\BaseWebApplication as Application;
use Symfony\Component\HttpFoundation\Request;
use Radvance\Exception\BadMethodCallException;
use Doctrine\Common\Inflector\Inflector;

class BaseForm
{
    protected $defaults;
    protected $builder;
    protected $form;
    protected $application;
    protected $request;
    protected $submitted = false;

    public function __construct(Application $app, Request $request)
    {
        $this->application = $app;
        $this->request = $request;
    }

    public function setEntity(BaseModel $entity)
    {
        $this->entity = $entity;
        $this->setDefaults();

        return $this;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function isSubmitted()
    {
        return $this->submitted;
    }

    public function dispatch()
    {
        $this->builder = $this->application['form.factory']->createBuilder('form', $this->defaults);
        $this->addFields();

        // handle submission
        $this->form->handleRequest($this->request);
        if ($this->form->isValid()) {
            $data = $this->form->getData();

            foreach ($data as $d => $value) {
                $method = 'set' . Inflector::camelize($d);
                // if (!method_exists($this->entity, $method)) {
                //     throw new BadMethodCallException('No matching method to handle '.$d);
                // }
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d');
                }
                $this->entity->$method($value);
            }

            $formattedTime = (new \DateTime())->format('Y-m-d');
            // set created at for new entity
            if (!$this->entity->getId()) {
                try {
                    $this->entity->setCreatedAt($formattedTime);
                } catch (\Exception $e) {
                }
            }

            // set updated at
            try {
                $this->entity->setUpdatedAt($formattedTime);
            } catch (\Exception $e) {
            }

            $this->submitted = true;
        }

        return $this;
    }

    public function getView()
    {
        return $this->form->createView();
    }

    protected function fields()
    {
        throw new BadMethodCallException('fields method must be implemented in your form class');
    }

    private function addFields()
    {
        $fields = $this->fields();
        foreach ($fields as $value) {
            $this->builder->add($value[0], $value[1], (isset($value[2])?$value[2]:null));
        }

        $this->form = $this->builder->getForm();
    }

    private function setDefaults()
    {
        $defaults = [];
        $fields = $this->fields();
        foreach ($fields as $value) {
            $method = 'get' . Inflector::camelize($value[0]);
            switch ($value[1]) {
                case 'date':
                case 'datetime':
                    $defaults[$value[0]] = new \DateTime($this->entity->$method());
                    break;
                default:
                    $defaults[$value[0]] = $this->entity->$method();
                    break;
            }
        }

        $this->defaults = $defaults;

        return $this;
    }
}
