#!/bin/sh

cd /root/
wget --quiet http://download.gna.org/wkhtmltopdf/0.12/0.12.2.1/wkhtmltox-0.12.2.1_linux-wheezy-amd64.deb
dpkg -i wkhtmltox-0.12.2.1_linux-wheezy-amd64.deb
rm wkhtmltox-0.12.2.1_linux-wheezy-amd64.deb
ln -s /usr/local/bin/wkhtmltopdf /usr/bin/wkhtmltopdf
ln -s /usr/local/bin/wkhtmltoimage /usr/bin/wkhtmltoimage