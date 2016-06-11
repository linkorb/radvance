Translation
===========

Radvance is using the following standard libraries to enable translation in Radvance applications:

* [Symfony Translation Component](http://symfony.com/doc/current/components/translation/index.html)
* [Silex TranslationServiceProvider](http://silex.sensiolabs.org/doc/providers/translation.html)

Please refer to these libraries for initial information.

## Adding translations

Radvance will load translation files from your application's `app/l10n/*.yml`.

Simply include a `yml` file for each locale, for example:

* `en_US.yml` American English
* `en_UK.yml` British English
* `nl_NL.yml` Dutch
* `nl_BE.yml` Dutch Belgium

It's recommended to follow [standard posix locale name format](https://www.gnu.org/software/gettext/manual/html_node/Locale-Names.html)


## Organizing your translations

Every `.yml` file can include a key called `include`, that lists an array of further filenames to include.

For example

```yml
include:
    - en_US/other.yml
    
model:
  example:
    name: Name
```

## Translating templates

You can use the basic `trans` filter to translate a string in twig. For example:

```html
<h1>{{ 'route.dashboard.header'|trans }}</h1>

<ul>
    <li>{{ 'model.example.name'| trans }}: {{ example.getName() }}</li>
</ul>
```

In templates, a route-specific translation can be shortened from `route.dashboard.header` to just `.header`. Radvance will automatically prefix it with `route.dashboard`.

## Translating strings in PHP code (i.e. forms)

This is using the standard Symfony/Silex method:

```php
$text = $app['translator']->trans('model.example.name');
```

## Translation id conventions.

It is very important to keep a strict structure in all translation ids. The following convention is used:

### Meaning

Keys should always describe their *purpose* and not their *location*. For example, if a form has a field with the label "Username", then a nice key would be label.username, not edit_form.label.username.

### Casing

Translation ids should be all lower-case. Segments are seperated using dot-notation, and each segment is underscore seperated. For example: `route.dashboard_index.sub_header`

### Template specific strings

Use `route.{routeName}.{element}`. For example `route.dashboard.header` to specify the content of the h1 header text.

Try to minimize this type of strings

### Model field names

Model field names are often re-used between templates (on index, view, edit, add, delete screens, etc). This can be achieved by following the id format: `model.{modelName}.{fieldName}`. For example: `model.contact.firstName`

### Common strings

A minimal list of common strings can be defined in the `common.` namespace.
Good examples:

* common.action.yes
* common.action.no
* common.label.id
* common.label.name

Make sure to split the `action` (words that appear on buttons) and `label` (things that appear statically in the ui) namespaces to avoid conflicts.

Bad example:

* common.label.product_barcode # too specific, should be in template or model
