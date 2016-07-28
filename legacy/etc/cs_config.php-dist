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

if (!function_exists('deleteLastSlash')) {
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
}

// Database setup
$db["normal"]["host"] = "localhost";
$db["normal"]["user"] = "commsy";
$db["normal"]["password"] = "commsy";
$db["normal"]["database"] = "commsy";

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
   $c_commsy_domain = "http://commsy.localhost.de";
}
$c_commsy_domain = deleteLastSlash($c_commsy_domain);

if ( !empty($_SERVER["SCRIPT_NAME"]) ) {
   $c_commsy_url_path = dirname($_SERVER["SCRIPT_NAME"]);
} elseif ( !empty($_SERVER["PHP_SELF"]) ) {
   $c_commsy_url_path = dirname($_SERVER["PHP_SELF"]);
} else {
   $c_commsy_url_path = "/htdocs";
}
$c_commsy_url_path = deleteLastSlash($c_commsy_url_path);

if ( !empty($_SERVER["SCRIPT_FILENAME"]) ) {
   $c_commsy_path_file = dirname($_SERVER["SCRIPT_FILENAME"]);
   $c_commsy_path_file = str_replace('/htdocs','',$c_commsy_path_file);
   $c_commsy_path_file = str_replace('/',DIRECTORY_SEPARATOR,$c_commsy_path_file);
} else {
   $c_commsy_path_file = "H:/XAMPP/commsy";
}
$c_commsy_path_file = deleteLastSlash($c_commsy_path_file);

// security key
$c_security_key = "3a2a14dbbab21710e78f7091af0f70dd";

//$cs_max_list_length = 20;

//$cs_max_search_list_length = 20;
//$cs_special_language_tags = '';
//$cs_external_login_redirect = 'url_without_commsy_parameter';
//$cs_external_login_redirect_portal_array = array();
//$cs_external_login_redirect_exeption_var = 'get_parameter_for_exception';


// include more commsy settings
@include_once('config_meta.php');

// use, if cron_new.php is started from the commanline
//$c_commsy_cron_path = "http://localhost/commsy/htdocs/";
// cron_new.php must be run as root to make changing user and group for commsy/var/temp possible!
//$c_commsy_cron_var_temp_user = "www-data";
//$c_commsy_cron_var_temp_group = "www-data";
?>