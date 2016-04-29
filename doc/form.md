# Automatic form handling

Radvance can automate form handling in your controllers.

## Make the form class
```php
<?php

namespace StorePanel\Form;

use Radvance\Form\BaseForm;
use Symfony\Component\Validator\Constraints as Assert;

class CashType extends BaseForm
{
    protected function fields()
    {
        // the array members are the same as Symfony form add method parameters
        return [
            ['date', 'date', ['required' => true]],
            [
                'content',
                'textarea',
                [
                    'required' => false,
                    'constraints' => array(new Assert\NotBlank(
                        ['message' => 'Content cannot be empty']
                    )),
                ],
            ],
        ];
    }
}

```

## Use the form in controller
```php
<?php

// ...

protected function getEditForm(Application $app, Request $request, $id = null)
{
    $repo = $app->getRepository($this->getModelName());
    $entity = $repo->findOrCreate($id);
    if (!$id) {
        $entity->setstoreId((int) $app['oStore']->getId());
    }

    // create the form
    $form = new CashTypeForm($app, $request);
    // set the entity and then use it
    $form->setEntity($entity)->dispatch();

    // if it's a form submission save the entity
    if ($form->isSubmitted()) {
        $entity = $form->getEntity();
        if ($repo->persist($entity)) {
            return $app->redirect($app['url_generator']->generate(
                sprintf('%s_index', $this->getModelName()),
                array(
                    'accountName' => $request->get('accountName'),
                    'storeName' => $request->get('storeName'),
                )
            ));
        }
    }

    return $this->renderEdit($app, array(
        'form' => $form->getView(),
        'entity' => $entity,
        'add' => !$id,
    ));
}
```
