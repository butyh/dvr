#!/bin/bash

IP=192.168.0.31
VIDEO_PATH=/home/butyh/video
SEGMENT_TIME=300

ffmpeg -i "rtsp://$IP:554/11" -v verbose -threads 4 -vcodec copy -acodec aac -strict experimental -ar 44100 -r 9 -map 0 -f segment -segment_time $SEGMENT_TIME "$VIDEO_PATH/$IP-%03d.mp4"
