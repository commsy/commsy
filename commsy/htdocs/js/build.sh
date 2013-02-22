#!/bin/bash

echo "Truncating old build target directory..."
rm -Rf ./build/*

echo "Copying source..."
rsync -avzbqC ./src/ ./src_cpy/

echo "Building..."
./src/util/buildscripts/build.sh --profile build.js

echo "Removing copied source..."
rm -Rf ./src_cpy