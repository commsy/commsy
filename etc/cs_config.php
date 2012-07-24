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
$db["normal"]["database"] = "commsy_dev";

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
   $c_commsy_url_path = "/Workspace/CommSy8/htdocs";
}
$c_commsy_url_path = deleteLastSlash($c_commsy_url_path);

if ( !empty($_SERVER["SCRIPT_FILENAME"]) ) {
   $c_commsy_path_file = dirname($_SERVER["SCRIPT_FILENAME"]);
   $c_commsy_path_file = str_replace('/htdocs','',$c_commsy_path_file);
   $c_commsy_path_file = str_replace('/',DIRECTORY_SEPARATOR,$c_commsy_path_file);
} else {
   $c_commsy_path_file = "C:/xampp/htdocs/Workspace/commsy";
}
$c_commsy_path_file = deleteLastSlash($c_commsy_path_file);

// security key
$c_security_key = "4664fa050c78653df35c55deb381b5e8";

// include first default commsy settings
@include_once('etc/commsy/default.php');

// include second special commsy settings
@include_once('etc/commsy/settings.php');

$c_smarty = true;
$theme_array = array();
/* Standard- Array */
$theme_array['yellow'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_YELLOW',
	'value'		=> 'yellow',
	'disabled'	=> false
);
$theme_array['ocean'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_OCEAN',
	'value'		=> 'ocean',
	'disabled'	=> false
);
$theme_array['darkblue'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_DARK_BLUE',
	'value'		=> 'darkblue',
	'disabled'	=> false
);
$theme_array['confetti'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_CONFETTI',
	'value'		=> 'confetti',
	'disabled'	=> false
);
$theme_array['yellow'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_YELLOW',
	'value'		=> 'yellow',
	'disabled'	=> false
);
$theme_array['grey'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_GREY',
	'value'		=> 'grey',
	'disabled'	=> false
);
$theme_array['redgrey'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_REDGREY',
	'value'		=> 'redgrey',
	'disabled'	=> false
);
$theme_array['football'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_FOOTBALL',
	'value'		=> 'football',
	'disabled'	=> false
);
$theme_array['red'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_RED',
	'value'		=> 'red',
	'disabled'	=> false
);
$theme_array['ice'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_ICE',
	'value'		=> 'ice',
	'disabled'	=> false
);
$theme_array['book'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_BOOK',
	'value'		=> 'book',
	'disabled'	=> false
);
$theme_array['flower'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_FLOWER',
	'value'		=> 'flower',
	'disabled'	=> false
);
$theme_array['stars'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_STARS',
	'value'		=> 'stars',
	'disabled'	=> false
);
$theme_array['aquarium'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_AQUARIUM',
	'value'		=> 'aquarium',
	'disabled'	=> false
);
$theme_array['heaven'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_HEAVEN',
	'value'		=> 'heaven',
	'disabled'	=> false
);
$theme_array['pink'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_PINK',
	'value'		=> 'pink',
	'disabled'	=> false
);
$theme_array['autumn'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_AUTUMN',
	'value'		=> 'autumn',
	'disabled'	=> false
);
$theme_array['neongreen'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_NEONGREEN',
	'value'		=> 'neongreen',
	'disabled'	=> false
);
$theme_array['schulehh'] = array(
	'text'		=> 'COMMON_COLOR_SCHEMA_SCHULEHH',
	'value'		=> 'schulehh',
	'disabled'	=> false
);

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
?>