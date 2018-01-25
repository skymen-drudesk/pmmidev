# DD Party

DD stands for Drupal Docker. DD Party provides scripts to help
with building the site and deploying changes as well as helper
scripts to run commands inside Docker containers.

## Requirements

* [Docker](https://docs.docker.com/engine/installation)
* [Docker Compose](https://docs.docker.com/compose/install)
* [Direnv](http://direnv.net/) (optional)

## Quick Start

* Set the following environment variables in your project's
`env.dist` or `.env` file:
  * `WEB_SERVICE`: The Docker Compose service for the web container.
  Default: `web`
  * `DRUPAL_ROOT`: The path (in the web container) to the Drupal
  site root. Default: `/var/www/html`
  * `THEME_ROOT`: The path (in the web container) to the default
  site theme. Default: `/var/www/html/themes/contrib/bootstrap`
  
* To start Docker containers and apply changes, run
`vendor/bin/dd-party`

* To (re)build Docker containers and apply changes, run
`vendor/bin/dd-party -b`

* To install Drupal and apply changes, run `vendor/bin/dd-party -i`.

* To (re)build Docker containers, install Drupal, and apply changes,
run `vendor/bin/dd-party -b -i`

## Direnv

`Direnv` is an environment switcher for the shell.

Using `direnv` makes the helper scripts available as commands. For
example, `ddrush cr` can be run instead of having to run
`vendor/bin/ddrush cr`.

### Use

1. Create `.envrc` in your project root if it does not already exist.
2. Add `PATH_add vendor/bin` to `.envrc`.

## The Build and Deployment Scripts

`vendor/bin/dd-party` builds and links the necessary Docker
containers (using Docker Compose v3).

Then, Composer installs all the necessary packages.

Finally, either `build/install.sh` or `build/update.sh` is invoked.

Keep in mind this build is using Docker merely as a wrapper to the
scripts you would use to do your normal deployment on any other
machine. The scripts are intended to work on any environment.

Note that `build/install.sh` really just installs Drupal and then
passes off to `build/update.sh`, which is the reusable and non-
destructive script for applying updates in code to a Drupal site
with existing content.

Use `vendor/bin/dd-party -b -i` to create a brand new environment.
Use this script to simulate what would happen when deploying from a
certain state like currently what is on production.

Use `vendor/bin/dd-party` to *ACTUALLY* deploy changes onto an
environment like production or staging.

This is the tool can be used when testing to see if changes have
been persisted in such that collaborators can use them and is a
great alternative to just running `vendor/bin/dd-party -i` over
and over:

```bash
./vendor/bin/dd-party -i                                  # get a baseline
./vendor/bin/ddrush sql-dump > base.sql                   # save your baseline
# ... do a whole bunch of Drupal hacking ...
./vendor/bin/ddrush sql-dump > tmp.sql                    # save your intended state
./vendor/bin/ddrush -y sql-drop && drush sqlc < base.sql  # restore baseline state
./vendor/bin/dd-party                                     # apply changes to baseline
```

There should be a lot of errors if, for example, an update hook for
deleting a field whose fundamental config is changing. Or, perhaps
changes were made through the UI and now it is not working as
expected. This is a great time to fix these issues.


### Config Import and Features Reversion Order

The importing of configuration and reverting of features happens in
the following order (if the directories exist):

1. `config/drupal/sync`
2. `config/drupal/panels_pages`
3. `config/drupal/test` (if test environment) / `config/drupal/dev`
(if dev environment).
4. Features reversion.
5. `config/drupal/overrides`

## Helper Scripts

Helper scripts are provided to make performing common tasks inside
the Docker web container easier.

The scripts are located in `vendor/bin`.

**dbackupdb:** Backs up the default database to `build/ref` using Drush.

**dbash:** Starts a bash session inside the web container.

**dcomposer:** Runs a Composer command inside the web container. Use:
`dcomposer command args/options`.

**ddrupal:** Runs a Drupal Console command inside the web container.
Use: `ddrupal command args/options`.

**ddrush:** Runs a Drush command inside the web container. Use:
`ddrush command args/options`.

**djs:** Executes `npm run js:w` inside the site theme defined by
the `THEME_ROOT` environment variable.

**dnpm:** Runs a npm command inside the web container. Use: `npm
command args/options`.

**dsass:** Executes `npm run sass:w` inside the site theme defined
by the `THEME_ROOT` environment variable.
