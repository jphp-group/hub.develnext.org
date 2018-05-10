#!/usr/bin/env bash

realpath() {
    [[ $1 = /* ]] && echo "$1" || echo "$PWD/${1#./}"
}

APP_HOME=$(dirname "$(realpath "$0")")

sh "$APP_HOME/stop.sh"
sh "$APP_HOME/start.sh"
