#!/bin/bash

PIDFILE="/www/web/process-queue.pid"

if [ -e "${PIDFILE}" ] && (ps -u $(whoami) -opid= | grep -P "^\s*$(cat ${PIDFILE})$" &> /dev/null); then
	exit 99
fi

php /www/web/process-queue.php &> /www/web/logs/process-queue.log &

echo $! > "${PIDFILE}"
chmod 644 "${PIDFILE}"
