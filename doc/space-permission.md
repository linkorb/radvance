# Spaces and Permissions

If you want to implement the space and permission management UIs on your app, Radvance can do that for you.

## How it works
There is no need to make controllers and templates, but you do need to create the routes, repositories and models.

### Routes
Simply include the following routes in your routes.yml without any changes.
```ymal
# dashboard

dashboard:
    pattern: /dashboard
    defaults: { _controller: Radvance\Controller\DashboardController::indexAction }

# library routes

space_index:
    pattern: /{accountName}
    defaults: { _controller: Radvance\Controller\SpaceController::indexAction }
space_add:
    pattern: /{accountName}/addlibrary
    defaults: { _controller: Radvance\Controller\SpaceController::addAction }
space_view:
    pattern: /{accountName}/{spaceName}
    defaults: { _controller: Radvance\Controller\SpaceController::viewAction }
space_edit:
    pattern: /{accountName}/{spaceName}/edit
    defaults: { _controller: Radvance\Controller\SpaceController::editAction }
space_delete:
    pattern: /{accountName}/{spaceName}/delete
    defaults: { _controller: Radvance\Controller\SpaceController::deleteAction }

# Permission routes

permission_index:
    pattern: /{accountName}/{spaceName}/permissions
    defaults: { _controller: Radvance\Controller\PermissionController::indexAction }
permission_add:
    pattern: /{accountName}/{spaceName}/permissions/add
    defaults: { _controller: Radvance\Controller\PermissionController::addAction }
    requirements: { _method: post }
permission_delete:
    pattern: /{accountName}/{spaceName}/permissions/{permissionId}/delete
    defaults: { _controller: Radvance\Controller\PermissionController::deleteAction }
```

### Repositories
The space and permission pdo repositories are needed:
```php
<?php

namespace Herald\Server\Repository;

use Radvance\Repository\PdoSpaceRepository;
use Radvance\Repository\SpaceRepositoryInterface;
use Herald\Server\Model\Library;

class PdoLibraryRepository extends PdoSpaceRepository implements SpaceRepositoryInterface
{
    # implement the methdos here
}
```
```php
<?php

namespace Herald\Server\Repository;

use Radvance\Repository\PermissionRepositoryInterface;
use Radvance\Repository\BaseRepository;
use Herald\Server\Model\Permission;

class PdoPermissionRepository extends BaseRepository implements PermissionRepositoryInterface
{
    # implement the methods here
}

```
The Radvance application can find these repositories and treat them differently.

### Models
The space and permission models are needed:
```php
<?php

namespace Herald\Server\Model;

use Radvance\Model\Space;
use Radvance\Model\SpaceInterface;

class Library extends Space implements SpaceInterface
{
    public function getName()
    {
        return $this->name;
    }

    public function getAccountName()
    {
        return $this->account_name;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
```
```php
<?php

namespace Herald\Server\Model;

use Radvance\Model\BaseModel;
use Radvance\Model\PermissionInterface;

class Permission extends BaseModel implements PermissionInterface
{
    protected $id;
    protected $username;
    protected $library_id;

    public function getUsername()
    {
        return $this->username;
    }
}

```
