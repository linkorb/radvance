## Development on Radvance

### Setup a test project with generators 

The best way to work on Radvance generators is to use this workflow:

* create a `test/` directory in the main repository (it's in .gitignore, so it won't be committed)
* in there, follow the general `new-project.md` steps to initialize a project.
* This allows you to test the project generators

### Working with a local Radvance repository in your custom project

Edit your web/index.php, and setup a custom registration for the Radvance namespace:

```php
$loader = require_once __DIR__.'/../vendor/autoload.php';
$pathToRadvance = __DIR__ .'/../../src/';
$loader->addPsr4('Radvance\\', $pathToRadvance, true);

$app = require_once __DIR__.'/../app/bootstrap.php';
$app->run();
```

You'll need to change `pathToRadvance` to point to the `src/` directory of your local Radvance repository
