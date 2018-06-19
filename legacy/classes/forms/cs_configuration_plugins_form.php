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
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form
    */
   function _initForm () {

      // headline
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_PLUGIN_LINK');
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/32x32/config/plugin.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_HTMLTEXTAREA_FORM_TITLE').'"/>';
      } else {
         $image = '<img src="images/commsyicons/32x32/config/plugin.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_HTMLTEXTAREA_FORM_TITLE').'"/>';
      }
      if ( !empty($image) ) {
         $this->_headline = $image.' '.$this->_headline;
      }

      // plugins
      global $c_plugin_array;
      if (isset($c_plugin_array) and !empty($c_plugin_array)) {
         $current_portal_item = $this->_environment->getCurrentPortalItem();
         foreach ($c_plugin_array as $plugin) {
            $plugin_class = $this->_environment->getPluginClass($plugin);
            $current_context_item = $this->_environment->getCurrentContextItem();
            if ( (
                   $this->_environment->inServer()
                   and method_exists($plugin_class,'isConfigurableInServer')
                   and $plugin_class->isConfigurableInServer()
                 )
                 or
            	  (
                   $this->_environment->inPortal()
                   and method_exists($plugin_class,'isConfigurableInPortal')
                   and $plugin_class->isConfigurableInPortal()
                 )
                 or
                 (
                   !$this->_environment->inServer()
                   and $current_portal_item->isPluginOn($plugin)
                   and method_exists($plugin_class,'isConfigurableInRoom')
                   and $plugin_class->isConfigurableInRoom($current_context_item->getItemType())
                 )
               ) {
               $temp_array = array();
               $temp_array2 = array();
               $temp_array2['text']  = $this->_translator->getMessage('COMMON_ON');
               $temp_array2['value'] = 1;
               $temp_array[] = $temp_array2;
               $temp_array2 = array();
               $temp_array2['text']  = $this->_translator->getMessage('COMMON_OFF');
               $temp_array2['value'] = -1;
               $temp_array[] = $temp_array2;

               $this->_array_plugins[$plugin_class->getIdentifier()]['values'] = $temp_array;
               $this->_array_plugins[$plugin_class->getIdentifier()]['title'] = $plugin_class->getTitle();
               if ( method_exists($plugin_class,'getDescription') ) {
                  $this->_array_plugins[$plugin_class->getIdentifier()]['description'] = $plugin_class->getDescription();
               }
               if ( method_exists($plugin_class,'getHomepage') ) {
                  $this->_array_plugins[$plugin_class->getIdentifier()]['homepage'] = $plugin_class->getHomepage();
               }
               if ( $this->_environment->inServer()
                    and method_exists($plugin_class,'configurationAtServer')
                  ) {
                  $this->_array_plugins[$plugin_class->getIdentifier()]['change_form'] = $plugin_class->configurationAtServer('change_form');
               } elseif ( $this->_environment->inPortal()
                    and method_exists($plugin_class,'configurationAtPortal')
                  ) {
                  $this->_array_plugins[$plugin_class->getIdentifier()]['change_form'] = $plugin_class->configurationAtPortal('change_form');
               } elseif ( !$this->_environment->inServer()
                          and method_exists($plugin_class,'configurationAtRoom')
                        ) {
                  $this->_array_plugins[$plugin_class->getIdentifier()]['change_form'] = $plugin_class->configurationAtRoom('change_form');
               }
            }
         }
      }
      if ( !empty($this->_array_plugins) ) {
         ksort($this->_array_plugins);
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $this->setHeadline($this->_headline);
      $this->_form->addText('text','',$this->_translator->getMessage('CONFIGURATION_PLUGIN_DESC'),'');
      
      global $symfonyContainer;
      $c_wordpress = $symfonyContainer->getParameter('commsy.wordpress.enabled');
      
      if ( isset($c_wordpress) and  $c_wordpress and $this->_environment->inPortal()) {
      	$this->_form->addEmptyLine();
      	
      	$temp_array = array();
      	$temp_array2 = array();
      	$temp_array2['text']  = $this->_translator->getMessage('COMMON_ON');
      	$temp_array2['value'] = 1;
      	$temp_array[] = $temp_array2;
      	$temp_array2 = array();
      	$temp_array2['text']  = $this->_translator->getMessage('COMMON_OFF');
      	$temp_array2['value'] = -1;
      	$temp_array[] = $temp_array2;
      	
      	$current_portal_item = $this->_environment->getCurrentPortalItem();
      	if($current_portal_item->getWordpressPortalActive()){
      		$value = 'ein';
      	} else {
      		$value = 'aus';
      	}
      	$wordpress_url = $current_portal_item->getWordpressUrl();
      	$this->_form->addRadioGroup('wp',$this->_translator->getMessage('CONFIGURATION_EXTRA_WORDPRESS_PORTAL'),'',$temp_array,$value,true,true);
      	$this->_form->addTextfield('wp_url',$wordpress_url,'Wordpress URL', '', '', '', '');
      	$this->_form->addText('description','',$this->_translator->getMessage('CONFIGURATION_EXTRA_WORDPRESS_PORTAL_DESC'));
//       	$this->_array_extra[24]['text']  = $this->_translator->getMessage('CONFIGURATION_EXTRA_WORDPRESS');
//       	$this->_array_extra[24]['value'] = 'CONFIGURATION_EXTRA_WORDPRESS';
      }

      // plugins
      if ( !empty($this->_array_plugins ) ) {
         foreach ( $this->_array_plugins as $plugin => $plugin_data) {
            $this->_form->addEmptyLine();
            $this->_form->addRadioGroup($plugin,$plugin_data['title'],'',$plugin_data['values'],'',true,true);
            if ( !empty($plugin_data['description']) ) {
               $this->_form->combine();
               $this->_form->addText('description','',$plugin_data['description']);
            }
            if ( !empty($plugin_data['homepage']) ) {
               $this->_form->combine();
               $this->_form->addText('homepage','',$this->_translator->getMessage('CONFIGURATION_PLUGIN_HOMEPAGE').': '.$plugin_data['homepage']);
            }
            if ( !empty($plugin_data['change_form']) ) {
               $plugin_class = $this->_environment->getPluginClass($plugin);
               if ( $this->_environment->inServer() ) {
                  if ( method_exists($plugin_class,'configurationAtServer') ) {
                     $plugin_class->configurationAtServer('create_form',array('form' => $this->_form));
                  }
               } elseif ( $this->_environment->inPortal() ) {
                  if ( method_exists($plugin_class,'configurationAtPortal') ) {
                     $plugin_class->configurationAtPortal('create_form',array('form' => $this->_form));
                  }
               } elseif ( !$this->_environment->inServer() ) {
                  $current_portal_item = $this->_environment->getCurrentPortalItem();
                  if ( method_exists($plugin_class,'configurationAtRoom')
                      and $current_portal_item->isPluginOn($plugin)
                     ) {
                     $plugin_class->configurationAtRoom('create_form',array('form' => $this->_form));
                  }
               }
            }
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
            $current_portal_item = $this->_environment->getCurrentPortalItem();
            foreach ($c_plugin_array as $plugin) {
               $plugin_class = $this->_environment->getPluginClass($plugin);
               if ( ( $this->_environment->inServer()
                      and method_exists($plugin_class,'isConfigurableInServer')
                      and $plugin_class->isConfigurableInServer()
                    )
                    or
               	  ( $this->_environment->inPortal()
                      and method_exists($plugin_class,'isConfigurableInPortal')
                      and $plugin_class->isConfigurableInPortal()
                    )
                    or
                    ( !$this->_environment->inServer()
                      and $current_portal_item->isPluginOn($plugin)
                      and method_exists($plugin_class,'isConfigurableInRoom')
                      and $plugin_class->isConfigurableInRoom($current_context_item->getItemType())
                    )
                  ) {
                  if ( $current_context_item->isPluginOn($plugin) ) {
                     $this->_values[$plugin] = 1;
                  } else {
                     $this->_values[$plugin] = -1;
                  }
                  $values = array();
                  $values['current_context_item'] = $current_context_item;
                  if ( $this->_environment->inServer()
                       and method_exists($plugin_class,'configurationAtServer')
                     ) {
                     $this->_values = array_merge($plugin_class->configurationAtServer('load_values_item',$values),$this->_values);
                  } elseif ( $this->_environment->inPortal()
                       and method_exists($plugin_class,'configurationAtPortal')
                     ) {
                     $this->_values = array_merge($plugin_class->configurationAtPortal('load_values_item',$values),$this->_values);
                  } elseif ( !$this->_environment->inServer()
                             and method_exists($plugin_class,'configurationAtRoom')
                           ) {
                     $this->_values = array_merge($plugin_class->configurationAtRoom('load_values_item',$values),$this->_values);
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