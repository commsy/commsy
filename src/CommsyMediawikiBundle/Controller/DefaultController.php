<?php

/*
    nginx vhost for mein wiki and dynamic subdomain wikis
    
    server {
        listen                *:80;

        server_name           mediawiki.vm www.mediawiki.vm;
        client_max_body_size 1m;

        root /var/www/mediawiki;
            index  index.html index.htm index.php;

        access_log            /var/log/nginx/nxv_oghu35qgj8kg.access.log;
        error_log             /var/log/nginx/nxv_oghu35qgj8kg.error.log;
        
        location / {
            root  /var/www/mediawiki;
            try_files $uri /app.php$is_args$args;
            autoindex off;
            index  index.html index.htm index.php;
        }
        
        location ~ \.php(/|$) {
            fastcgi_index index.php;
            fastcgi_split_path_info ^(.+\.php)(/.*)$;
            include /etc/nginx/fastcgi_params;
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param HTTPS off;
        }
        sendfile off;
    }

    server {
        listen                *:80;

        server_name ~^(?<contextid>.+)\.mediawiki\.vm$;
        root /var/www/mediawiki_$contextid;

        access_log            /var/log/nginx/nxv_oghu35qgj8kg.access.log;
        error_log             /var/log/nginx/nxv_oghu35qgj8kg.error.log;
        
        location / {
            root  /var/www/mediawiki_$contextid;
            try_files $uri /app.php$is_args$args;
            autoindex off;
            index  index.html index.htm index.php;
            disable_symlinks off;
        }
        
        location ~ \.php(/|$) {
            fastcgi_index index.php;
            fastcgi_split_path_info ^(.+\.php)(/.*)$;
            include /etc/nginx/fastcgi_params;
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param HTTPS off;
            disable_symlinks off;
        }
        sendfile off;
    }
    
    mkdir /var/www/mediawiki_<contextid>
    cd /var/www/mediawiki_<contextid>
    ln -s ../mediawiki/* .
    rm LocalSettings.php
    cp ../mediawiki/LocalSettings.php .
    rm images -R
    mkdir images
    
    duplicate base database
    
    In LocalSettings.php
    $wgServer = "http://<contextid>.mediawiki.vm";
    ## Database settings
    ...
    $wgDBname = "mediawiki_<contextid>";
    ...
    
*/

namespace CommsyMediawikiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction()
    {
        return $this->render('CommsyMediawikiBundle:Default:index.html.twig');
    }
}
