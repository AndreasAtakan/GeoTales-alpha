#!/bin/sh

#apt update
#apt upgrade -y
#apt autoremove -y

ufw disable
apt update
apt install -y ca-certificates

# php
apt install -y php-common php-fpm php-opcache php-bcmath php-curl php-gd php-json php-mbstring php-xml php-xmlrpc php-zip php-pgsql
cp local.ini /etc/php/8.1/fpm/conf.d/
systemctl restart php8.1-fpm

#certbot
apt install -y certbot
certbot certonly --standalone -d alpha.geotales.io
cp options-ssl-nginx.conf /etc/letsencrypt/

# nginx
apt install -y nginx nginx-extras
cp geotales.conf /etc/nginx/conf.d/
cp alpha.geotales.io /etc/nginx/sites-available/
ln -s /etc/nginx/sites-available/alpha.geotales.io /etc/nginx/sites-enabled/
nginx -s reload
