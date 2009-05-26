<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_plugins_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the plugins
   */
   var $_array_plugins = NULL;

  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function cs_configuration_plugins_form ($params) {
      $this->cs_rubric_form($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form
    */
   function _initForm () {

      // headline
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_PLUGIN_LINK');

      // plugins
      global $c_plugin_array;
      if (isset($c_plugin_array) and !empty($c_plugin_array)) {
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($plugin);
            if ( method_exists($plugin_class,'isConfigurableInPortal') ) {
               if ( $plugin_class->isConfigurableInPortal() ) {
                  $temp_array = array();
                  $temp_array2 = array();
                  $temp_array2['text']  = getMessage('COMMON_ON');
                  $temp_array2['value'] = 1;
                  $temp_array[] = $temp_array2;
                  $temp_array2 = array();
                  $temp_array2['text']  = getMessage('COMMON_OFF');
                  $temp_array2['value'] = -1;
                  $temp_array[] = $temp_array2;

                  $this->_array_plugins[$plugin_class->getIdentifier()]['values'] = $temp_array;
                  $this->_array_plugins[$plugin_class->getIdentifier()]['title'] = $plugin_class->getTitle();
               }
            }
         }
      }
      ksort($this->_array_plugins);
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $this->setHeadline($this->_headline);
      $this->_form->addText('text','',$this->_translator->getMessage('CONFIGURATION_PLUGIN_DESC'),'');

      // plugins
      if ( !empty($this->_array_plugins ) ) {
         foreach ( $this->_array_plugins as $plugin => $plugin_data) {
            $this->_form->addRadioGroup($plugin,$plugin_data['title'],'',$plugin_data['values'],'',true,true);
         }
         // buttons
         $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'','','','','','');
      } else {
         // TEXT
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
      } else {
         $current_context_item = $this->_environment->getCurrentContextItem();
         global $c_plugin_array;
         if (isset($c_plugin_array) and !empty($c_plugin_array)) {
            foreach ($c_plugin_array as $plugin) {
               $plugin_class = $this->_environment->getPluginClass($plugin);
               if ( method_exists($plugin_class,'isConfigurableInPortal') ) {
                  if ( $plugin_class->isConfigurableInPortal() ) {
                     if ( $current_context_item->isPluginOn($plugin) ) {
                        $this->_values[$plugin] = 1;
                     } else {
                        $this->_values[$plugin] = -1;
                     }
                  }
               }
            }
         }
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   }

   /** reset rubric form
    *  reset this rubric form (item, values, postvars and the form [elements])
    */
   function reset () {
      parent::reset();
      unset($this->_array_plugins);
   }
}
?>