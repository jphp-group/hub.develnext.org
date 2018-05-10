#!/usr/bin/env bash

realpath() {
    [[ $1 = /* ]] && echo "$1" || echo "$PWD/${1#./}"
}

APP_HOME=$(dirname "$(realpath "$0")")
APP_PID=$(<"$APP_HOME/app.pid")

if ps -p $APP_PID > /dev/null
then
    echo "Shutdown app with pid = $APP_PID ..."
    kill -15 "$APP_PID"

    while ps -p $APP_PID > /dev/null
    do
        echo "Waiting ..."
        sleep 0.3
    done

    echo "Done!"
else
    echo "App is not running."
fi