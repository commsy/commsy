wget http://www.mediabird.net/fileadmin/release/MediabirdStable.tar.bz2 -O MediabirdStable.tar.bz2
rm MediabirdStable -rf
mkdir MediabirdStable
cd MediabirdStable
tar -xf ../MediabirdStable.tar.bz2
cp source/server ../ -r
cp source/config ../ -r
cp source/server ../ -r
cp source/js ../../../htdocs/plugins/mediabird/ -r
cp source/css ../../../htdocs/plugins/mediabird/ -r
cp source/images ../../../htdocs/plugins/mediabird/ -r
cd ..
rm MediabirdStable -rf
rm MediabirdStable.tar.bz2
