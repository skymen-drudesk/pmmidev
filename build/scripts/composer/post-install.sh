#!/bin/sh

# Prepare the services file for installation
if [ ! -f html/sites/default/services.yml ]
  then
    cp html/sites/default/default.services.yml html/sites/default/services.yml
    chmod 777 html/sites/default/services.yml
fi

# Prepare the files directory for installation
if [ ! -d html/sites/default/files ]
  then
    mkdir -m777 html/sites/default/files
fi

# Prepare the config directory for installation
if [ ! -d config ]
  then
    mkdir -m777 config
fi
if [ ! -d config/active ]
  then
    mkdir -m777 config/active
fi
if [ ! -d config/staging ]
  then
    mkdir -m777 config/staging
fi
if [ ! -d config/sync ]
  then
    mkdir -m777 config/sync
fi