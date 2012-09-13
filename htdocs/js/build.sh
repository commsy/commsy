#!/bin/bash

echo "Truncating old build target directory..."
rm -Rf ./build/*

echo "Moving source away..."
mv ./src ./src_org

echo "Copying source..."
rsync -avzbqC ./src_org/ ./src

echo "Building..."
./src/util/buildscripts/build.sh --profile build.js

echo "Removing copied source..."
rm -Rf ./src

echo "Restoring original source..."
mv ./src_org ./src