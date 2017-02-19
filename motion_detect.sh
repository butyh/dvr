#!/bin/bash
ffmpeg -i "rtsp://192.168.0.31:554/12" -threads 4 -filter:v "select='gt(scene,0.05)',showinfo" -vsync 0 frames/%05d.jpg