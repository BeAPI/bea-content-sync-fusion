#!/usr/bin/env bash
# 2018 - BE API - Cronjob for "BEA Content Sync Fusion" plugin

## INSTALL
# We recommend to exec this task each minute : */1 * * * *

## USAGE
# 3 arguments max
# 1st argument : REQUIRED - the network URL of WordPress, eg : mydomain.fr, https://mydomain.fr
# 2nd argument : OPTIONAL - the WP-CLI binary command, eg: wp, "lando wp", "php wp-cli.phar" (Default value : wp)
# 3nd argument : OPTIONAL - the path of WP installation, eg: /var/www/wp/ (not default value)

## TODO
# Allow to customize path for PID file

# set -e # same AS set -o errexit
set -o pipefail
set -o nounset

# Set magic variables for current file & dir
# See: https://kvz.io/blog/2013/11/21/bash-best-practices/
__dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
__file="${__dir}/$(basename "${BASH_SOURCE[0]}")"
__base="$(basename ${__file} .sh)"

# The first argument is mandatory
if [ -z "${1:-}" ]
then
      echo "You must pass at least one argument, the WordPress network URL..."
      exit 1
fi

# Set variables from command arguments
WP_NETWORK_URL=${1}
WP_CLI_BIN=${2:-wp}

# Wrap the 3rd argument if is filled
if [ -n "${3:-}" ]; then
    WP_PATH="--path=${3}"
else
    WP_PATH=""
fi

# Create and test for a LOCK PID FILE for Preventing duplicate cron job executions
# See: https://bencane.com/2015/09/22/preventing-duplicate-cron-job-executions/
PIDFILE="$__dir/wp-bea-csf.pid"
if [ -f $PIDFILE ]
then
  PID=$(cat $PIDFILE)
  ps -p $PID > /dev/null 2>&1
  if [ $? -eq 0 ]
  then
    echo "Process already running"
    exit 1
  else
    ## Process not found assume not running
    echo $$ > $PIDFILE
    if [ $? -ne 0 ]
    then
      echo "Could not create PID file"
      exit 1
    fi
  fi
else
  echo $$ > $PIDFILE
  if [ $? -ne 0 ]
  then
    echo "Could not create PID file"
    exit 1
  fi
fi

# Regular queue
$WP_CLI_BIN content-sync-fusion queue get_sites --url="$WP_NETWORK_URL" $WP_PATH  | xargs -I {} $WP_CLI_BIN content-sync-fusion queue pull --url={}  $WP_PATH 

# Check for resync content (new site/blog)
$WP_CLI_BIN content-sync-fusion resync new_sites --smart=true --attachments=true --post_type=true --taxonomies=true --url="$WP_NETWORK_URL" $WP_PATH

# Remove lock PIDFILE
rm $PIDFILE

exit 0