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


/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_mail_form extends cs_rubric_form {


  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;


  /**
   * array - containing the mail texts to choose
   */
   var $_array_mail_text = NULL;


  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }


   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {

      // headline
      $this->_headline = $this->_translator->getMessage('CONFIGURATION_MAIL_FORM_HEADLINE');
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/32x32/config/mail_options.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_MAIL_FORM_TITLE').'"/>';
      } else {
         $image = '<img src="images/commsyicons/32x32/config/mail_options.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION_MAIL_FORM_TITLE').'"/>';
      }
      if ( !empty($image) ) {
         $this->_headline = $image.' '.$this->_headline;
      }

      // mail text choice
      $this->_array_mail_text[0]['text']  = '*'.$this->_translator->getMessage('MAIL_CHOICE_CHOOSE_TEXT');
      $this->_array_mail_text[0]['value'] = -1;

      // mail salutation
      $this->_array_mail_text[1]['text']  = '----------------------';
      $this->_array_mail_text[1]['value'] = 'disabled';
      $this->_array_mail_text[2]['text']  = $this->_translator->getMessage('MAIL_CHOICE_HELLO');
      $this->_array_mail_text[2]['value'] = 'MAIL_CHOICE_HELLO';
      $this->_array_mail_text[3]['text']  = $this->_translator->getMessage('MAIL_CHOICE_CIAO');
      $this->_array_mail_text[3]['value'] = 'MAIL_CHOICE_CIAO';

      // user
      $this->_array_mail_text[4]['text']  = '----------------------';
      $this->_array_mail_text[4]['value'] = 'disabled';
      $this->_array_mail_text[5]['text']  = $this->_translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_DELETE');
      $this->_array_mail_text[5]['value'] = 'MAIL_CHOICE_USER_ACCOUNT_DELETE';
      $this->_array_mail_text[6]['text']  = $this->_translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_LOCK');
      $this->_array_mail_text[6]['value'] = 'MAIL_CHOICE_USER_ACCOUNT_LOCK';
      $this->_array_mail_text[7]['text']  = $this->_translator->getMessage('MAIL_CHOICE_USER_STATUS_USER');
      $this->_array_mail_text[7]['value'] = 'MAIL_CHOICE_USER_STATUS_USER';
      $this->_array_mail_text[8]['text']  = $this->_translator->getMessage('MAIL_CHOICE_USER_STATUS_MODERATOR');
      $this->_array_mail_text[8]['value'] = 'MAIL_CHOICE_USER_STATUS_MODERATOR';
      $this->_array_mail_text[9]['text']  = $this->_translator->getMessage('MAIL_CHOICE_USER_MAKE_CONTACT_PERSON');
      $this->_array_mail_text[9]['value'] = 'MAIL_CHOICE_USER_MAKE_CONTACT_PERSON';
      $this->_array_mail_text[10]['text']  = $this->_translator->getMessage('MAIL_CHOICE_USER_UNMAKE_CONTACT_PERSON');
      $this->_array_mail_text[10]['value'] = 'MAIL_CHOICE_USER_UNMAKE_CONTACT_PERSON';
      $this->_array_mail_text[40]['text']  = $this->_translator->getMessage('EMAIL_CHOICE_PASSWORD_EXPIRATION_SOON');
      $this->_array_mail_text[40]['value'] = 'EMAIL_CHOICE_PASSWORD_EXPIRATION_SOON';
      $this->_array_mail_text[41]['text']  = $this->_translator->getMessage('EMAIL_CHOICE_PASSWORD_EXPIRATION');
      $this->_array_mail_text[41]['value'] = 'EMAIL_CHOICE_PASSWORD_EXPIRATION';
      if ($this->_environment->inCommunityRoom()) {
         $this->_array_mail_text[11]['text']  = $this->_translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_PASSWORD');
         $this->_array_mail_text[11]['value'] = 'MAIL_CHOICE_USER_ACCOUNT_PASSWORD';
      }

      if ($this->_environment->inCommunityRoom()) {
         $this->_array_mail_text[12]['text']  = $this->_translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_MERGE');
         $this->_array_mail_text[12]['value'] = 'MAIL_CHOICE_USER_ACCOUNT_MERGE';
      }
          if ($this->_environment->inPortal()) {
         $this->_array_mail_text[13]['text']  = $this->_translator->getMessage('MAIL_CHOICE_USER_PASSWORD_CHANGE');
         $this->_array_mail_text[13]['value'] = 'MAIL_CHOICE_USER_PASSWORD_CHANGE';
          }

      // material
      $current_context = $this->_environment->getCurrentContextItem();
      if ($this->_environment->inCommunityRoom() and $current_context->isOpenForGuests()) {
         $this->_array_mail_text[20]['text']  = '----------------------';
         $this->_array_mail_text[20]['value'] = 'disabled';
         $this->_array_mail_text[21]['text']  = $this->_translator->getMessage('MAIL_CHOICE_MATERIAL_WORLDPUBLIC');
         $this->_array_mail_text[21]['value'] = 'MAIL_CHOICE_MATERIAL_WORLDPUBLIC';
         $this->_array_mail_text[22]['text']  = $this->_translator->getMessage('MAIL_CHOICE_MATERIAL_NOT_WORLDPUBLIC');
         $this->_array_mail_text[22]['value'] = 'MAIL_CHOICE_MATERIAL_NOT_WORLDPUBLIC';
      }

      // room
      if ($this->_environment->inPortal()) {
         $this->_array_mail_text[30]['text']  = '----------------------';
         $this->_array_mail_text[30]['value'] = 'disabled';
         $this->_array_mail_text[31]['text']  = $this->_translator->getMessage('MAIL_CHOICE_ROOM_LOCK');
         $this->_array_mail_text[31]['value'] = 'MAIL_CHOICE_ROOM_LOCK';
         $this->_array_mail_text[32]['text']  = $this->_translator->getMessage('MAIL_CHOICE_ROOM_UNLOCK');
         $this->_array_mail_text[32]['value'] = 'MAIL_CHOICE_ROOM_UNLOCK';
         $this->_array_mail_text[34]['text']  = $this->_translator->getMessage('MAIL_CHOICE_ROOM_DELETE');
         $this->_array_mail_text[34]['value'] = 'MAIL_CHOICE_ROOM_DELETE';
         $this->_array_mail_text[35]['text']  = $this->_translator->getMessage('MAIL_CHOICE_ROOM_OPEN');
         $this->_array_mail_text[35]['value'] = 'MAIL_CHOICE_ROOM_OPEN';
      }
      if ($this->_environment->inCommunityRoom()) {
         $this->_array_mail_text[30]['text']  = '----------------------';
         $this->_array_mail_text[30]['value'] = 'disabled';
         $this->_array_mail_text[33]['text']  = $this->_translator->getMessage('MAIL_CHOICE_ROOM_UNLINK');
         $this->_array_mail_text[33]['value'] = 'MAIL_CHOICE_ROOM_UNLINK';
      }
      
      // inactive rooms and users
      if ($this->_environment->inPortal()) {
         $this->_array_mail_text[50]['text']  = '----------------------';
         $this->_array_mail_text[50]['value'] = 'disabled';
         $this->_array_mail_text[51]['text']  = $this->_translator->getMessage('MAIL_CHOICE_PROJECT_ARCHIVE_INFO');
         $this->_array_mail_text[51]['value'] = 'MAIL_CHOICE_PROJECT_ARCHIVE_INFO';
         $this->_array_mail_text[52]['text']  = $this->_translator->getMessage('MAIL_CHOICE_PROJECT_ARCHIVE');
         $this->_array_mail_text[52]['value'] = 'MAIL_CHOICE_PROJECT_ARCHIVE';
         $this->_array_mail_text[53]['text']  = $this->_translator->getMessage('MAIL_CHOICE_PROJECT_DELETE_INFO');
         $this->_array_mail_text[53]['value'] = 'MAIL_CHOICE_PROJECT_DELETE_INFO';
         $this->_array_mail_text[54]['text']  = $this->_translator->getMessage('MAIL_CHOICE_PROJECT_DELETE');
         $this->_array_mail_text[54]['value'] = 'MAIL_CHOICE_PROJECT_DELETE';
         
         $this->_array_mail_text[55]['text']  = '----------------------';
         $this->_array_mail_text[55]['value'] = 'disabled';
         $this->_array_mail_text[56]['text']  = $this->_translator->getMessage('MAIL_CHOICE_INACTIVITY_LOCK_NEXT');
         $this->_array_mail_text[56]['value'] = 'MAIL_CHOICE_INACTIVITY_LOCK_NEXT';
         $this->_array_mail_text[57]['text']  = $this->_translator->getMessage('MAIL_CHOICE_INACTIVITY_LOCK_TOMORROW');
         $this->_array_mail_text[57]['value'] = 'MAIL_CHOICE_INACTIVITY_LOCK_TOMORROW';
         $this->_array_mail_text[58]['text']  = $this->_translator->getMessage('MAIL_CHOICE_INACTIVITY_LOCK_NOW');
         $this->_array_mail_text[58]['value'] = 'MAIL_CHOICE_INACTIVITY_LOCK_NOW';
         $this->_array_mail_text[59]['text']  = $this->_translator->getMessage('MAIL_CHOICE_INACTIVITY_DELETE_NEXT');
         $this->_array_mail_text[59]['value'] = 'MAIL_CHOICE_INACTIVITY_DELETE_NEXT';
         $this->_array_mail_text[60]['text']  = $this->_translator->getMessage('MAIL_CHOICE_INACTIVITY_DELETE_TOMORROW');
         $this->_array_mail_text[60]['value'] = 'MAIL_CHOICE_INACTIVITY_DELETE_TOMORROW';
         $this->_array_mail_text[61]['text']  = $this->_translator->getMessage('MAIL_CHOICE_INACTIVITY_DELETE_NOW');
         $this->_array_mail_text[61]['value'] = 'MAIL_CHOICE_INACTIVITY_DELETE_NOW';
      }
   }


   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $translator = $this->_environment->getTranslationObject();
      if (isset($this->_form_post['mail_text']) and $this->_form_post['mail_text'] != -1) {
         $disabled = false;
      } else {
         $disabled = true;
      }

      $this->setHeadline($this->_headline);

      $this->_form->addSelect( 'mail_text',
                               $this->_array_mail_text,
                               '',
                               $this->_translator->getMessage('CONFIGURATION_MAIL_FORM_CHOOSE_MAIL'),
                               $this->_translator->getMessage('CONFIGURATION_MAIL_FORM_CHOOSE_MAIL_DESC'),
                               '',
                               '',
                               '',
                               true,
                               $this->_translator->getMessage('COMMON_CHOOSE_BUTTON'),
                               'option','','','',true);
      $context_item = $this->_environment->getCurrentContextItem();

      if ( ( $this->_environment->inCommunityRoom()
             and $context_item->getLanguage() == 'user'
           )
           or $this->_environment->inPortal()
           or ( $this->_environment->inProjectRoom()
                and $context_item->getLanguage() == 'user'
              )
           or ( $this->_environment->inGroupRoom()
                and $context_item->getLanguage() == 'user'
              )
         ) {
         $languages = $this->_environment->getAvailableLanguageArray();
      } else {
         $languages[] = $context_item->getLanguage();
      }

      if (!empty($this->_form_post['mail_text']) and mb_strlen($this->_form_post['mail_text']) > 2) {
         switch ( $this->_form_post['mail_text'] ){
            case 'MAIL_CHOICE_HELLO':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_HELLO');
               break;
            case 'MAIL_CHOICE_CIAO':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_CIAO');
               break;
            case 'MAIL_CHOICE_USER_ACCOUNT_DELETE':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_DELETE');
               break;
            case 'MAIL_CHOICE_USER_ACCOUNT_LOCK':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_LOCK');
               break;
            case 'MAIL_CHOICE_USER_STATUS_USER':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_USER_STATUS_USER');
               break;
            case 'MAIL_CHOICE_USER_STATUS_MODERATOR':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_USER_STATUS_MODERATOR');
               break;
            case 'MAIL_CHOICE_USER_ACCOUNT_PASSWORD':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_PASSWORD');
               break;
            case 'MAIL_CHOICE_USER_ACCOUNT_MERGE':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_MERGE');
               break;
            case 'MAIL_CHOICE_MATERIAL_WORLDPUBLIC':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_MATERIAL_WORLDPUBLIC');
               break;
            case 'MAIL_CHOICE_ROOM_UNLINK':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_ROOM_UNLINK');
               break;
            case 'MAIL_CHOICE_USER_MAKE_CONTACT_PERSON':
                if ($this->_environment->inPortal()){
                   $headline = ': '.$translator->getMessage('MAIL_BODY_USER_MAKE_CONTACT_PERSON_PO');
                }elseif ($this->_environment->inCommunityRoom()){
                   $headline = ': '.$translator->getMessage('MAIL_BODY_USER_MAKE_CONTACT_PERSON_GR');
                }else {
                   $headline = ': '.$translator->getMessage('MAIL_BODY_USER_MAKE_CONTACT_PERSON_PR');
                }
               break;
            case 'MAIL_CHOICE_USER_UNMAKE_CONTACT_PERSON':
                if ($this->_environment->inPortal()){
                   $headline = ': '.$translator->getMessage('MAIL_BODY_USER_UNMAKE_CONTACT_PERSON_PO');
                }elseif ($this->_environment->inCommunityRoom()){
                   $headline = ': '.$translator->getMessage('MAIL_BODY_USER_UNMAKE_CONTACT_PERSON_GR');
                }else {
                   $headline = ': '.$translator->getMessage('MAIL_BODY_USER_UNMAKE_CONTACT_PERSON_PR');
                }
               break;
            case 'MAIL_CHOICE_PROJECT_ARCHIVE_INFO':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_PROJECT_ARCHIVE_INFO');
               break;
            case 'MAIL_CHOICE_PROJECT_ARCHIVE':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_PROJECT_ARCHIVE');
               break;
            case 'MAIL_CHOICE_PROJECT_DELETE_INFO':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_PROJECT_DELETE_INFO');
               break;
            case 'MAIL_CHOICE_PROJECT_DELETE':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_PROJECT_DELETE');
               break;
            case 'MAIL_CHOICE_INACTIVITY_LOCK_NEXT':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_INACTIVITY_LOCK_NEXT');
               break;
            case 'MAIL_CHOICE_INACTIVITY_LOCK_TOMORROW':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_INACTIVITY_LOCK_TOMORROW');
               break;
            case 'MAIL_CHOICE_INACTIVITY_LOCK_NOW':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_INACTIVITY_LOCK_NOW');
               break;
            case 'MAIL_CHOICE_INACTIVITY_DELETE_NEXT':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_INACTIVITY_DELETE_NEXT');
               break;
            case 'MAIL_CHOICE_INACTIVITY_DELETE_TOMORROW':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_INACTIVITY_DELETE_TOMORROW');
               break;
            case 'MAIL_CHOICE_INACTIVITY_DELETE_NOW':
               $headline = ': '.$translator->getMessage('MAIL_CHOICE_INACTIVITY_DELETE_NOW');
               break;
            default:
               $headline = ': '.$translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_configuration_mail_form(215) ');
               break;
         }
         $mandatory = true;
      } else {
         $headline = '';
         $mandatory = false;
      }
      foreach ($languages as $language) {
         switch ( mb_strtoupper($language, 'UTF-8') ){
            case 'DE':
               $this->_form->addHeadline('headline',
                                         $translator->getMessage('DE').$headline
                                        );
               $this->_form->addTextArea('text['.$language.']',
                                         '',
                                         $translator->getMessage('COMMON_BODY').' ('.$translator->getMessage('DE').')',
                                         '',
                                         '58',
                                         '10',
                                         '',
                                         $mandatory,
                                         $disabled,
                                         false
                                        );
               break;
            case 'EN':
               $this->_form->addHeadline('headline',
                                         $translator->getMessage('EN').$headline
                                        );
               $this->_form->addTextArea('text['.$language.']',
                                         '',
                                         $translator->getMessage('COMMON_BODY').' ('.$translator->getMessage('EN').')',
                                         '',
                                         '58',
                                         '10',
                                         '',
                                         $mandatory,$disabled,false
                                        );
               break;
            case 'RO':
               $this->_form->addHeadline('headline',
                                         $translator->getMessage('RO').$headline
                                        );
               $this->_form->addTextArea('text['.$language.']',
                                         '',
                                         $translator->getMessage('COMMON_BODY').' ('.$translator->getMessage('RO').')',
                                         '',
                                         '58',
                                         '10',
                                         '',
                                         $mandatory,
                                         $disabled,
                                         false
                                        );
               break;
            default:
               break;
         }
         $this->_form->addCheckbox('reset['.$language.']',
                                   'value',
                                   false,
                                   $translator->getMessage('MAIL_EDIT_RESET'),
                                   $translator->getMessage('COMMON_YES'),
                                   $translator->getMessage('MAIL_EDIT_RESET_TEXT'),
                                   '',
                                   $disabled
                                  );
         $this->_form->addEmptyLine();
      }

      // buttons
      $this->_form->addButtonBar('option',
                                 $translator->getMessage('PREFERENCES_SAVE_BUTTON'),
                                 '',
                                 '',
                                 '',
                                 '',
                                 '',
                                 $disabled
                                );
   }


   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
         if ( mb_strlen($this->_values['mail_text']) == 2 and $this->_values['mail_text'] != -1) {
            $this->_values['mail_text'] = -1;
         }
         foreach ($this->_values['text'] as $key => $value) {
            $this->_values['text['.$key.']'] = $value;
         }
      } else {
         $this->_values['mail_text'] = -1;
      }
   }


   /** specific check the values of the form
    * this method checks the entered values
    */
   function _checkValues () {
      // check choosen mail text
      if (mb_strlen($this->_form_post['mail_text']) == 2 and $this->_form_post['mail_text'] != -1) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_MAIL_CHOICE_ERROR');
         $this->_form->setFailure('mail_text','');
      }
      if ( mb_strlen($this->_form_post['mail_text']) == 2 and
           $this->_form_post['mail_text'] == -1 and
           isset($this->_form_post['option']) and
           isOption($this->_form_post['option'], $this->_translator->getMessage('COMMON_SAVE_BUTTON'))
         ) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_MAIL_CHOICE_ERROR');
         $this->_form->setFailure('mail_text','');
      }
   }

}
?>