#!/bin/bash

ffmpeg -i "rtsp://$1:554/12" -threads 4 -filter:v "select='gt(scene,0.05)',showinfo" -vsync 0 $2/%05d.jpg
