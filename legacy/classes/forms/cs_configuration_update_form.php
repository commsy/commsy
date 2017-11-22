<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
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

$this->includeClass(RUBRIC_FORM);

/** class for CommSy forms
 * this class implements an interface for the creation of forms in the CommSy style
 */
class cs_configuration_update_form extends cs_rubric_form {

   private $_version_code = '';
   private $_version_db = '';
   private $_path_to_scripts = '';
   private $_script_array = array();

   /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   public function __construct($params) {
      cs_rubric_form::__construct($params);
      $this->_path_to_scripts = 'scripts';
   }

   private function _getRelevantUpdateFolder ($version) {
      if ( substr_count($version,'.') > 2 ) {
         $version = substr($version,0,strrpos($version,'.'));
      }
      $retour = array();
      $dir_array = array();
      $directory_handle = @opendir($this->_path_to_scripts);
      if ($directory_handle) {
         while ( false !== ( $entry = readdir($directory_handle) ) ) {
            if ( is_dir($this->_path_to_scripts.'/'.$entry)
                 and strstr($entry,'_to_')
               ) {
               $dir_array[] = $entry;
            }
         }
         closedir($directory_handle);
      }
      if ( !empty($dir_array) ) {
         // sort($dir_array);
         usort($dir_array, 'version_compare');
         $over = false;
         $found = false;
         foreach ( $dir_array as $entry ) {
            if ( ( !$found
                    and strstr($entry,$version.'_to_')
                 ) or $over
               ) {
               $over = true;
               $folder_array = explode('_to_',$entry);
               $first_folder = $folder_array[0];
               $first_folder_array = explode('.',$first_folder);
               if ( $first_folder_array[0] > 6
                    or ( $first_folder_array[0] == 6
                         and $first_folder_array[1] > 3
                       )
                    or ( $first_folder_array[0] == 6
                         and $first_folder_array[1] == 3
                         and $first_folder_array[2] > 2
                       )
                  ) {
                  $found = true;
               }
            }
            if ( $found ) {
               $retour[] = $entry;
            }
         }
      }
      return $retour;
   }

   private function _getRelevantUpdateScripts ( $array ) {
      $retour = array();
      foreach ( $array as $folder ) {
         $retour[$folder] = array();
         $path = $this->_path_to_scripts.'/'.$folder;
         $directory_handle = @opendir($path);
         if ($directory_handle) {
            while ( false !== ( $entry = readdir($directory_handle) ) ) {
               if ( !is_dir($path.'/'.$entry)
                    and !strstr($entry,'master_update')
                    and !strstr($entry,'.cvsignore') // development
                    and !strstr($entry,'_old') // migration
                  ) {
                  $retour[$folder][] = $entry;
               }
            }
         }
         sort($retour[$folder]);
      }
      return $retour;
   }

   public function getScriptArray () {
      return $this->_script_array;
   }

   public function getRootScriptPath () {
      return $this->_path_to_scripts;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      // headline
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_UPDATE_LINK');

      // version code
      $this->_version_code = getCommSyVersion();

      // version db
      $server_item = $this->_environment->getServerItem();
      $this->_version_db   = $server_item->getDBVersion();
      unset($server_item);

      // now the scripts
      if ( $this->_version_db != $this->_version_code  ) {
         $script_array = $this->_getRelevantUpdateFolder($this->_version_db);
         if ( !empty($script_array) ) {
            $script_array = $this->_getRelevantUpdateScripts($script_array);
            if ( !empty($script_array) ) {
               $this->_script_array = $script_array;
            }
         }
      }
      if ( !empty($this->_script_array) ) {
         $scripts = false;
         foreach ($this->_script_array as $key => $script_array) {
            if ( !empty($script_array) ) {
               $scripts = true;
            }
         }
         if ( !$scripts ) {
            $current_version_array = explode('_to_',$key);
            if ( !empty($current_version_array[1])) {
               $current_version = $current_version_array[1];
               $current_code_version = getCommSyVersion();
               if ( !strstr($current_code_version,'beta')
                    and !strstr($current_code_version,' ')
                    and substr_count($current_code_version,'.') == 2
                  ) {
                  $current_context = $this->_environment->getServerItem();
                  $current_context->setDBVersion($current_version);
                  $current_context->save();

                  redirect($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),array());
               }
            }
         }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      $this->setHeadline($this->_headline);

      $this->_form->addText('version_code',$this->_translator->getMessage('CONFIGURATION_UPDATE_VERSION_CODE'),$this->_version_code);
      $this->_form->addText('version_db',$this->_translator->getMessage('CONFIGURATION_UPDATE_VERSION_DB'),$this->_version_db);

      foreach ( $this->_script_array as $folder => $script_array ) {
         $this->_form->addCheckbox($folder,1,false,str_replace('_to_',' -> ',$folder),$this->_translator->getMessage('CONFIGURATION_UPDATE_ALL'));
         foreach ( $script_array as $script ) {
            $this->_form->combine();
            $this->_form->addCheckbox($folder.'/'.$script,1,false,'',$script);
         }
      }

      // buttons
      $this->_form->addButtonBar( 'option',
                                  $this->_translator->getMessage('CONFIGURATION_UPDATE_BUTTON'),
                                  $this->_translator->getMessage('COMMON_CANCEL_BUTTON')
                                 );
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();

      if (isset($this->_item)) {
         $this->_values['version_db'] = $this->_item->getDBVersion();
      } elseif ( isset($this->_form_post) ) {
         $this->_values = $this->_form_post;
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   }
}
?>