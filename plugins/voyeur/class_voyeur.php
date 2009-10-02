<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Dr. Iver Jackewitz
//
// This file is part of the voyeur plugin for CommSy.
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
class class_voyeur extends cs_plugin {

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   public function __construct ($environment) {
      parent::__construct($environment);
      $this->_translator->addMessageDatFolder('plugins/voyeur/messages');
      $this->_identifier = 'voyeur';
      $this->_title      = 'Voyeur';
      $this->_image_path = 'plugins/'.$this->getIdentifier();
   }

   public function isConfigurableInPortal () {
      return true;
   }

   public function configurationAtPortal ( $type = '', $values = array() ) {
      $retour = '';
      if ( $type == 'change_form' ) {
         $retour = true;
      } elseif ( $type == 'create_form' ) {
         if ( !empty($values['form']) ) {
            $retour = $values['form'];
            $retour->addTextfield( $this->_identifier.'_server_url',
                                   '',
                                   $this->_translator->getMessage('VOYEUR_CONFIG_FORM_TITLE_CONFIG'),
                                   $this->_translator->getMessage('VOYEUR_CONFIG_FORM_TITLE_CONFIG_DESC',$this->getTitle()),
                                   255,
                                   50,
                                   false,
                                   '',
                                   '',
                                   '',
                                   'left',
                                   $this->_translator->getMessage('VOYEUR_CONFIG_FORM_TITLE_URL'),
                                   '',
                                   false,
                                   '',
                                   10,
                                   true,
                                   false);
         }
      } elseif ( $type == 'save_config'
                 and !empty($values['current_context_item'])
               ) {
         if ( isset( $values[$this->_identifier.'_server_url'] ) ) {
            $values['current_context_item']->setPluginConfigForPlugin($this->_identifier,array($this->_identifier.'_server_url' => $values[$this->_identifier.'_server_url']));
         }
      } elseif ( $type == 'load_values_item'
                 and !empty($values['current_context_item'])
               ) {
         $retour = array();
         $config = $values['current_context_item']->getPluginConfigForPlugin($this->_identifier);
         if ( !empty($config[$this->_identifier.'_server_url']) ) {
            $retour[$this->_identifier.'_server_url'] = $config[$this->_identifier.'_server_url'];
         }
      }
      return $retour;
   }

   public function isConfigurableInRoom ( $room_type = '' ) {
      $retour = true;
      return $retour;
   }

   public function getDetailActionAsHTML () {
      $retour = '';
      $title = $this->_translator->getMessage('VOYEUR_ACTION_ICON_TITLE');
      $img =  '<img src="'.$this->_image_path.'/voyeur_icon_22x22.png" style="vertical-align:bottom;" title="'.$title.'"/>';
      $url_params = array();
      $url_params['iid'] = $this->_environment->getValueOfParameter('iid');
      $session_item = $this->_environment->getSessionItem();
      if ( isset($session_item) ) {
         $url_params['SID'] = $session_item->getSessionID();
      }
      unset($session_item);
      $url = curl($this->_environment->getCurrentContextID(),$this->_identifier,'reload',$url_params);
      $retour .= '<a href="'.$url.'" target="_blank">'.$img.'</a>';
      return $retour;
   }

   public function getVoyeurURL () {
      $retour = '';
      $url = $this->_getConfigValueFor($this->_identifier.'_server_url');
      if ( !empty($url) ) {
         $url_params = array();
         $url_params['iid'] = $this->_environment->getValueOfParameter('iid');
         $session_item = $this->_environment->getSessionItem();
         if ( isset($session_item) ) {
            $url_params['SID'] = $session_item->getSessionID();
         }
         unset($session_item);

         global $c_commsy_domain, $c_commsy_url_path;
         $url_to_zip = $c_commsy_domain.$c_commsy_url_path;
         $url_to_zip .= '/'._curl(false,$this->_environment->getCurrentContextID(),$this->_identifier,'download',$url_params);
         if ( strstr($url,'?') ) {
            $url .= '&';
         } else {
            $url .= '?';
         }
         $url .= 'inputFormat=ZIP';
         $url .= '&input='.urlencode($url_to_zip);
         $url = str_replace('&&','&',$url);
      }
      if ( !empty($url) ) {
         $retour = $url;
      }
      return $retour;
   }
}
?>