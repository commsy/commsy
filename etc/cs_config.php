<?php
// Copyright (c)2008 Matthias Finck, Iver Jackewitz, Dirk Blössl
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

function deleteLastSlash ( $path ) {
   $retour = $path;
   if ( !empty($path) ) {
      if ( substr($path,strlen($path)-1) == '/'
           or substr($path,strlen($path)-1) == '\\'
         ) {
         $retour = substr($path,0,strlen($path)-1);
      }
   }
   return $retour;
}

// Database setup
$db["normal"]["host"] = "localhost";
$db["normal"]["user"] = "commsy";
$db["normal"]["password"] = "commsy";
$db["normal"]["database"] = "commsy7";

// Path setup
if ( !empty($_SERVER["HTTP_HOST"]) ) {
   if ( !empty($_SERVER["SERVER_PORT"])
        and $_SERVER["SERVER_PORT"] == 443
      ) {
      $c_commsy_domain = 'https://';
   } else {
      $c_commsy_domain = 'http://';
   }
   $c_commsy_domain .= $_SERVER["HTTP_HOST"];
} else {
   $c_commsy_domain = "http://localhost";
}
$c_commsy_domain = deleteLastSlash($c_commsy_domain);

if ( !empty($_SERVER["SCRIPT_NAME"]) ) {
   $c_commsy_url_path = dirname($_SERVER["SCRIPT_NAME"]);
} elseif ( !empty($_SERVER["PHP_SELF"]) ) {
   $c_commsy_url_path = dirname($_SERVER["PHP_SELF"]);
} else {
   $c_commsy_url_path = "/phpWorkspace/commsy7/htdocs";
}
$c_commsy_url_path = deleteLastSlash($c_commsy_url_path);

if ( !empty($_SERVER["SCRIPT_FILENAME"]) ) {
   $c_commsy_path_file = dirname($_SERVER["SCRIPT_FILENAME"]);
   $c_commsy_path_file = str_replace('/htdocs','',$c_commsy_path_file);
   $c_commsy_path_file = str_replace('/',DIRECTORY_SEPARATOR,$c_commsy_path_file);
} else {
   $c_commsy_path_file = "C:/xampp/htdocs/phpWorkspace/CommSy7";
}
$c_commsy_path_file = deleteLastSlash($c_commsy_path_file);

// security key
$c_security_key = "ac2e92293e8f34eeec1fd575a28351ca";

// include first default commsy settings
@include_once('etc/commsy/default.php');

// include second special commsy settings
@include_once('etc/commsy/settings.php');

/** include then special config files **/
@include_once('etc/commsy/cookie.php');
@include_once('etc/commsy/etchat.php');
@include_once('etc/commsy/jsmath.php');
@include_once('etc/commsy/pmwiki.php');
@include_once('etc/commsy/swish-e.php');
@include_once('etc/commsy/ims.php');
@include_once('etc/commsy/fckeditor.php');
@include_once('etc/commsy/clamscan.php');
@include_once('etc/commsy/development.php');
@include_once('etc/commsy/autosave.php');
@include_once('etc/commsy/plugin.php');

$c_use_new_private_room = true;

?>