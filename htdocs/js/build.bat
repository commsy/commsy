@echo off



echo Truncating old build target directory...

del /s /q build\*.*

echo Copying source...

xcopy "src" "src_cpy" /s /c /i /h /k /o /x /y /exclude:exclude.txt



echo Building...

src/util/buildscripts/build.bat --profile build.js



echo Removing copied source...

rmdir /s /q src_cpy