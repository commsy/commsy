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
      $this->_title      = 'Voyant';
      $this->_image_path = 'plugins/'.$this->getIdentifier();
   }

   public function getDescription () {
      return $this->_translator->getMessage('VOYEUR_DESCRIPTION');
   }

   public function getHomepage () {
      return 'http://docs.voyant-tools.org';
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
      if ( $room_type == CS_PRIVATEROOM_TYPE ) {
         $retour = false;
      }
      return $retour;
   }

   public function getDetailActionAsHTML () {
      $retour = '';
      $title = $this->_translator->getMessage('VOYEUR_ACTION_ICON_TITLE');
      #$img =  '<img src="'.$this->_image_path.'/voyeur_icon_22x22.png" style="vertical-align:bottom;" title="'.$title.'"/>';
      $url_params = array();
      $url_params['iid'] = $this->_environment->getValueOfParameter('iid');
      $session_item = $this->_environment->getSessionItem();
      if ( isset($session_item) ) {
         $url_params['SID'] = $session_item->getSessionID();
      }
      unset($session_item);
      $url = curl($this->_environment->getCurrentContextID(),$this->_identifier,'reload',$url_params);
      $retour .= '<a href="'.$url.'" target="_blank" title="'.$title.'">'.ucfirst($this->_translator->getMessage('VOYEUR_DETAIL_ACTION_ANALYSE')).'</a>';
      return $retour;
   }

   public function getVoyeurURL ( $filename = '' ) {
      $retour = '';
      $url = $this->_getConfigValueFor($this->_identifier.'_server_url');
      if ( !empty($url) ) {
         $url_params = array();
         if ( empty($filename) ) {
            $url_params['iid'] = $this->_environment->getValueOfParameter('iid');
         } else {
            $url_params['filename'] = $filename;
         }
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
         #$url .= 'inputFormat=zip';
         $url .= '&input='.urlencode($url_to_zip);
         $url = str_replace('&&','&',$url);
      }
      if ( !empty($url) ) {
         $retour = $url;
      }
      return $retour;
   }

   /*
    * CommSy7
    */
   public function getAdditionalViewActionsAsHTML ( $params ) {
      $retour = '';
      $retour .= '   <option value="'.$this->_identifier.'_analyse">'.$this->_translator->getMessage('VOYEUR_LIST_ACTION_ANALYSE').'</option>'.LF;
      return $retour;
   }

   /*
    * CommSy8
    */
   public function getAdditionalListOptions ( $params ) {
      $retour = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => $this->_identifier.'_analyse', 'display' => '___VOYEUR_LIST_ACTION_ANALYSE___');
      return $retour;
   }

   public function performListAction ( $params ) {
      if ( $params['index_view_action'] == $this->_identifier.'_analyse'
           and !empty($params['attach'])
         ) {
         $id_array = array();
         foreach ($params['attach'] as $key => $value) {
            $id_array[] = $key;
         }
         $class_factory = $this->_environment->getClassFactory();
         $list2zip = $class_factory->getClass(MISC_LIST2ZIP,array('environment' => $this->_environment));
         $list2zip->setItemIDArray($id_array);
         $list2zip->setModule($this->_environment->getCurrentModule());
         $list2zip->execute();
         $zipname_wf = $list2zip->getZipFilenameWithFolder();
         $new_zip = $this->_changeZIP($zipname_wf);
         unlink($zipname_wf);
         
         $retour  = '';
         $retour .= '<script type=\'text/javascript\'>';
         $retour .= 'window.open(\''.$this->getVoyeurURL(basename($new_zip)).'\');';
         $retour .= '</script>';
         return $retour;

         /* CommSy7
         $params = array();
         $params['environment'] = $this->_environment;
         $params['with_modifying_actions'] = true;
         $box = $class_factory->getClass(OVERLAYBOX_VIEW,$params);
         $box->setTitle($this->_title);
         unset($params);

         $params = $this->_environment->getCurrentParameterArray();
         unset($params['iid']);
         unset($params['mode']);
         unset($params['download']);
         $back_link = curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params);
         $box->setBackLink($back_link);

         $text = $this->_translator->getMessage('VOYEUR_LIST_ACTION_TEXT',$this->getVoyeurURL(basename($new_zip)));
         $box->setText($text);
         global $page;
         $page->addOverlay($box);
         unset($_GET['download']);
         unset($_GET['mode']);
         */
      }
   }

   private function _changeZIP ($zip_name) {
      $retour = '';
      // change ZIP
      if ( file_exists($zip_name) ) {
         $zip = new ZipArchive();
         if ( $zip->open($zip_name) !== TRUE ) {
            include_once('functions/error_functions.php');
            trigger_error('can not modify zip',E_USER_WARNING);
         } else {
            // extract zip
            global $symfonyContainer;
            $export_temp_folder = $symfonyContainer->getParameter('commsy.settings.export_temp_folder');
            if ( !isset($export_temp_folder) ) {
               $export_temp_folder = 'var/temp/zip_export';
            }
            $voyeur_dir = $export_temp_folder.'/'.str_replace('.zip','_voyeur',basename($zip_name));
            if ( !is_dir($voyeur_dir) ) {
               mkdir($voyeur_dir, 0777);
               if ( is_dir($voyeur_dir) ) {
                  $zip->extractTo($voyeur_dir);
                  $zip->close();

                  // delete folder
                  $disc_manager = $this->_environment->getDiscManager();
                  $disc_manager->removeDirectory($voyeur_dir.'/css');
                  $disc_manager->removeDirectory($voyeur_dir.'/images');
               } else {
                  include_once('functions/error_functions.php');
                  trigger_error('can not make directory ('.$voyeur_dir.')',E_USER_ERROR);
               }
            }

            // make zip
            $zip = new ZipArchive();
            $voyeur_zip_name = str_replace('.zip','_voyeur.zip',$zip_name);
            if ( $zip->open($voyeur_zip_name, ZIPARCHIVE::CREATE) !== TRUE ) {
                include_once('functions/error_functions.php');
                trigger_error('can not open zip-file '.$voyeur_zip_name,E_USER_WARNNG);
            }
            $temp_dir = getcwd();
            chdir($voyeur_dir);

            $zip = addFolderToZip('.',$zip);
            chdir($temp_dir);

            $zip->close();
            unset($zip);

            $disc_manager = $this->_environment->getDiscManager();
            $disc_manager->removeDirectory($voyeur_dir);

            $retour = $voyeur_zip_name;
         }
      } else {
         include_once('functions/error_functions.php');
         trigger_error('can not open zip ('.$zip_name.')',E_USER_ERROR);
      }
      return $retour;
   }

   private function _sendZIP ( $value ) {
      if ( file_exists($value) ) {
         header('Content-type: application/zip');
         header('Content-Disposition: attachment; filename="'.basename($value).'"');
         readfile($value);
         exit();
      }
   }
}
?>