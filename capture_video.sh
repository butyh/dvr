#!/bin/bash

IP=192.168.0.31
VIDEO_PATH=/home/pi/video
SEGMENT_TIME=300

echo $IP:$VIDEO_PATH:$SEGMENT_TIME

avconv -i "rtsp://$IP:554/11" -v quiet -vcodec copy -acodec aac -strict experimental -ar 44100 -r 9 -map 0 -f segment -segment_time $SEGMENT_TIME "$VIDEO_PATH/$IP-%03d.mp4"
