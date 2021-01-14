#!/usr/bin/env bash

if [ $# -eq 0 ]; then
  echo "Supply a relative path for the recording. The generated files will have an extension appended to it."
  exit 1
fi

PATH=$1
echo "Recording $PATH.asf ..."
/usr/bin/mplayer "http://listen.noagendastream.com/noagenda" -dumpstream -dumpfile $PATH.asf &> $PATH.log & /bin/sleep 60s
/usr/bin/pkill mplayer
