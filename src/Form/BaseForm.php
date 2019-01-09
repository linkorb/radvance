<?php

namespace Radvance\Form;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Doctrine\Common\Inflector\Inflector;
use Radvance\Model\BaseModel;
use Radvance\Exception\BadMethodCallException;

class BaseForm
{
    protected $defaults;
    protected $builder;
    protected $form;
    protected $formFactory;
    protected $request;
    protected $submitted = false;

    public function __construct(FormFactory $formFactory, Request $request = null)
    {
        $this->formFactory = $formFactory;
        $this->request = $request ?: Request::createFromGlobals();
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

    public function getForm()
    {
        return $this->form;
    }

    public function isSubmitted()
    {
        return $this->submitted;
    }

    public function dispatch()
    {
        $this->builder = $this->formFactory->createBuilder(FormType::class, $this->defaults);
        $this->addFields();

        // handle submission
        $this->form->handleRequest($this->request);
        if ($this->form->isSubmitted() && $this->form->isValid()) {
            $data = $this->form->getData();

            foreach ($data as $d => $value) {
                $method = 'set'.Inflector::camelize($d);
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
                } catch (\BadMethodCallException $e) {
                }
            }

            // set updated at
            try {
                $this->entity->setUpdatedAt($formattedTime);
            } catch (\BadMethodCallException $e) {
            }

            $this->submitted = true;
        }

        return $this;
    }

    public function getView()
    {
        return $this->form->createView();
    }

    protected function buildForm()
    {
        throw new BadMethodCallException('The buildForm method must be implemented in your form class');
    }

    private function addFields()
    {
        $fields = $this->buildForm();
        foreach ($fields as $value) {
            $options = isset($value[2]) ? $value[2] : [];
            foreach ($options as $k => $v) {
                if ('read_only' == $k) {
                    unset($options['read_only']);
                    $options['attr']['readonly'] = true;
                }
                if ('empty_value' == $k) {
                    $options['placeholder'] = $options['empty_value'];
                    unset($options['empty_value']);
                }
                if ('choices' == $k) {
                    $options['choices'] = array_flip($options['choices']);
                }
            }
            switch ($value[1]) {
            case 'text':
                $type = \Symfony\Component\Form\Extension\Core\Type\TextType::class;
                break;
            case 'textarea':
                $type = \Symfony\Component\Form\Extension\Core\Type\TextareaType::class;
                break;
            case 'email':
                $type = \Symfony\Component\Form\Extension\Core\Type\EmailType::class;
                break;
            case 'date':
                $type = \Symfony\Component\Form\Extension\Core\Type\DateType::class;
                break;
            case 'password':
                $type = \Symfony\Component\Form\Extension\Core\Type\PasswordType::class;
                break;
            case 'choice':
                $type = \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class;
                break;
            case 'number':
                $type = \Symfony\Component\Form\Extension\Core\Type\NumberType::class;
                break;
            case 'integer':
                $type = \Symfony\Component\Form\Extension\Core\Type\IntegerType::class;
                break;
            case 'url':
                $type = \Symfony\Component\Form\Extension\Core\Type\UrlType::class;
                break;
            case 'money':
                $type = \Symfony\Component\Form\Extension\Core\Type\MoneyType::class;
                break;
            default:
                $type = $value[1];
            }
            $this->builder->add($value[0], $type, $options);
        }

        $this->form = $this->builder->getForm();
    }

    private function setDefaults()
    {
        $defaults = [];
        $fields = $this->buildForm();
        foreach ($fields as $value) {
            $method = 'get'.Inflector::camelize($value[0]);
            switch ($value[1]) {
                case 'date':
                case 'datetime':
                    // $defaults[$value[0]] = new \DateTime($this->entity->$method());
                    $defaults[$value[0]] = $this->entity->$method() ? new \DateTime($this->entity->$method()) : null;
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
