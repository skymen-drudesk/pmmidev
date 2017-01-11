# PMMI

## Requirements

* Docker
** [Linux](https://docs.docker.com/linux/)
** [Mac](https://docs.docker.com/mac/)
** [Windows](https://docs.docker.com/windows/)

* [Direnv](http://direnv.net/)
** Optional
** Used with wrapper scripts to add `dbash` and `ddrush` commands

## Getting Started Developing

You can most easily start your Drupal project with this baseline by 
using
[Composer]
(https://getcomposer.org/) and [Docker](https://www.docker.com/):

```bash
composer create-project summitmedia/pmmi your_project_name
cd your_project_name
cp cnf/settings.local.php html/sites/default
cp cnf/.env.dist ./.env
./build/party.sh -b -i
```

To update without rebuilding from scratch, run `./build/party.sh` from 
the project root.

The `b` option when running `./build/party.sh` causes the Docker
containers to be (re)built.

The `i` option when running `./build/party.sh` causes Drupal to be 
installed. If a reference database file exists (see directly below), 
the site will be installed from the reference database. If a reference 
database file and Drupal configuration is exported, the site will be 
installed from configuration using the Configuration Installer profile. 
Otherwise, the site will be installed from scratch using the `minimal` 
install profile.

It is also worth noting, if you are working on an existing site, that 
the default install script allows you to provide a reference database 
in order to start your development. Simply add a sql file to either of 
the following:

* `build/ref/pmmi.sql`
* `build/ref/pmmi.sql.gz`

## Use

**IMPORTANT**

Use the .env file to customize the modules the site is seeded from.
There are defaults set up for different environments in env.dist. This 
file will be used by default if .env does not exist. The production 
environment is assumed if a global environment variable is not set to 
say otherwise.

The .env file should also be used to set the site environment. 
Available environments are:

* prod
* test
* dev

You can add you own custom modules to be built with your local install 
and set the site environment by making your .env file look something 
like this:

```bash
export SITE_ENVIRONMENT=dev
source env.dist
DROPSHIP_SEEDS=$DROPSHIP_SEEDS:devel:devel_themer:views_ui:features_ui
```

Note that the last line is only necessary if you wish to enable modules
in addition to the `DROPSHIP_SEEDS` defined in the `env.dist` file.

## Easy Development with Docker

### Wrapper Scripts

* `ddrush` executes [drush](https://github.com/drush-ops/drush)
  inside the web container.
* `ddrupal` executes [drupal console]
(https://github.com/hechoendrupal/DrupalConsole) inside the web 
container.
* `dbash` opens a bash shell inside the web container (as *root*)

[Direnv](http://direnv.net/) is used to include the wrapper scripts and
the `vendor/bin` directory into the PATH.


## Xdebug

1. `sudo ifconfig lo0 alias 10.254.254.254`
2. PhpStorm server settings: http://take.ms/avckT

## The Build and Deployment Scripts

You may have noticed that `build/party.sh` builds and links the 
necessary Docker containers (using Docker Compose v2).

Then, Composer installs all the necessary packages.

Finally, either `build/install.sh` or `build/update.sh` is invoked.

Keep in mind this build is using Docker merely as a wrapper to the 
scripts you would use to do your normal deployment on any other 
machine. The scripts are intended to work on any environment.

You should note that `build/install.sh` really just installs Drupal and 
then passes off to `build/update.sh`, which is the reusable and 
non-destructive script for applying updates in code to a Drupal site 
with existing content.

Use `build/party.sh -b -i` to create a brand new environment. You can 
use this script to simulate what would happen when you deploy from a 
certain state like currently what is on production.

Use `build/party.sh` to *ACTUALLY* deploy your changes onto an 
environment like production or staging.

This is the tool you can use when testing to see if your changes have 
been persisted in such a way that your collaborators can use them and 
is a great alternative to just running `build/party.sh -i` over and 
over:

```bash
build/party.sh -i                            # get a baseline
ddrush sql-dump > base.sql                   # save your baseline
# ... do a whole bunch of Drupal hacking ...
ddrush sql-dump > tmp.sql                    # save your intended state
ddrush -y sql-drop && drush sqlc < base.sql  # restore baseline state
build/party.sh                               # apply changes to baseline
```

Note: The above assumes the use of Direnv.

You should see a lot of errors if, for example, you failed to provide 
an update hook for deleting a field whose fundamental config you are 
changing. Or, perhaps you've done the right thing and clicked through 
he things that should be working now and you see that it is not working 
as expected. This is a great time to fix these issues, because you know 
what you meant to do and your collaborators don't!

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

`ddrush csex sync -y` (Note: This assumes the use of Direnv.)

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
