#!/usr/bin/env bash

realpath() {
    [[ $1 = /* ]] && echo "$1" || echo "$PWD/${1#./}"
}

APP_HOME=$(dirname "$(realpath "$0")")
APP_PID=$(<"$APP_HOME/app.pid")

if ps -p $APP_PID > /dev/null
then
    echo "Failed to start, service already started!"
    exit 1
fi

nohup java -jar -Dfile.ecoding=UTF-8 -Xmx512M "$APP_HOME/dn-hub.jar" > "$APP_HOME/application.log" 2>&1 &

APP_PID=$!

echo $APP_PID > "$APP_HOME/app.pid"

echo "App has been started, pid = $APP_PID."