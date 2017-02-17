#!/bin/bash
VIDEO_PATH=$1
IP=$2
avconv -i "rtsp://$IP:554/11" -v quiet -vcodec copy -acodec aac -strict experimental -ar 44100 -r 9 -map 0 -f segment -segment_time 30 "$VIDEO_PATH/cam_$IP-%04d.mp4"
