# Templates

Radvance integrates the Twig template engine.

## Predefined variables

The following variables are automatically defined by Radvance in all templates:

### current_user

If the current user is logged in, the `current_user` variable is set to an
instance of `Symfony\Component\Security\Core\User\AdvancedUserInterface`.

If the current user is not logged in, the `current_user` variable is undefined.

### accountName

If the current route contains a GET-parameter called 'accountName', it will be available
in the twig template.

### userbaseUrl

This variable is passed on from `parameters.yaml` if it's defined there.

### app_name

This variable is passed on from `parameters.yaml` if it's defined there.
