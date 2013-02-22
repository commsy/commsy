wget http://www.mediabird.net/fileadmin/release/MediabirdNightly.tar.bz2 -O MediabirdNightly.tar.bz2
rm MediabirdNightly -rf
mkdir MediabirdNightly
cd MediabirdNightly
tar -xf ../MediabirdNightly.tar.bz2
cp source/server ../ -r
cp source/config ../ -r
cp source/server ../ -r
cp source/js ../../../htdocs/plugins/mediabird/ -r
cp source/css ../../../htdocs/plugins/mediabird/ -r
cp source/images ../../../htdocs/plugins/mediabird/ -r
cd ..
rm MediabirdNightly -rf
rm MediabirdNightly.tar.bz2
