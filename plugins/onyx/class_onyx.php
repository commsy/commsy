<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2011 Dr. Iver Jackewitz
//
// This file is part of the onyx plugin for CommSy.
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
class class_onyx extends cs_plugin {
   
   private $_format_media_key = '(:qti';
   private $_player = NULL;
   private $_player_url_base = NULL;
   private $_player_url_wsdl = NULL;
   private $_player_url_run = NULL;
   private $_player_lms_key = NULL;
    
   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   public function __construct ($environment) {
      parent::__construct($environment);
      $this->_translator->addMessageDatFolder('plugins/onyx/messages');
      $this->_identifier = 'onyx';
      $this->_title      = 'Onyx';
      $this->_image_path = 'plugins/'.$this->getIdentifier();
      
      $this->_player_url_base = $this->_getConfigValueFor($this->_identifier.'_player_url');
      $this->_player_url_wsdl = $this->_player_url_base.'/services?wsdl'; 
      $this->_player_url_run = $this->_player_url_base.'/onyxrun'; 
      $this->_player_lms_key = $this->_getConfigValueFor($this->_identifier.'_lms_name');
   }

   public function getDescription () {
      return $this->_translator->getMessage('ONYX_DESCRIPTION');
   }

   public function getHomepage () {
      return '';
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
            $retour->addTextfield( $this->_identifier.'_player_url',
                                   '',
                                   $this->_translator->getMessage('ONYX_CONFIG_FORM_TITLE_CONFIG'),
                                   $this->_translator->getMessage('ONYX_CONFIG_FORM_TITLE_CONFIG_DESC',$this->getTitle()),
                                   255,
                                   50,
                                   false,
                                   '',
                                   '',
                                   '',
                                   'left',
                                   $this->_translator->getMessage('ONYX_CONFIG_FORM_TITLE_URL'),
                                   '',
                                   false,
                                   '',
                                   10,
                                   true,
                                   false);
            $retour->combine();
            $retour->addTextfield( $this->_identifier.'_lms_name',
                                   '',
                                   $this->_translator->getMessage('ONYX_CONFIG_FORM_TITLE_CONFIG'),
                                   $this->_translator->getMessage('ONYX_CONFIG_FORM_TITLE_CONFIG_DESC',$this->getTitle()),
                                   255,
                                   50,
                                   false,
                                   '',
                                   '',
                                   '',
                                   'left',
                                   $this->_translator->getMessage('ONYX_CONFIG_FORM_LMS_NAME'),
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
         $config_array = array();
         if ( isset( $values[$this->_identifier.'_player_url'] ) ) {
            $config_array[$this->_identifier.'_player_url'] = $values[$this->_identifier.'_player_url'];
         }
         if ( isset( $values[$this->_identifier.'_lms_name'] ) ) {
            $config_array[$this->_identifier.'_lms_name'] = $values[$this->_identifier.'_lms_name'];
         }
         $values['current_context_item']->setPluginConfigForPlugin($this->_identifier,$config_array);
      } elseif ( $type == 'load_values_item'
                 and !empty($values['current_context_item'])
               ) {
         $retour = array();
         $config = $values['current_context_item']->getPluginConfigForPlugin($this->_identifier);
         if ( !empty($config[$this->_identifier.'_player_url']) ) {
            $retour[$this->_identifier.'_player_url'] = $config[$this->_identifier.'_player_url'];
         }
         if ( !empty($config[$this->_identifier.'_lms_name']) ) {
            $retour[$this->_identifier.'_lms_name'] = $config[$this->_identifier.'_lms_name'];
         }
      }
      return $retour;
   }
   
   public function getMediaRegExp () {
      $retour = array();
      $retour[$this->_format_media_key] = '~\\(:qti (.*?)(\\s.*?)?\\s*?:\\)~eu';
      return $retour;
   }
   
   public function formatMedia ( $params ) {
      $retour = '';
      if ( !empty($params['key'])
           and $params['key'] == $this->_format_media_key
           and !empty($params['value_new'])
           and strstr($params['value_new'],$this->_format_media_key)
           and !empty($this->_player_url_base)
         ) {
         $key = $params['key'];
         $value_new = $params['value_new'];
         $args_array = array();
         if ( !empty($params['args_array']) ) {
            $args_array = $params['args_array'];
         }

         if ( !empty($args_array[2]) ) {
            $args = $this->_text_converter->_parseArgs($args_array[2]);
            if ( !empty($args) ) {
               $args_array = array_merge($args_array,$args);
            }
         }
         
         $file_array = array();
         if ( !empty($params['file_array']) ) {
            $file_array = $params['file_array'];
         }
         $temp_file_name = htmlentities($args_array[1], ENT_NOQUOTES, 'UTF-8');
         if ( !empty($file_array[$temp_file_name]) ) {
            $file = $file_array[$temp_file_name];
         } elseif ( !empty($file_array[html_entity_decode($temp_file_name,ENT_COMPAT,'UTF-8')]) ) {
            $file = $file_array[html_entity_decode($temp_file_name,ENT_COMPAT,'UTF-8')];
         }

         // params
         $embedded = false;
         if ( !empty($args_array['embedded']) ) {
            $embedded = true;
         }

         $name = '';
         if ( !empty($args_array['text']) ) {
            $name = $args_array['text'];
         } elseif ( !empty($file) ) {
            $name = $file->getDisplayName();
         }

         $navi = NULL;
         if ( isset($args_array['navi']) ) {
            $navi = $args_array['navi'];
         }

         if ( !empty($args['target'])
              and ( $args['target'] == '_blank'
                    or $args['target'] == 'newwin'
                    or $args['target'] == 'tab'
                  )
            ) {
            $display = $args['target'];
         } elseif ( !empty($args['newwin']) ) {
            $display = 'newwin';
         } else {
            $display = 'window';
         }

         if ( isset($file) ) {

            // now connect onyx web player
            $player = $this->_getPlayerObject();
            
            // first: Anmeldung des Tests
            $session_item = $this->_environment->getSessionItem();
            
            $id = $session_item->getSessionID();
            $qti = $file->getString();
            $lang = $this->_environment->getSelectedLanguage();
            $inst = '';
            if ( !isset($navi) ) { 
               if ($embedded) {
                  $navi = 'onyxwithoutnav';
               } else {
                  $navi = '';
               }
            } else {
               if ( !empty($navi) and $navi == 'true' ) {
                  $navi = '';                  
               } else {
                  $navi = 'onyxwithoutnav';                  
               }
            }
            $lms = $this->_player_lms_key;
            $solution = 'true';
            
            $success = $player->run($id,$qti,$lang,$inst,$navi,$lms,$solution);  
            if ( $success and !is_soap_fault($success) ) {
               if ($embedded) {
                  $retour .= '<style type="text/css">
   <!--
      iframe.onyx {
         width: 630px;
         height: 500px;
         border: 0px;
      }
   -->
   </style>';
                  $retour .= '<div><iframe class="onyx" src="'.$this->_player_url_run.'?id='.$id.'"></iframe></div>';
               } else {
                  if ( $display == 'newwin'
                       or $display == '_blank'
                       or $display == 'tab'
                     ) {
                     $target = 'target="_blank"';
                     $onclick = '';
                  } else {
                     $target = 'target="help"';
                     $onclick = 'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, dependent=yes, copyhistory=yes, width=900, height=600\');"';
                  }
                  
                  $c_single_entry_point = $this->_environment->getConfiguration('c_single_entry_point');
                  $retour = '<a href="'.$c_single_entry_point.'?cid='.$this->_environment->getCurrentContextID().'&amp;mod='.$this->_identifier.'&amp;fct=showqti&amp;iid='.$id.'" '.$target.' '.$onclick.'>'.$name.'</a>';                  
               }
            }            
         }
      }
      return $retour;
   }
   
   private function _getPlayerObject () {
      $retour = NULL;
      if ( empty($this->_player) ) {
         if ( class_exists('SoapClient') ) {            
            $options = array("trace" => 1, "exceptions" => 0, 'user_agent'=>'PHP-SOAP/php-version', 'connection_timeout' => 150);
            if ( $this->_environment->getConfiguration('c_proxy_ip') ) {
              $options['proxy_host'] = $this->_environment->getConfiguration('c_proxy_ip');
            }
            if ( $this->_environment->getConfiguration('c_proxy_port') ) {
              $options['proxy_port'] = $this->_environment->getConfiguration('c_proxy_port');
            }
            $this->_player = new SoapClient($this->_player_url_wsdl, $options);
         } else {
            $html .= 'SOAP-Funktionen von PHP stehen nicht zur Verfügung. Bitte aktivieren Sie diese Funktionen oder lassen Sie diese aktivieren.';
         }
      }
      if ( !empty($this->_player) ) {
         $retour = $this->_player;
      }
      return $retour;
   }
   
   public function getPlayerRunUrl () {
      return $this->_player_url_run;
   }
   
   public function getTestFormatingInformationAsHTML () {
      $retour = $this->_translator->getMessage('ONYX_TEXTFORMATING_DESCRIPTION');
      return $retour;
   }
}
?>
