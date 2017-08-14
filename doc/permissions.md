Permissions
===========

Radvance is using the standard security functionality from silex an symfony:

* https://silex.symfony.com/doc/2.0/providers/security.html
* https://symfony.com/doc/current/components/security.html

## Checking for permissions in code

In the code, for example in a controller, you can simply call:

```php
$auth = $app['security.authorization_checker'];
if (!$auth->isGranted('ROLE_BLOG_EDITOR')) {
    throw new Exception("Access denied!");
}
```

Often all the methods in a Controller class all require the same permissions. So you can simply check
the permission in the Controller's `__construct()` method:

```php
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BlogController
{
    public function __construct(AuthorizationChecker $auth)
    {
        if (!$auth->isGranted('ROLE_BLOG_EDITOR')) {
            throw new AccessDeniedException();
        }
    }

    public function indexAction()
    {
        // no need to check permissions here, it's already checked in the controller :-)
    }
}
```

## Checking for permissions in templates

You can use the standard `is_granted` function in Twig templates like this:

```html
<h1>Blog</h1>
{% if is_granted('ROLE_BLOG_PUBLISHER')}
    <a href="/blogs/{blogId}/publish">Publish</a>
{% endif %}
```

Always remember to check permissions in the controllers or firewall too! Just hiding a link or button
does not secure the route on it's own.

## RoleProvider

How does your app know which ROLE(s) a user has?

You can support this by implementing the `Radvance\Security\RoleProviderInterface`.
Using such a class, you can tell the security component which roles a user has based on their username.

You can store the permissions in a database, file, or config parameter for example.

Here's a simple example:

```php
namespace MyApp\Security;

use Radvance\Security\RoleProviderInterface

class MyRoleProvider implements RoleProviderInterface
{
    protected $repo;
    public function __construct($permissionRepo, $superusers)
    {
        $this->repo = $permissionRepo;
    }

    /*
     * This function should return an array of role names based on the username
     */
    public function getUserRoles($username)
    {
        $roles = [];
        foreach ($this->permissionRepo->findByUsername($username) as $permission) {
            $roles[] = $permission->getRole();
        }
        return $roles;
    }
}
```

Once you have created your `RoleProvider` implementation, you need to register it in the Application

The easiest way is to implement the method 'configureRoleProvider' on your Application:

```php
class Application extends BaseWebApplication
{
    public function configureRoleProvider()
    {
        $permissionRepo = $this->getRepository('permission');
        $this['security.role_provider'] = new MyRoleProvider($permissionRepo);
    }
}
```
