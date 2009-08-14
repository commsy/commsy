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
}
?>