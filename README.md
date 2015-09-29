# Radvance Framework

Warning! This framework in WIP state. Don't use it before release, please.

## TODO

- [x] Extract `BaseConsoleApplication` from `BaseApplication`
- [x] Extract `BaseWebApplication` from `BaseApplication`
- [ ] Release old skeleton as `1.0.0`
- [ ] Make new skeleton based on framework and tag it as `2.0.0`
- [ ] Several security providers for test-ready `parameters.yml.dist` on localhost without any real passwords
- [ ] Exceptions and errors reporting / env-based verbosity
- [ ] Error messages on constraints fails
- [x] Publish framework as separate lib

## Why?

- To minimize copy-paste and monkey-coding
- To **centralized** improvements injection to all our projects
- To decrease codebse size and increase code quality
- To implement best practices on it
- To decrease cost of development

## How?

### Application

You should extend your application from `BaseWebApplication` if you use controllers or `BaseConsoleApplication` otherwise.

```php
# src/Application.php

namespace ExampleApp;

use Radvance\BaseWebApplication;
use Radvance\FrameworkApplicationInterface;

class Application extends BaseWebApplication implements FrameworkApplicationInterface
{
    public function getRootPath()
    {
        return realpath(__DIR__.'/../');
    }
}
```

### Models

#### Name convention

Each model need to be named with CamelCase notation.
For example:

- `Thing` will be stored at `thing` database table, managed by `PdoThingRepository` and `ThingController`; templates will be stored at `templates/thing/` directory, routes should start from `thing_`: `thing_index`, `thing_view`, `thing_delete`, etc.
- `AnotherThing` will be stored at `another_thing` database table, managed by `PdoAnotherThingRepository` and `AnotherThingController`; templates will be stored at `templates/another_thing/` directory, routes should start from `another_thing_`: `another_thing_index`, `another_thing_view`, `another_thing_delete`, etc.

#### Example:

If you don't want to write getters/setters - you can just define class variables as `protected`.

All class variables need to be lowercase with underscores.

Good:

- `$id`
- `$another_id`
- `$another_variable`

Bad:

- `$Id`
- `$anotherId`
- `$another_Variable`

```php
# src/Model/Thing.php

namespace LinkORB\Skeleton\Model;

use LinkORB\Framework\Model\ModelInterface;
use LinkORB\Framework\Model\BaseModel;

class Thing extends BaseModel implements ModelInterface
{
    protected $id;
}
```

### Repositories

You should use `BaseLegacyRepository` for fast switching to repository from old codebase
or `BaseRepository` if you start new application.

#### Name convention

Each repository need to be named with CamelCase notation and consists from `Pdo`, model name and `Repository`.
For example:

- If we making repository for `Thing` model, right name will be `PdoThingRepository`.
- If we making repository for `AnotherThing` model, right name will be `PdoAnotherThingRepository`.

#### Example:

```php
# src/Repository/PdoThingRepository.php
namespace LinkORB\Skeleton\Repository;

use Radvance\Repository\BaseRepository;
use Radvance\Repository\RepositoryInterface;
use Radvance\Model\Thing;
use PDO;

class PdoThingRepository extends BaseRepository implements RepositoryInterface
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createEntity()
    {
        return Thing::createNew();
    }
}
```

### Controllers

#### Name convention

Each controller need to be named with CamelCase notation and consists from model name and `Controller`.
For example:

- If we making controller for `Thing` model, right name will be `ThingController`.
- If we making controller for `AnotherThing` model, right name will be `AnotherThingController`.

#### Example:

```
# src/Radvance/ThingController.php

namespace ExampleApp\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Radvance\Controller\BaseController;

class ThingController extends BaseController
{
    private function getEditForm(Application $app, Request $request, $id = null)
    {
        $repo = $app->getRepository($this->getModelName());
        $entity = $repo->findOrCreate($id);
    }
}
```

### Themes

Themes stored centralised right at framework.
So when we change design - it applied to all our projects.

You can choose theme via `parameters.yml`:

```
# app/config/parameters.yml

theme: default
```

If `theme` parameter not listed at `parameters.yml` - `default` theme used.

Currently we have only one theme - `default`.

### Templates

#### Index

```twig
{% extends "@BaseTemplates/crud/index.html.twig" %}

{% block index %}
    {% if entities|length %}
        <table class="table table-{{ name }}">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>

            <tbody>
            {% for entity in entities %}
                <tr>
                    <td>{{ entity.id }}</td>
                    <td>{{ entity.name }}</td>
                    <td>{{ entity.description }}</td>
                    <td>
                        <a href="{{ path('proxy_config_nginx_generate', { 'id': entity.id }) }}" class="btn btn-info btn-sm" title="Generate nginx config">
                            <i class="fa fa-server"></i>
                        </a>

                        {% include '@BaseTemplates/crud/index/row_buttons.html.twig' %}
                    </td>
                </tr>
            {% endfor %}
            </tbody>
        </table>
    {% else %}
        There are no proxies
    {% endif %}
{% endblock %}

{% block buttons %}
    <!-- Additional buttons-->
{% endblock %}
```

#### Edit

```
{% extends "@BaseTemplates/crud/edit.html.twig" %}

{% block buttons %}
    <!-- Additional buttons-->
{% endblock %}
```

### View

```
{% extends "@BaseTemplates/crud/view.html.twig" %}

{% block view %}
    ID: {{ entity.getId() }}<br/>
    Name: {{ entity.getName() }}<br/>
    Description: {{ entity.getDescription() }}<br/>
{% endblock %}

{% block buttons %}
    <a href="{{ path('proxy_config_nginx_generate', { 'id': entity.id }) }}" class="btn btn-primary">
        Generate nginx config
    </a>
    {#
    <a href="{{ path('proxy_config_uberproxy_generate', { 'id': entity.id }) }}" class="btn btn-primary">
        Generate uberproxy config
    </a>
    #}
{% endblock %}
```

## Behat tests

### Requirements

Add next lines to your app's `composer.json` at `require-dev` section:

```
"behat/behat": "^3.0",
"behat/mink": "^1.6",
"behat/mink-extension": "^2.0",
"behat/mink-goutte-driver": "^1.1",
```

### Examples

```
@crud
Feature: Server CRUD
    In order to manage server records
    As an admin
    I want to be able to perform CRUD operations with server records

    Background:
        Given there are proxy, server tables truncated
        And there are the following proxy:
            | name          | description  |
            | First         | First proxy  |
            | Second proxy  |              |
        And there are the following server:
            | proxyId  | name       | description   | ip        | port |
            | 1        | first.com  | First server  | 127.0.0.1 | 80   |
            | 1        | second.com | Second server | 127.0.0.1 | 81   |
            | 1        | third.com  |               | 127.0.0.1 | 8080 |

    Scenario: List index of all servers
        Given I am on the frontpage page
         When I follow "Server"
         Then I should be on the server index page
          And I should see 3 server in the list

    Scenario: Names are listed in the index
        Given I am on the frontpage page
         When I follow "Server"
         Then I should be on the server index page
          And I should see server with name "first.com" in the list
          And I should see server with name "second.com" in the list

    Scenario: Seeing empty index of servers
        Given there are no server
         When I am on the server index page
         Then I should see "There are no servers"

    Scenario: Accessing the server creation form
        Given I am on the frontpage page
         When I follow "Server"
          And I follow "Add"
         Then I should be on the server creation page

  # Scenario: Creating title for server

    Scenario: Creating new server
        Given I am on the server creation page
         When I select "Second proxy" from "Proxy id"
         When I fill in "Name" with "fourth.com"
         When I fill in "Ip" with "127.0.0.1"
         When I fill in "Port" with "80"
          And I press "Add"
         Then I should be on the server index page
        # And I should see "Server has been successfully created"
          And Text "fourth.com" should appear on the page
          And Text "Second proxy" should appear on the page

  # Scenario: View title for server

    Scenario: Edit title for server
        Given I am on the frontpage page
         When I follow "Server"
          And I click "Edit" near "first.com"
         Then I should see "Edit first.com server"

    Scenario: Edit existing server
        Given I am on the server "second.com" editing page
         When I fill in "Port" with "8081"
          And I press "Save"
         Then I should be on the server index page
        # And I should see "Server has been successfully updated"
          And Text "8081" should appear on the page

    Scenario: Delete server from editing page
        Given I am on the server "third.com" viewing page
         When I press "Delete"
         Then I should be redirected to server index page
        # And I should see "Server 'third.com' has been successfully deleted"
          And Text "third.com" should not appear on the page

    Scenario: Delete server from list
        Given I am on the server index page
         When I click "Delete" near "first.com"
         Then I should be redirected to server index page
        # And I should see "Server 'first.com' has been successfully deleted"
          And Text "first.com" should not appear on the page

```

## Where used?

- [Proxytect](https://github.com/linkorb/proxytect)

## Any questions?

Read the code.
