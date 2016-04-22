#!/bin/sh

adduser etherpad-lite --system --group --home /var/etherpad

mkdir /var/log/etherpad-lite
chown -R etherpad-lite /var/log/etherpad-lite

cd /usr/share
git clone git://github.com/ether/etherpad-lite.git
chown -R etherpad-lite: /usr/share/etherpad-lite

cp /vagrant/puphpet/files/etherpad-lite /etc/init.d/etherpad-lite
chmod +x /etc/init.d/etherpad-lite
update-rc.d etherpad-lite defaults
service etherpad-lite start