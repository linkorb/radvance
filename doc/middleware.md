Middleware
==========

Radvance integrates StackPHP for HttpKernelInterface based middleware.

Examples can be found: [here](http://stackphp.com/middlewares/) and on packagist.

The `BaseWebApplication` class can enable a couple of common middleware's using
`parameters.yml` (see examples below).

## Available middlewares through configuration:

### Misc embed codes:

* Piwik: requires `url` and `siteId`
* GoogleAnalytics: requires `siteId`
* HotJar: requires `siteId`
* SpotClarify: requires `key`
* Inspectlet: requires `siteId`

### RequestId

This middleware will generate a unique RequestId (uuid) for every request.
This id can be used to consolidate logs, enable multi-tier tracking. The RequestID is also
exposed to clients using the `x-request-id` HTTP response header.

It doesn't require any configuration, and is enabled by default

### RequestLog

Logs both request + response details to one or more URLs. Example config in `parameters.yml`:

```
parameters:
    request_log:
        urls:
            - json-path://home/joe/var/request_log/{date}
```

Note: the json-path output url is an absolute path.
Use the `{date}` variable to partition logs per day.

### Maintenance

This middleware will show a maintenance page, while allowing developers/admins to use
the system based on an IP-whitelist:

```yml
maintenance:
    enabled: true
    whitelist:
        - 127.0.0.1
        - 10.0.0.1
```

## Adding your own application-specific middleware:

Simply override your app's configureStack() method and push your own middleware onto the stack. For example:

```php

class Application extends BaseWebApplication
{
    public function configureStack()
    {
        parent::configureStack(); // base config
        $this->stack->push(MyMiddleware::class, $some, $arguments, $needed, $by, $middleware);
    }
}
```
