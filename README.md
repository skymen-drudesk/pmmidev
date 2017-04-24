# PMMI

**Make sure to read and follow the
[PMMI.org Technical Architecture Guide](./ARCHITECTUREGUIDE.md)**

This project is setup with an opinionated development workflow.

A Docker based development environment is provided.

[DD Party](https://github.com/summitmedia/dd-party) provides scripts
to help with building the site and deploying changes as well as
helper scripts to run commands inside Docker containers. For use, see
the [project's README](https://github.com/summitmedia/dd-party).

## Requirements

* Docker
  * [Linux](https://docs.docker.com/linux/)
  * [Mac](https://docs.docker.com/mac/)
  * [Windows](https://docs.docker.com/windows/)

* [Direnv](http://direnv.net/)
  * Optional
  * Used with wrapper scripts to add `dbash` and `ddrush` commands

* [docker-sync](https://github.com/EugenMayer/docker-sync/) version 0.2.x.
  * Optional
  * Used to improve performance on macOS (OS X).

## Initial Setup

You can most easily start your Drupal project with this baseline by
using [Composer](https://getcomposer.org/) and
[Docker](https://www.docker.com/):

```bash
git clone summitmedia/pmmi your_project_name
cd your_project_name
cp cnf/settings.local.php html/sites/default
cp cnf/.envt ./.env
./build/scripts/proj-init
```

## Performance on macOS (OS X).

To improve performance on macOS, use [docker-sync](https://github.com/EugenMayer/docker-sync/).

To install docker-sync run:

1. `gem install docker-sync`
2. `brew install fswatch`

In `.env`, set `OSX=1`:

```bash
export SITE_ENVIRONMENT=dev

source env.dist

OSX=1
```

## Using a Reference Database

It is also worth noting, if you are working on an existing site, that
the default install script allows you to provide a reference database
in order to start your development. Simply add a sql file to either of
the following:

* `build/ref/pmmi.sql`
* `build/ref/pmmi.sql.gz`

## Environment Settings

**IMPORTANT**

Use the .env file to set the environment and customize the modules the
site is seeded from. There are defaults set up for different
environments in env.dist. This file will be used by default if .env
does not exist. The production environment is assumed if a global
environment variable is not set to say otherwise.

The .env file should also be used to set the site environment.
Available environments are:

* prod
* test
* dev

You can add you own custom modules to be built with your local install
by making your .env file look something like this:

```bash
export SITE_ENVIRONMENT=dev
source env.dist
DROPSHIP_SEEDS=$DROPSHIP_SEEDS:devel:devel_themer:views_ui:features_ui
```

Note that the last line is only necessary if you wish to enable modules
in addition to the `DROPSHIP_SEEDS` defined in the `env.dist` file.

## Deploying Changes to non-Development Environments

To deploy/apply changes to an existing site with content, run
`./vendor/bin/dd-party`.

*Important* Make sure to set the `SITE_ENVIRONMENT` environment
variable in `.env` to `prod` or `staging`:

```bash
export SITE_ENVIRONMENT=prod
source env.dist
```

## Xdebug

1. `sudo ifconfig lo0 alias 10.254.254.254`
2. PhpStorm server settings: http://take.ms/avckT

## Composer with Drupal

We reference the official Drupal composer repository in composer.json
[here](composer.json#L5-8).

As you add modules to your project, just update composer.json and run 
`composer update`. (Keep in mind that this will get any available 
updates for all packages, based on defined version constraints.) You 
will also need to pin some versions down as you  run across point 
releases that break other functionality. If you are fastidious with 
this practice, you will never accidentally install the  wrong version 
of a module if a point release should happen between your  testing, and 
client sign off, and actually deploying changes to production. If you 
are judicious with your constraints, you will be  able to update your 
contrib without trying to remember known untenable versions and work 
arounds -- you will just run `composer update` and be done.

This strategy may sound a lot like `drush make`, but it's actually what 
you would get if you took the good ideas that lead to `drush make`, and 
then discarded everything else about it, and then discarded those good 
ideas for better ideas, and then added more good ideas.

`composer install` is called in both `./build/install.sh` and
`./build/update.sh` so any changes you make to composer.lock by running
`composer update` will be installed when anyone uses those scripts. 
Make sure to commit composer.lock after you run `composer update`.

See:

* [composer](https://getcomposer.org)
  * [composer install](https://getcomposer.org/doc/03-cli.md#install)
  * [composer update](https://getcomposer.org/doc/03-cli.md#update)
  * [composer create-project]
  (https://getcomposer.org/doc/03-cli.md#create-project)
  * [composer scripts](https://getcomposer.org/doc/articles/scripts.md)

### Applying Patches

[cweagans/composer-patches]
(https://github.com/cweagans/composer-patches) is used to apply Drupal 
patches, whcih are specified in `composer.patches.json`.

## Configuration with Drupal 8

By default, configuration uses the core Configuration Import Manager. 
You will notice that this project actually imports all configuration 
from `config/drupal/sync`. This directory is set up in the settings 
file to be the default configuration sync folder. `config/drupal/test` 
and `config/drupal/dev` are also set up in the settings file as 
additional configuration folders.

Environment specific configuration is set based on the 
`SITE_ENVIRONMENT` environment variable using the [Configuration Split]
(https://www.drupal.org/project/config_split) module. Configuration for 
the test dev environments live in `config/drupal/test` and 
`config/drupal/dev`, respectively.

Skipped modules are set in `drush/drushrc.php`. This excludes the 
modules from `core.extension.yml` and skips the enabling and 
uninstalling of modules during configuration import. For more on the 
theory behind the skipped modules approach see [Using the Configuration 
Module Filter in Drush 8]
(https://pantheon.io/blog/using-configuration-module-filter-drush-8).

To export the current configuration of your site use:

`ddrush csex -y` (Note: This assumes the use of Direnv.)

This should dump all configuration that all modules implement to 
`config/drupal/sync` allowing our build script to import it at build 
time using `drush cim sync --partial`. The `partial` option prevents 
configuration not exported from being deleted.

Configuration for test environments is exported to 
`config/drupal/test` and imported with `drush cim test --partial` if 
the `SITE_ENVIRONMENT` variable is set to `test`.

Configuration for dev environments is exported to 
`config/drupal/dev` and imported with `drush cim dev --partial` if 
the `SITE_ENVIRONMENT` variable is set to `dev`. 

*Make sure to commit any changes to git.*

## Custom Code

You will find that a place for your custom code has already been 
created.

`html/modules/custom` contains a single custom modules that is used as 
the parent for all custom modules of the site. To enable new modules. 
Simply add them as a dependency of this module or as a dependency of a 
module this module considers a dependency and it will be enabled when 
running `./build/party.sh`.


`html/themes/custom` will need to be created when you are ready to 
create a theme.

## Contributed Code

All Contributed code is downloaded using composer. Simply put the 
version you wish to download in composer.json and run 
`composer update`. [Composer Installers]
(https://github.com/composer/installers) to install packages the 
correct location based on package type.
