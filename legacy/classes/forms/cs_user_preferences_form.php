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
include_once('functions/text_functions.php');

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_user_preferences_form extends cs_rubric_form {

   /*
   * object -contains the user in this
   */
   var $_user;

   var $_options;

   var $_language_options;

   var $_choice_options;

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /** constructor: cs_user_preferences_form
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function __construct($params ) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {

      if (!empty($this->_item)) {
         $this->_user = $this->_item;
      } else {
         $user_manager = $this->_environment->getUserManager();
         $this->_user = $user_manager->getItem($this->_form_post['iid']);
      }

      // language options
      $context = $this->_environment->getCurrentContextItem();
      if ($context->getLanguage() == 'user') {
         $i=0;
         $this->_language_options = array();
         $this->_language_options[$i]['value'] = 'browser';
         $this->_language_options[$i]['text'] = $this->_translator->getMessage('USER_BROWSER_LANGUAGE');
         $i++;
         $this->_language_options[$i]['value'] = 'disabled';
         $this->_language_options[$i]['text'] = '------------------';
         $i++;

         $languages = $this->_environment->getAvailableLanguageArray();
         foreach ($languages as $language) {
            $this->_language_options[$i]['value'] = $language;
            $this->_language_options[$i]['text']  = $this->_translator->getLanguageLabelOriginally($language);
            $i++;
         }
      }

      // visible options
      $this->_options = array();
//      $this->_options[0]['text'] = $this->_translator->getMessage('VISIBLE_NEVER');
//      $this->_options[0]['value'] = '1';
      $this->_options[0]['text'] = $this->_translator->getMessage('VISIBLE_ONLY_LOGGED');
      $this->_options[0]['value'] = '1';
      $this->_options[1]['text'] = $this->_translator->getMessage('VISIBLE_ALWAYS');
      $this->_options[1]['value'] = '2';

      // choice options
      $this->_choice_options = array();
      $this->_choice_options[0]['text'] = $this->_translator->getMessage('COMMON_YES');
      $this->_choice_options[0]['value'] = 'yes';
      $this->_choice_options[1]['text'] = $this->_translator->getMessage('COMMON_NO');
      $this->_choice_options[1]['value'] = 'no';
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $disable_save_button = true;

      $this->_form->addHeadline('headline',$this->_translator->getMessage('USER_EDIT_PREFERENCES'));
      $this->_form->addHidden('iid','');

      $context = $this->_environment->getCurrentContextItem();
      if ($context->getLanguage() == 'user') {
         $this->_form->addSelect('language',
                                 $this->_language_options,
                                 '',
                                 $this->_translator->getMessage('USER_LANGUAGE'),
                                 $this->_translator->getMessage('USER_LANGUAGE_DESC'),
                                 '',
                                 '',
                                 true
                                );
         $disable_save_button = false;
      }

      if ( $this->_environment->inCommunityRoom() ) {
         $current_context = $this->_environment->getCurrentContextItem();
         if ( $current_context->isOpenForGuests() ) {
            $this->_form->addRadioGroup('commsy_visible',
                                        $this->_translator->getMessage('VISIBLE_PROPERTY'),
                                        $this->_translator->getMessage('VISIBLE_PROPERTY_DESC'),
                                        $this->_options,
                                        '',
                                        true,
                                        false
                                       );
            $disable_save_button = false;
         }
         $current_user = $this->_environment->getCurrentUserItem();
         if ( $this->_user->isModerator() ) {
            $context = $this->_environment->getCurrentContextItem();
            $this->_form->addRadioGroup('want_mail_get_account',
                                        $this->_translator->getMessage('USER_MAIL_GET_ACCOUNT'),
                                        $this->_translator->getMessage('USER_MAIL_GET_ACCOUNT_DESC',
                                        $this->_translator->getMessage('MAIL_AT_CAMPUS'),
                                        $context->getTitle()),
                                        $this->_choice_options,
                                        '',
                                        true,
                                        true
                                       );
            if ($context->isOpenForGuests()) {
               $this->_form->addRadioGroup('want_mail_publish_material',
                                           $this->_translator->getMessage('USER_MAIL_PUBLISH_MATERIAL'),
                                           $this->_translator->getMessage('USER_MAIL_PUBLISH_MATERIAL_DESC'),
                                           $this->_choice_options,
                                           '',
                                           true,
                                           true
                                          );
               $disable_save_button = false;
            }
         }
      } elseif ( !$context->isPrivateRoom() ) {
         if ( $this->_user->isModerator() ) {
            $context = $this->_environment->getCurrentContextItem();
            $this->_form->addRadioGroup('want_mail_get_account',
                                        $this->_translator->getMessage('USER_MAIL_GET_ACCOUNT'),
                                        $this->_translator->getMessage('USER_MAIL_GET_ACCOUNT_DESC',
                                        $this->_translator->getMessage('MAIL_AT_CAMPUS'),
                                        $context->getTitle()),
                                        $this->_choice_options,
                                        '',
                                        true,
                                        true
                                       );
            $disable_save_button = false;
         }
      }

      if ( !$context->isPrivateRoom()
           and $this->_user->isModerator()
         ) {
         $this->_form->addRadioGroup('want_mail_open_room',
                                     $this->_translator->getMessage('USER_MAIL_ROOM'),
                                     '',
                                     $this->_choice_options,
                                     '',
                                     true,
                                     true
                                    );
         $disable_save_button = false;
      }

      if ( $context->isPrivateRoom() ) {
         $this->_form->addRadioGroup('autosave_status',
                                     $this->_translator->getMessage('CONFIGURATION_AUTOSAVE_STATUS'),
                                     '',
                                     $this->_choice_options,
                                     '',
                                     true,
                                     true
                                    );
         $disable_save_button = false;
      }

      $this->_form->addButtonBar('option',
                                 $this->_translator->getMessage('COMMON_CHANGE_BUTTON'),
                                 $this->_translator->getMessage('COMMON_CANCEL_BUTTON'),
                                 '',
                                 '',
                                 '',
                                 '',
                                 $disable_save_button
                                );
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();

      if (isset($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['want_mail_get_account'] = $this->_item->getAccountWantMail();
         $this->_values['want_mail_publish_material'] = $this->_item->getPublishMaterialWantMail();
         $this->_values['want_mail_open_room'] = $this->_item->getOpenRoomWantMail();
         $this->_values['commsy_visible'] = $this->_item->getVisible();
         if ( $this->_environment->inPrivateRoom() ) {
            if ($this->_item->isAutoSaveOn()) {
               $this->_values['autosave_status'] = 'yes';
            } else {
               $this->_values['autosave_status'] = 'no';
            }
         }

         $context = $this->_environment->getCurrentContextItem();
         if ($context->getLanguage() == 'user') {
            $this->_values['language'] = $this->_item->getLanguage();
         }

      } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
      }
   }
}
?>