#!/usr/bin/env bash

if [ $# -lt 2 ]
  then
    echo "Supply a source path for the recording and a target path prefix for the split files."
    exit 1
fi

SOURCE=$1
TARGET=$2

for OFFSET in 0 300 600 900 1200 1500 1800 2100 2400 2700 3000
do
  ffmpeg -ss $OFFSET -t 600 -i $SOURCE $TARGET$OFFSET.mp3
done
