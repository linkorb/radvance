# Spaces and Permissions

If you want to implement the space and permission management UIs on your app, Radvance can do that for you.

## How it works
There is no need to make routes, controllers or templates, but you do need to implement schema, repositories and models.

The following example code assumes the __Library__ as the space.

### Data structure (schema.xml)
Simply replace all the __library__ with your own space name.
```xml
<table name="library">
    <column name="id" type="integer" unsigned="true" autoincrement="true" />
    <column name="name" type="string" length="64" />
    <column name="account_name" type="string" length="64" />
    <column name="description" type="text" notnull="false"/>
    <column name="created_at" type="integer" notnull="false" />
    <column name="deleted_at" type="integer" notnull="false" />

    <index name="primary" primary="true" columns="id" />
    <index name="unique_account_name_library_name" unique="true" doc="unique account and library" columns="name, account_name" />
</table>
<table name="permission">
    <column name="id" type="integer" autoincrement="true"  unsigned="true" />
    <column name="username" type="string" length="64"/>
    <column name="library_id" type="integer" unsigned="true" />

    <index name="primary" primary="true" columns="id" />
    <index name="username" primary="false" columns="username" />
    <index name="unique_username_library_id" unique="true" doc="unique username and library id" columns="username, library_id" />
</table>
```
Don't forget to run __dbtk-schema-loader schema:load__

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
