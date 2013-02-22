<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
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

$class_factory->includeClass(RUBRIC_FORM);

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class class_configuration_plugin_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * string - containing the headline of the form for main sponsors
   */
   var $_headline_main_sponsors = NULL;

  /**
   * int - counter for main sponsors
   */
   var $_main_counter = 0;

  /**
   * string - containing the button text of the add sponsor button for main sponsors
   */
   var $_button_main_sponsors = NULL;

  /**
   * string - containing the headline of the form for normal sponsors
   */
   var $_headline_normal_sponsors = NULL;

  /**
   * int - counter for normal sponsors
   */
   var $_normal_counter = 0;

  /**
   * string - containing the button text of the add sponsor button for normal sponsors
   */
   var $_button_normal_sponsors = NULL;

  /**
   * string - containing the headline of the form for little sponsors
   */
   var $_headline_little_sponsors = NULL;

  /**
   * int - counter for little sponsors
   */
   var $_little_counter = 0;

  /**
   * string - containing the button text of the add sponsor button for little sponsors
   */
   var $_button_little_sponsors = NULL;

  /**
   * array - containing choices for showing ads
   */
   var $_show_ads_choice = array();

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function class_configuration_plugin_form ($environment) {
      $this->cs_rubric_form($environment);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      $translator = $this->_environment->getTranslationObject();

      // headlines
      $this->_headline = $translator->getMessage('CONFIGURATION_SPONSOR_LINK');
      $this->_headline_normal_sponsors = $translator->getMessage('ADS_CONFIGURATION_FORM_NORMAL_SPONSORS_HEADLINE');

      // choice show ads
      $this->_show_ads_choice[0]['text'] = $translator->getMessage('COMMON_YES');
      $this->_show_ads_choice[0]['value'] = 1;
      $this->_show_ads_choice[1]['text'] = $translator->getMessage('COMMON_NO');
      $this->_show_ads_choice[1]['value'] = -1;

      if ( $this->_normal_counter == 0) {
         if ( isset($this->_item) ) {
            $this->_normal_counter = $this->_item->getCountNormalSponsors();
         } else {
            if ( isset($_POST['normal_url']) ) {
               $this->_normal_counter = count($_POST['normal_url']);
            }
         }
      }
      if ( $this->_normal_counter == 0) {
         $this->_button_normal_sponsors = $translator->getMessage('ADS_AD_NORMAL_SPONSOR_BUTTON');
      } else {
         $this->_button_normal_sponsors = $translator->getMessage('ADS_ADD_NEXT_NORMAL_SPONSOR_BUTTON');
      }

   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $translator = $this->_environment->getTranslationObject();

      $this->setHeadline($this->_headline);
      $this->_form->addRadioGroup('show_google_ads',$this->_translator->getMessage('ADS_SHOW_GOOGLE_ADS_CHOICE_FORM_TITLE'),'',$this->_show_ads_choice,'-1',true,true);
      $this->_form->addRadioGroup('show_amazon_ads',$this->_translator->getMessage('ADS_SHOW_AMAZON_ADS_CHOICE_FORM_TITLE'),'',$this->_show_ads_choice,'-1',true,true);
      $this->_form->addRadioGroup('show_ads',$this->_translator->getMessage('ADS_SHOW_ADS_CHOICE_FORM_TITLE'),'',$this->_show_ads_choice,'-1',true,true);

      // normal sponsors
      $this->_form->addHeadline('headline',$this->_headline_normal_sponsors);
      $this->_form->addTextField('normal_title','',$this->_translator->getMessage('ADS_COMMON_SPONSOR_FORM_ELEMENT_HEADLINE'),'',200,'',false);
      $first = true;
      for ($i=0; $i<$this->_normal_counter; $i++) {
         $this->_form->addEmptyLine('empty');
         $this->_form->addButton('normal_delete_'.$i,$this->_translator->getMessage('ADS_DELETE_BUTTON'),$this->_translator->getMessage('ADS_FORM_ELEMENT_TITLE_SPONSOR_NO',$i+1));
         if ($i > 0) {
            $this->_form->combine('horizontal');
            $this->_form->addButton('normal_up_'.$i,$this->_translator->getMessage('ADS_UP_BUTTON'),'','','');
         }
         if ($i < $this->_normal_counter-1) {
            $this->_form->combine('horizontal');
            $this->_form->addButton('normal_down_'.$i,$this->_translator->getMessage('ADS_DOWN_BUTTON'),'','','');
         }
         $this->_form->addImage('normal_name['.$i.']','',$this->_translator->getMessage('ADS_IMAGE_FORM_ELEMENT_TITLE'),'');
         $this->_form->addTextField('normal_url['.$i.']','',$this->_translator->getMessage('ADS_URL_FORM_ELEMENT_TITLE'),'',200,'',false);
      }
      unset($first);
      $this->_form->addButton('option',$this->_button_normal_sponsors);

      $this->_form->addEmptyLine('empty');
      $this->_form->addButtonBar('option',$translator->getMessage('COMMON_SAVE_BUTTON'),'','','','','','');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;

         // normal sponsors
         if ( !empty($this->_values['normal_name']['name']) ) {
            foreach ($this->_values['normal_name']['name'] as $key => $value) {
               if ( !empty($this->_values['normal_name']['tmp_name'][$key]) ) {
                  $temp_array = array();
                  $temp_array['name'] = $this->_values['normal_name']['tmp_name'][$key];
                  $temp_array['filename'] = $value;
                  $this->_values['normal_name['.$key.']'] = $temp_array;
                  unset($temp_array);
               }
               if ( !empty($this->_form_post['normal_url'][$key]) ) {
                  $this->_values['normal_url['.$key.']'] = $this->_form_post['normal_url'][$key];
               }
               if ( !empty($this->_form_post['normal_html'][$key]) ) {
                  $this->_values['normal_html['.$key.']'] = $this->_form_post['normal_html'][$key];
               }
            }
         }
         if ( !empty($this->_values['hidden_normal_name']) ) {
            foreach ($this->_values['hidden_normal_name'] as $key => $value) {
               $this->_values['normal_name['.$key.']'] = $value;
               if ( !empty($this->_form_post['normal_url'][$key]) ) {
                  $this->_values['normal_url['.$key.']'] = $this->_form_post['normal_url'][$key];
               }
               if ( !empty($this->_form_post['normal_html'][$key]) ) {
                  $this->_values['normal_html['.$key.']'] = $this->_form_post['normal_html'][$key];
               }
               if ( isset($this->_values['hidden_file_normal_name'][$key]) ) {
                  $temp_array = array();
                  $temp_array['name'] = $value;
                  $temp_array['filename'] = $this->_values['hidden_file_normal_name'][$key];
                  $this->_values['normal_name['.$key.']'] = $temp_array;
                  unset($temp_array);
               }
            }
         }
         if ( !empty($this->_values['hidden_delete_normal_name']) ) {
            $counter = 0;
            foreach ($this->_values['hidden_delete_normal_name'] as $value) {
               $this->_form->addHidden('hidden_delete_normal_name['.$counter.']','$value');
               $this->_values['hidden_delete_normal_name['.$counter.']'] = $value;
               $counter++;
            }
            unset($counter);
         }
      } elseif ( !empty($this->_item) ) {
         if ( $this->_item->showAds() ) {
            $this->_values['show_ads'] = 1;
         } else {
            $this->_values['show_ads'] = -1;
         }
         if ( $this->_item->showGoogleAds() ) {
            $this->_values['show_google_ads'] = 1;
         } else {
            $this->_values['show_google_ads'] = -1;
         }
         if ( $this->_item->showAmazonAds() ) {
            $this->_values['show_amazon_ads'] = 1;
         } else {
            $this->_values['show_amazon_ads'] = -1;
         }

         // normal
         if ( $this->_item->hasNormalSponsors() ) {
            $array = $this->_item->getNormalSponsorArray();
            $session_file_array = array();
            $counter = 0;
            foreach ($array as $sponsor) {
               if ( isset($sponsor['IMAGE']) and !empty($sponsor['IMAGE']) ) {
                  $this->_values['normal_name['.$counter.']'] = $sponsor['IMAGE'];
                  $session_file_array['normal_name']['name'][$counter] = $sponsor['IMAGE'];
                  $disc_manager = $this->_environment->getDiscManager();
                  $session_file_array['normal_name']['tmp_name'][$counter] = $disc_manager->_getFilePath().$sponsor['IMAGE'];
               }
               if ( isset($sponsor['URL']) and !empty($sponsor['URL']) ) {
                  $this->_values['normal_url['.$counter.']'] = $sponsor['URL'];
               }
               if ( isset($sponsor['HTML']) and !empty($sponsor['HTML']) ) {
                  $this->_values['normal_html['.$counter.']'] = $sponsor['HTML'];
               }
               $counter++;
            }
            if ( !empty($session_file_array) ) {
               $current_iid = $this->_environment->getCurrentContextID();
               $session_item = $this->_environment->getSessionItem();
               if ( isset($session_item) ) {
                  $session_item->unsetValue('ads_'.$current_iid.'_files_array');
                  $session_item->setValue('ads_'.$current_iid.'_files_array',$session_file_array);
               }
               unset($disc_manager);
               unset($session_file_array);
            }
            unset($counter);
         }
         $this->_values['normal_title'] = $this->_item->getNormalSponsorTitle();
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   }

   function setCounterMainSponsors ($value) {
      $this->_main_counter = (int)$value;
   }

   function setCounterNormalSponsors ($value) {
      $this->_normal_counter = (int)$value;
   }

   function setCounterLittleSponsors ($value) {
      $this->_little_counter = (int)$value;
   }
}
?>