rm MediabirdSource -rf
mkdir MediabirdSource
cd MediabirdSource
tar -xf ../MediabirdSource.tar.bz2
cp source/server ../ -r
cp source/config ../ -r
cp source/server ../ -r
cp source/js ../../../htdocs/plugins/mediabird/ -r
cp source/css ../../../htdocs/plugins/mediabird/ -r
cp source/images ../../../htdocs/plugins/mediabird/ -r
cd ..
rm MediabirdSource -rf
rm MediabirdSource.tar.bz2
