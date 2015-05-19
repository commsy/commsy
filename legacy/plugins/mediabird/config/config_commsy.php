<?php

// cache folder
$mediabird_folder = 'var/mediabird/';
if (!file_exists($mediabird_folder)) {
   mkdir($mediabird_folder);
   if (!file_exists($mediabird_folder)) {
      include_once ('functions/error_functions.php');
      trigger_error('can not make root directory for mediabird plugin in var', E_USER_WARNING);
   }
}

$cache_folder2 = 'var/mediabird/cache/';
if (!file_exists($cache_folder2)) {
   mkdir($cache_folder2);
   if (!file_exists($cache_folder2)) {
      include_once ('functions/error_functions.php');
      trigger_error('can not make cache directory for mediabird plugin', E_USER_WARNING);
   }
}
MediabirdConfig :: $cache_folder = $cache_folder2;

if (!isset ($environment) and isset ($this->_environment)) {
   $environment = $this->_environment;
}

// mail
if ( isset($environment) ) {
   $server_item = $environment->getServerItem();
   if ( isset($server_item) ) {
      $default_sender_address = $server_item->getDefaultSenderAddress();
      if ( !empty($default_sender_address) ) {
         MediabirdConfig :: $no_reply_address = $default_sender_address;
         MediabirdConfig :: $webmaster_address = $default_sender_address;
      }
   }
}
if ( isset($environment) ) {
   $c_send_email = $environment->getConfiguration('c_send_email');
   if (isset ($c_send_email)) {
      if ($c_send_email) {
         MediabirdConfig :: $disable_mail = false;
      } else {
         MediabirdConfig :: $disable_mail = true;
      }
   } else {
      MediabirdConfig :: $disable_mail = false;
   }
} else {
   MediabirdConfig :: $disable_mail = false;
}

// proxy: address and port
if ( isset($environment) ) {
   global $symfonyContainer;
   $c_proxy_ip = $symfonyContainer->getParameter('commsy.settings.proxy_ip');
   $c_proxy_port = $symfonyContainer->getParameter('commsy.settings.proxy_port');
   if ( isset($c_proxy_ip)
        and !empty($c_proxy_ip)
      ) {
      MediabirdConfig :: $proxy_address = $c_proxy_ip;
      if ( isset($c_proxy_port)
           and !empty($c_proxy_port)
         ) {
         MediabirdConfig :: $proxy_port = $c_proxy_port;
      }
   }
}
?>