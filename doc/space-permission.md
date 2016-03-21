# Spaces and Permissions

If you want to implement the space and permission management UIs on your app, Radvance can do that for you.

## How it works
There is no need to make routes, controllers or templates, but you do need to create the simple repositories and models.

### Repositories
The space and permission PDO repositories are needed. In the example, we use __Library__ as the space. So please replace __Library__ to your own needs.
```php
<?php

namespace Herald\Server\Repository;

use Radvance\Repository\PdoSpaceRepository;
use Radvance\Repository\SpaceRepositoryInterface;

class PdoLibraryRepository extends PdoSpaceRepository implements SpaceRepositoryInterface
{
    // the model class of space
    protected $modelClassName = '\Herald\Server\Model\Library';
    // the name of the space, to be used in UI
    protected $nameOfSpace = 'Library';
    // the plural name of the space, to be used in UI. Optional
    protected $nameOfSpacePlural = 'Libraries';
    // the permission table name.
    protected $permissionTableName = 'permission';
    // the foreign key name in the permission table that links to space
    protected $permissionTableForeignKeyName = 'library_id';
}

```
```php
<?php

namespace Herald\Server\Repository;

use Radvance\Repository\PermissionRepositoryInterface;
use Radvance\Repository\PdoPermissionRepository as BaseRepository;
use Herald\Server\Model\Permission;

class PdoPermissionRepository extends BaseRepository implements PermissionRepositoryInterface
{
    // the model class of permission
    protected $modelClassName = '\Herald\Server\Model\Permission';
    // the foreign key name in the permission table that links to space
    protected $spaceTableForeignKeyName = 'library_id';
}

```
The Radvance application can find these repositories and treat them differently.

### Models
The space and permission models are needed. In the example, we use __Library__ as the space. So please replace __Library__ to your own needs.
```php
<?php

namespace Herald\Server\Model;

use Radvance\Model\Space;
use Radvance\Model\SpaceInterface;

class Library extends Space implements SpaceInterface
{
    // nothing needs to be implemented
}

```
```php
<?php

namespace Herald\Server\Model;

use Radvance\Model\Permission as BasePermission;
use Radvance\Model\PermissionInterface;

class Permission extends BasePermission implements PermissionInterface
{
    // only need to put the permission-to-space foreignkey property here, nothing else
    protected $library_id;
}

```
That's all you have to do. The related routes are automatically included by Radvance.
