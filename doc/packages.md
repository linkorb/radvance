Packages
========

Packages allow you to structure your code so that it becomes re-usable in multiple applications.
It also allows you to split large applications (with many controllers, repositories, models, etc) into
smaller structured pieces.

It works very similar to Symfony Bundles, Laravel Service Providers etc.

Actually, Radvance Packages are designed to be **framework independent**. So you can use your Packages
in Radvance projects, but also in Symfony, Laravel, etc projects etc.

## Directory structure:

Each package lives in it's own namespace. For example: `Acme\Package\Hello`.

Inside this directory, you'll find the following sub-directories (where applicable):

* `Controller/`: Put all your controllers here.
* `Model/`: Put all your models here.
* `Repository/`: Put all your repositories here.
* `Resources/views`: Put all your templates here.

These directories are conventions, and should contain framework-independent code only.

Additionally, one or more of the following files may exist in the root of your Package directory:

* `HelloProvider.php`: Initializer code for Radvance, Silex and Pimple based applications.
* `HelloBundle.php`: Initializer code for Symfony based applications.
* `HelloServiceProvider.php`: Initializer code for Laravel based applications.

## Using packages

In order to use a package, you'll need to configure it in your main Application class. Here's an example:

```php
namespace Acme;

class Application extends BaseWebApplication implements FrameworkApplicationInterface
{
    protected function configurePackages()
    {
        $this->register(new \Acme\Package\Hello\HelloProvider());
        $this->register(new \Other\Package\Awesome\AwesomeProvider());
    }
}
```

Note how this is using the standard Silex/Pimple way of registering services.

After registering your package, Radvance will take further actions to initialize your packages:

* The package path's are checked for a `Repository/` folder. Any valid Repository classes are automatically registered (just like if you'd use them in your main application code-path).
* The package path's are checked for a `Resources/views` folder. If it exists, the path will be registered in the Twig file loader. You can now access your templates as `@HelloPackage/templatename.html.twig`

## Ensure framework agnostic controllers!

Please refer to this excellent blog-post series:
* [part1](http://php-and-symfony.matthiasnoback.nl/2014/06/how-to-create-framework-independent-controllers/) 
* [part2](http://php-and-symfony.matthiasnoback.nl/2014/06/don-t-use-annotations-in-your-controllers/) 
* [part3](http://php-and-symfony.matthiasnoback.nl/2014/06/framework-independent-controllers-part-3/) 

In short:

* Don't extend from a "BaseController"
* Use Dependency Injection (supported in Radvance through constructor)
* Don't use ContainerAware (let the container inject what you need)
* Don't ask for `Application $app` as controller action arguments, be more specific.
