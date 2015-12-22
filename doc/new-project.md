Creating a New Project
======

This document describes the steps to create a new project.

## Setup project directory
```
mkdir myproject
cd myproject
git init
```

Create a `composer.json` file, based on a .dist file:

```
cp ~/git/radvance/radvance/doc/composer.json.dist composer.json
```

Edit the `composer.json` file with your project details (name, namespace, etc)

Load the dependencies:

```
composer install
```

Create a `radvance.yml` file in the root of your project:

```
name: MyProject
namespace: MyProject
code_path: src/
```

### Use the Radvance generator

Generate the project outline using the following command:

```
vendor/bin/radvance generate:project --projectPath=.
```

This will create the basic directory structures, and add sample files
