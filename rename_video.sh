#!/bin/bash

IFS='
'
while true
do
  for f in `ls -t /home/butyh/video/192* | sed '1d'`
  do
      FTIME=`stat $f | grep "Access: 20" | sed -e 's/Access: //' | sed -e 's/\.[[:digit:]]\{1,\} +[[:digit:]]\{1,\}//'`
      BASENAME=`basename $f`
      mv $f /home/butyh/video/$FTIME$BASENAME
  done
  sleep 1m
done
