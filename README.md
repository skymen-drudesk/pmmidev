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

You can most easily start your Drupal project with this baseline by using
[Composer](https://getcomposer.org/) and [Docker](https://www.docker.com/):

```bash
composer create-project summitmedia/pmmi your_project_name
cd your_project_name
./build/party.sh -b
```

To update without rebuilding from scratch, run `./build/party.sh` from the
project root.

It is also worth noting, if you are working on an existing site, that the
default install script allows you to provide a reference database in order to
start your development. Simply add a sql file to either of the following:

* `build/ref/pmmi.sql`
* `build/ref/pmmi.sql.gz`

## Use

**IMPORTANT**

Use the .env file to customize the modules the site is seeded from. There are
defaults set up for different environments in env.dist. This file will be used
by default if .env does not exist. The production environment is assumed if
a global environment variable is not set to say otherwise.

You can add you own custom modules to be built with your local install by
making your .env file look something like this:

```bash
export SITE_ENVIRONMENT=dev
source env.dist
DROPSHIP_SEEDS=$DROPSHIP_SEEDS:devel:devel_themer:views_ui:features_ui
```

## Easy Development with Docker

### Wrapper Scripts

* `ddrush` executes [drush](https://github.com/drush-ops/drush)
  inside the web container.
* `dbash` opens a bash shell inside the web container (as *www-data*)

[Direnv](http://direnv.net/) is used to include the wrapper scripts and
the `vendor/bin` directory into the PATH.

## The Build and Deployment Scripts

You may have noticed that `build/party.sh` builds and links the necessary
Docker containers (using Docker Compose v2).

Then, Composer installs all the necessary packages.

Finally, either `build/install.sh` or `build/update.sh` is invoked.

Keep in mind this build is using Docker merely as a wrapper to the scripts you
would use to do your normal deployment on any other machine. The scripts are
intended to work on any environment.

You should note that `build/install.sh` really just installs Drupal and then
passes off to `build/update.sh`, which is the reusable and non-destructive
script for applying updates in code to a Drupal site with existing content.

Use `build/install.sh` to create a brand new environment. You can use this
script to simulate what would happen when you deploy from a certain state like
currently what is on production.

Use `build/update.sh` to *ACTUALLY* deploy your changes onto an environment like
production or staging.

This is the tool you can use when testing to see if your changes have been
persisted in such a way that your collaborators can use them and is a great
alternative to just running `build/install.sh` over and over:

```bash
build/install.sh                                # get a baseline
alias drush="$PWD/vendor/bin/drush -r $PWD/www" # use drush from composer
drush sql-dump > base.sql                       # save your baseline
# ... do a whole bunch of Drupal hacking ...
drush sql-dump > tmp.sql                        # save your intended state
drush -y sql-drop && drush sqlc < base.sql      # restore baseline state
build/update.sh                                 # apply changes to the baseline
```

You should see a lot of errors if, for example, you failed to provide an update
hook for deleting a field whose fundamental config you are changing. Or, perhaps
you've done the right thing and clicked through the things that should be
working now and you see that it is not working as expected. This is a great time
to fix these issues, because you know what you meant to do and your
collaborators don't!

## Composer with Drupal

We reference a custom composer repository in composer.json
[here](composer.json#L5-8). This repository was created by
traversing the list of known projects on drupal.org using
`drupal/parse-composer`, and has the package metadata for all the valid
packages with a Drupal 8 release, including Drupal itself.

As you add modules to your project, just update composer.json and run `composer
update`. You will also need to pin some versions down as you run across point
releases that break other functionality. If you are fastidious with this
practice, you will never accidentally install the wrong version of a module if
a point release should happen between your testing, and client sign off, and
actually deploying changes to production. If you are judicious with your
constraints, you will be able to update your contrib without trying to remember
known untenable versions and work arounds -- you will just run `composer update`
and be done.

This strategy may sound a lot like `drush make`, but it's actually what you
would get if you took the good ideas that lead to `drush make`, and then
discarded everything else about it, and then discarded those good ideas for
better ideas, and then added more good ideas.

`composer install` is called in both `./build/install.sh` and
`./build/update.sh` so any changes you make to composer.lock by running
`composer update` will be installed when anyone uses those scripts. Make sure
to commit composer.lock when you do run `composer update`.

See:

* [composer](https://getcomposer.org)
  * [composer install](https://getcomposer.org/doc/03-cli.md#install)
  * [composer update](https://getcomposer.org/doc/03-cli.md#update)
  * [composer create-project](https://getcomposer.org/doc/03-cli.md#create-project)
  * [composer scripts](https://getcomposer.org/doc/articles/scripts.md)
  * [composer and Patching](http://generalredneck.com/blog/patching-modules-using-composer-patches-plugin)

## Configuration with Drupal 8

By default configuration uses the core Configuration Import Manager. You will
notice that this project actually imports all configuration from `cnf/drupal`.
This directory is set up in the settings file to be the default configuration
sync folder.

To add the current configuration of your site to this folder use:

`drush cex`

This should dump all configuration all modules implement to `cnf/drupal`
allowing our build script to import it at build time using `drush cim`. Make
sure to commit any changes to git.

## Custom Code

You will find that a place for your custom code has already been created.

`html/modules/custom` contains a single custom modules that is used as the parent
for all custom modules of the site. To enable new modules. Simply add them as
a dependency of this module or as a dependency of a module this module considers
a dependency and it will be enabled when running `./build/install.sh` or
`./build/update.sh`


`html/themes/custom` will need to be created when you are ready to create a theme.

## Contributed Code

All Contributed code is downloaded using composer. Simply put the version you
wish to download in composer.json and run `composer update`. These will be
downloaded to `www/modules/contrib and www/themes/contrib`. The rest will stay in
the created vendor folder.
