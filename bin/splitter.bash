#!/usr/bin/env bash

if [ $# -lt 2 ]
  then
    echo "Supply a source path for the recording and a target path prefix for the split files."
    exit 1
fi

SOURCE=$1
TARGET=$2
ffmpeg -i $SOURCE -f segment -segment_time 600 -c copy $2%03d.mp3
