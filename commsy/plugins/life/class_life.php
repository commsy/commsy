<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Dr. Iver Jackewitz, Johannes Schultze
//
// This file is part of the life (drupal) plugin for CommSy.
//
// This plugin is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This plugin is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You have received a copy of the GNU General Public License
// along with the plugin.

include_once('classes/cs_plugin.php');
class class_life extends cs_plugin {

   private $_url_to_life = '';
   private $_proxy_ip = '';
   private $_proxy_port = '';

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   public function __construct ($environment) {
      parent::__construct($environment);
      $this->_identifier = 'life';
      $this->_title      = 'Life';
      @include_once('plugins/life/config.php');
      if ( !empty($life_url_to_life) ) {
         $this->_url_to_life = $life_url_to_life;
      }
      if ( !empty($life_proxy_ip) ) {
         $this->_proxy_ip = $life_proxy_ip;
      }
      if ( !empty($life_proxy_port) ) {
         $this->_proxy_port = $life_proxy_port;
      }
   }

   public function isConfigurableInPortal () {
      return true;
   }

   public function logout () {
      $retour = false;
      $session = $this->_environment->getSessionItem();
      $user_id = $session->getValue('user_id');
      $auth_source_id = $session->getValue('auth_source');
      $context_id = $session->getValue('commsy_id');
      $this->_environment->setCurrentContextID($context_id);
      $user_manager = $this->_environment->getUserManager();
      $user_manager->setContextLimit($context_id);
      $user_manager->setUserIDLimit($user_id);
      $user_manager->setAuthSourceLimit($auth_source_id);
      $user_manager->select();
      $user_list = $user_manager->get();
      if ( $user_list->getCount() == 1 ) {
        $user_item = $user_list->getFirst();
        if($user_item->_issetExtra('EXTERNALID')){
         $session = $this->_environment->getSessionItem();
         if ( !empty($session) ) {
            $session_id = $session->getSessionID();
            if ( !empty($session_id) ) {
               $cURL = curl_init();
               curl_setopt($cURL, CURLOPT_URL, $this->_url_to_life."/logmeout/" . $session_id);
               curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
               curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
               curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, false);

               // proxy
               $proxy = '';
               if ( !empty($this->_proxy_ip) ) {
                  $proxy = $this->_proxy_ip;
                  if ( !empty($this->_proxy_port) ) {
                     $proxy .= ':'.$this->_proxy_port;
                  }
               }
               if ( isset($proxy) and !empty($proxy) ) {
                  curl_setopt($cURL,CURLOPT_PROXY,$proxy);
               }

               $output = curl_exec($cURL);
               if ( strstr(mb_strtolower($output,'UTF-8'),'true') ) {
                  $retour = true;
               } else {
                  #include_once('functions/error_functions.php');
                  #trigger_error('can not logout drupal user in life',E_USER_WARNING);
               }
               curl_close($cURL);
            }
         }
        }
      }
      return $retour;
   }

   public function user_save($user_item){
      $retour = false;
      if ($user_item->_issetExtra('EXTERNALID')){
         $changed = false;
         if ( !empty($user_item)
              and ( $user_item->hasChanged('email')
                    or $user_item->hasChanged('firstname')
                    or $user_item->hasChanged('lastname')
                    or $user_item->hasChanged('user_id')
                  )
            ) {
            $changed = true;
         }
         if ( $changed ) {
            $session = $this->_environment->getSessionItem();
            if ( !empty($session) ) {
               $session_id = $session->getSessionID();
               if ( !empty($session_id) ) {
                  $cURL = curl_init();
                  curl_setopt($cURL, CURLOPT_URL, $this->_url_to_life."/changeprofile/" . $session_id);
                  curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
                  curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
                  curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, false);

                  // proxy
                  $proxy = '';
                  if ( !empty($this->_proxy_ip) ) {
                     $proxy = $this->_proxy_ip;
                     if ( !empty($this->_proxy_port) ) {
                        $proxy .= ':'.$this->_proxy_port;
                     }
                  }
                  if ( isset($proxy) and !empty($proxy) ) {
                     curl_setopt($cURL,CURLOPT_PROXY,$proxy);
                  }

                  $output = curl_exec($cURL);
                  if ( strstr(mb_strtolower($output,'UTF-8'),'true') ) {
                     $retour = true;
                  } else {
                     #include_once('functions/error_functions.php');
                     #trigger_error('can not change drupal user in life',E_USER_WARNING);
                  }
                  curl_close($cURL);
               }
            }
         }
      }
      return $retour;
   }
}
?>