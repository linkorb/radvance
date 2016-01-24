# Configuration

Radvance apps are generally configured through two main config files:

1. `app/config/config.yml`: Application specific configuration
2. `app/config/parameters.yml`: Infrastructure related configuration

Usually `config.yml` "imports" `parameters.yml`

The `config.yml` file is normally checked into version-control, and contains no 
infrastructure related information (paths, hostnames, usernames, passwords, etc).
It includes default application settings that are identical for all installations of the app.

In most projects/repositories, you'll find a `parameters.yml.dist` file you can copy as a template:

```sh
cp app/config/parameters.yml.dist app/config/parameters.yml
```

In the `parameters.yml` file you specify infrastructure specific parameters such as database, smtp and other credentials etc.

## Example configuration:

`config.yml`
```yaml
---
# Import further configuration files
imports:
    - { resource: parameters.yml }

# Default app parameters
parameters:
    debug: false
    app_name: ContactBase

# Application specific configuration
app:
    name: %app_name%

# Security
security:
    providers:
        UserBase:
            url: %userbase_url%
            username: %userbase_username%
            password: %userbase_password%
```

`parameters.yml`
```yaml
---
parameters:
    debug: true
    pdo: mysql://username:password@localhost/dbname
    userbase_url: http://www.example.com/api/v1
    userbase_username: joejohnson
    userbase_password: qweqwe
```

## Parameter usage

You can use values from the `parameters` array in any other part of your configuration.
Simply pass the parameter-name between `%` signs. For example:

```yaml
---
example:
    my_setting: %example_setting%
```

You can now define parameters.example_setting in both your config.yml (for default values) and still override it in your infrastructure specific `parameters.yml`

## Parameter expression evaluator

You can use "expressions" in your parameter values too.

Any expression in between double curly braces will be evaluated.

This allows you to do this:

```yml
name: "{{env('APP_NAME') ?: 'Cool app'}}"
```

This will try to assign the environment variable 'APP_NAME' to your app's name.
If that variable is not defined, the `?:` Ternary Operators will set it to the default "Cool app"

You can use the full syntax of the [Symfony Expression Engine](http://symfony.com/doc/current/components/expression_language/syntax.html)

## Server global environment configuration

If you're running multiple Radvance applications on your server, you can configure some variables globally on your server.

This is useful for configuring your mysql, userbase and other parameters only once. These variables will then be accessible in all your Radvance applications.

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
