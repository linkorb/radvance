Cache
=====

Radvance uses the symfony/cache component which is PSR-6 compliant.

* http://symfony.com/doc/current/components/cache.html
* http://www.php-fig.org/psr/psr-6/

You can access it from the DI container: `$cache = $app['cache']`;

## Configuration

In your `config.yml`:

```yml
cache:
    type: filesystem
    directory: %cache_directory%
```

Then in your `parameters.yml`:
```yml
parameters:
    cache_directory: /tmp/my-app-cache
```
