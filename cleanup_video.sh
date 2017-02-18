#!/bin/bash

IFS='
'

VIDEO_PATH=/home/pi/video

while true
do
  USED_PERCENT=`df -h --output=pcent / | sed -e '1d' | sed -e 's/ //' | sed -e 's/%//'`
  while [ $USED_PERCENT -gt 10 ]
  do
      FNAME=`ls -tr $VIDEO_PATH/20* | head -n 1`
      rm /$VIDEO_PATH/$FNAME
      USED_PERCENT=`df -h --output=pcent / | sed -e '1d' | sed -e 's/ //' | sed -e 's/%//'`
  done
  sleep 5m
done