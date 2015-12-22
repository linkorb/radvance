# Configuration

Radvance apps are configured through a file called `app/config/parameters.yml`.

In most projects/repositories, you'll find a `parameters.yml.dist` file you can copy as a template.

## Parameter expression evaluator

You can use "expressions" in your parameter values.

Any expression in between double curly braces will be evaluated.

This allows you to do this:

```yml
name: "{{env('APP_NAME') ?: 'Cool app'}}"
```

This will try to assign the environment variable 'APP_NAME' to your app's name.
If that variable is not defined, the `?:` Ternary Operators will set it to the default "Cool app"

You can use the full syntax of the [Symfony Expression Engine](http://symfony.com/doc/current/components/expression_language/syntax.html)

## Server global environment configuration

If you're running multiple Radvance applications on your server, you can configure
some variables globally on your server.

This is useful for configuring your mysql, userbase and other parameters only once.
These variables will then be accessible in all your Radvance applications.

To setup environment variables globally for bash, you can use `/etc/profile` file.
Or use `~/.profile` to configure them for the current user only.

For example:
```
export USERBASE_URL="http://userbase.example.com/id/api/v1"
export USERBASE_USERNAME="joe"
export USERBASE_PASSWORD="secret"
```

You can also specify these parameters in `/etc/apache2/envvars` to make them 
 available to apps served by apache. Note that this only works if your
 apache is started through init, or apache2ctl

Try to avoid using `/etc/environment` it's ignored by bash
and only available to processes started by init.

More information:

* [Ubuntu help System Wide Environment Variables](https://help.ubuntu.com/community/EnvironmentVariables#System-wide_environment_variables)
