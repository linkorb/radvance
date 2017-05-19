Module support
======

## Installing a module
Installing a module is easy:
* require the module via composer
* run `composer update`
* run `vendor/bin/radvance code:update`
* create links in templates to the module routes

Example: https://github.com/linkorb/wiki-module

Radvance scans the installed packages via composer and identify modules (`linkorb/[module-name]-module`). Then automatically register them.

## Module directory structure
The module should have 2 top level directories:
```
- src
-- Controller
-- Model
-- Repository
- res
-- css
-- js
-- routes
-- templates
```

## Repositories
The module's repositories are automatically loaded.
One thing to keep in mind is to add a property in the module repository class which has foreign key to the space.
```
protected $spaceForeignKey = 'space_id';
```
This is useful for the filter (on space) to be applied.

## Templates
Templates are automatically available.

## Routes
Routes are automatically registered. As a convension, the module route names are prefixed with `module_`.

## Schema
Schema xml files are applied when executing `vendor/bin/radvance schema:update`, which is part of `vendor/bin/radvance code:update`.

## Assets
Assets, e.g. javascript and css, are symlinked from the module to the `web/modules` directory in the app.
Note: the app's base template should include a block for javascript
```
{% block scripts %}{% endblock %}
```
