# Using the Debug Bar

Radvance includes support for [phpdebugbar.com](http://phpdebugbar.com/).

## Enabling the debugbar

To enable the debugbar, make sure that both `debug=true` and `debugbar=true` in your `parameters.yml`

## How it works

When the debugbar is enabled, an `after` handler is registered, that will inject
the required html in every request, right before the closing `body` tag.
For details, refer to `BaseWebApplication::configureDebugBar()`

## Assets

Debugbar requires some js and css assets. These will be re-generated/dumped in the web-root
on every request as static files.

Radvance will insert links to these assets before the closing `body` tag.

## Using the debugbar

You can call `$app->getDebugBar()` to get the debugbar instance.
Using this, you can add data to it, as documented on the main phpdebugbar.com website.
