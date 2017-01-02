FQDN routes
===========

This feature allows you to link a FQDN to a space.

This can be used to host custom, usually public, sites.

To use this feature, add the following to your `config.yml`

## Configuration

```yml
fqdn:
  default: %fqdn_default%
```

Then in your `parameters.yml`, specify `fqdn_default` as a parameter,
which is your default (admin) FQDN.

If your site is accessed using any other FQDN, it will try to resolve the
space based on the `space.fqdn` field. If there is a space that matches the FQDN of the request, that space is marked as 'current', and the alternative routes from `routes-fqdn.yml` 
are loaded (instead of the regular `routes.yml`)

## Theming

If a directory `themes/fqdn` exists, it will be registered as a Twig
namespace, called `@FqdnTheme`, which you can use in your public/frontend templates.
