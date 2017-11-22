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

$this->includeClass(RUBRIC_FORM);
include_once('functions/text_functions.php');

/** class for commsy form: user
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_user_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing an array of groups in the context
   */
   var $_group_array = array();

  /**
   * array - containing an array of topics in the context
   */
   var $_topic_array = array();

  /**
   * boolean - gibt es ein Bild zu dieser Person?
   */
   var $_with_picture = false;

  /**
   * boolean - ist die Person ein Moderator?
   */
   var $_is_moderator = false;

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

  /**
    * @param boolean - gibt es ein Bild zum Benutzer?
    */
   function setWithPicture ($value) {
      $this->_with_picture = (boolean)$value;
   }

  /**
    * @param boolean - ist der Benutzer ein Moderator?
    */
   function setIsModerator ($value) {
      $this->_is_moderator = (boolean)$value;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {

      // headline
      if (!empty($this->_item)) {
         $this->_headline = $this->_translator->getMessage('USER_EDIT_FORM_TITLE');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = $this->_translator->getMessage('USER_EDIT_FORM_TITLE');
         }
      } else {
         $this->_headline = '';
      }
      $this->setHeadline($this->_headline);
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      if ( $this->_environment->inPrivateRoom()){
         $this->_form->addHidden('iid','');
         $this->_form->addTitleField('firstname','',$this->_translator->getMessage('USER_FULLNAME'),'','','18',true);
         $this->_form->combine('vertical');
         $this->_form->addTextField('lastname','',$this->_translator->getMessage('USER_LASTNAME'),'','','18',true);
         $this->_form->addHidden('lastname_hidden','');
         $this->_form->addHidden('firstname_hidden','');
         $this->_form->addTextField('title','',$this->_translator->getMessage('USER_TITLE'),'','','10',false);
         $this->_form->combine();
         $this->_form->addCheckbox('title_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');

         $this->_form->addTextField('birthday','',$this->_translator->getMessage('USER_BIRTHDAY'),'','','10',false);
         $this->_form->combine();
         $this->_form->addCheckbox('birthday_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');

         $this->_form->addTextField('street','',$this->_translator->getMessage('USER_STREET'),'','','30',false);
         $this->_form->combine();
         $this->_form->addCheckbox('street_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');

         $this->_form->addTextField('zipcode','',$this->_translator->getMessage('USER_ZIPCODE'),'','','10',false);
         $this->_form->combine();
         $this->_form->addCheckbox('zipcode_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');

         $this->_form->addTextField('city','',$this->_translator->getMessage('USER_CITY'),'','','30',false);
         $this->_form->combine();
         $this->_form->addCheckbox('city_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');

         $this->_form->addTextField('room','',$this->_translator->getMessage('USER_ROOM'),'','','30',false);
         $this->_form->combine();
         $this->_form->addCheckbox('room_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');

         $this->_form->addTextField('telephone','',$this->_translator->getMessage('USER_TELEPHONE'),'','','30',false);
         $this->_form->combine();
         $this->_form->addCheckbox('telephone_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');

         $this->_form->addTextField('cellularphone','',$this->_translator->getMessage('USER_CELLULARPHONE'),'','','30',false);
         $this->_form->combine();
         $this->_form->addCheckbox('cellularphone_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');

         $this->_form->addTextField('email','',$this->_translator->getMessage('USER_EMAIL'),'','','30',true);
         if ( !$this->_environment->inPrivateRoom() ) {
            $this->_form->combine();
            $this->_form->addCheckbox('email_visibility','check',false,'',$this->_translator->getMessage('USER_EMAIL_VISIBILITY_VALUE'),'');
         }
         $this->_form->combine();
         $this->_form->addCheckbox('email_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');

         $this->_form->addTextField('homepage','',$this->_translator->getMessage('USER_HOMEPAGE'),'','','30',false);
         $this->_form->combine();
         $this->_form->addCheckbox('homepage_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');

         ##################################################
         # messenger - BEGIN
         ##################################################

         $this->_form->addTextField('icq','',$this->_translator->getMessage('USER_MESSENGER_NUMBERS'),'','','18',false,'','','','left',$this->_translator->getMessage('USER_ICQ').':&nbsp;&nbsp;&nbsp;<span style="font-size: 13px;">&nbsp;</span>','',false,'');
         $this->_form->combine('horizontal');
         /*
         $this->_form->addTextField('jabber','',$this->_translator->getMessage('USER_JABBER'),'','','19',false,'','','','left','&nbsp;&nbsp;&nbsp;&nbsp;'.$this->_translator->getMessage('USER_JABBER').':','',false,'');
         $this->_form->combine();
         */
         $this->_form->addTextField('msn','',$this->_translator->getMessage('USER_MSN'),'','','18',false,'','','','left','&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$this->_translator->getMessage('USER_MSN').':','',false,'');
         $this->_form->combine();
         $this->_form->addTextField('skype','',$this->_translator->getMessage('USER_SKYPE'),'','','18',false,'','','','left',$this->_translator->getMessage('USER_SKYPE').':<span style="font-size: 1px;">&nbsp;</span>','',false,'');
         $this->_form->combine('horizontal');
         $this->_form->addTextField('yahoo','',$this->_translator->getMessage('USER_YAHOO'),'','','18',false,'','','','left','&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$this->_translator->getMessage('USER_YAHOO').':','',false,'');
         $this->_form->combine();
         $this->_form->addText('messenger_text',$this->_translator->getMessage('USER_MESSENGER_NUMBERS'),$this->_translator->getMessage('USER_MESSENGER_NUMBERS_TEXT'));
         $this->_form->combine();
         $this->_form->addCheckbox('messenger_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');

         ##################################################
         # messenger - END
         ##################################################

         $this->_form->addTextArea('description','',$this->_translator->getMessage('USER_DESCRIPTION'),'','58','10',false);
         $this->_form->combine();
         $this->_form->addCheckbox('description_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addEmptyline();

         $this->_form->addImage('upload','',$this->_translator->getMessage('USER_PICTURE_UPLOADFILE'), $this->_translator->getMessage('USER_PICTURE_FILE_DESC'));

         //delete picture
         if ( $this->_with_picture) {
            $this->_form->combine();
            $this->_form->addCheckbox('deletePicture',$this->_translator->getMessage('USER_DEL_PIC'),false,$this->_translator->getMessage('USER_DEL_PIC'),$this->_translator->getMessage('USER_DEL_PIC_BUTTON'),'');
         }

         $this->_form->combine();
         $this->_form->addCheckbox('picture_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');

      } else {

         // user
         $this->_form->addHidden('iid','');
         if ( $this->_environment->inPortal() ) {
            $this->_form->addTitleField('firstname','',$this->_translator->getMessage('USER_FULLNAME'),'','','18',true);
            $this->_form->combine('vertical');
            $this->_form->addTextField('lastname','',$this->_translator->getMessage('USER_LASTNAME'),'','','18',true);
            $this->_form->addHidden('lastname_hidden','');
            $this->_form->addHidden('firstname_hidden','');
         } else {
            $this->_form->addTitleText('fullname','');
            $this->_form->addHidden('fullname_hidden','');
         }
         $this->_form->addTextField('title','',$this->_translator->getMessage('USER_TITLE'),'','','10',false);
         $this->_form->addTextField('birthday','',$this->_translator->getMessage('USER_BIRTHDAY'),'','','10',false);
         $this->_form->addTextField('street','',$this->_translator->getMessage('USER_STREET'),'','','30',false);
         $this->_form->addTextField('zipcode','',$this->_translator->getMessage('USER_ZIPCODE'),'','','10',false);
         $this->_form->addTextField('city','',$this->_translator->getMessage('USER_CITY'),'',100,'30',false);
         $this->_form->addTextField('room','',$this->_translator->getMessage('USER_ROOM'),'','','30',false);
         $this->_form->addTextField('telephone','',$this->_translator->getMessage('USER_TELEPHONE'),'','','30',false);
         $this->_form->addTextField('cellularphone','',$this->_translator->getMessage('USER_CELLULARPHONE'),'','','30',false);
         $this->_form->addTextField('email','',$this->_translator->getMessage('USER_EMAIL'),'','','30',true,100);
         if ( !$this->_environment->inPortal() ) {
            $this->_form->combine();
            $this->_form->addCheckbox('email_visibility','check',false,'',$this->_translator->getMessage('USER_EMAIL_VISIBILITY_VALUE'),'');
         } else {
            $this->_form->combine();
            $this->_form->addCheckbox('email_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         }

         ##################################################
         # messenger - BEGIN
         ##################################################

         $this->_form->addTextField('icq','',$this->_translator->getMessage('USER_MESSENGER_NUMBERS'),'','','18',false,'','','','left',$this->_translator->getMessage('USER_ICQ').':&nbsp;&nbsp;&nbsp;<span style="font-size: 13px;">&nbsp;</span>','',false,'');
         $this->_form->combine('horizontal');
         /*
         $this->_form->addTextField('jabber','',$this->_translator->getMessage('USER_JABBER'),'','','19',false,'','','','left','&nbsp;&nbsp;&nbsp;&nbsp;'.$this->_translator->getMessage('USER_JABBER').':','',false,'');
         $this->_form->combine();
         */
         $this->_form->addTextField('msn','',$this->_translator->getMessage('USER_MSN'),'','','18',false,'','','','left','&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$this->_translator->getMessage('USER_MSN').':','',false,'');
         $this->_form->combine();
         $this->_form->addTextField('skype','',$this->_translator->getMessage('USER_SKYPE'),'','','18',false,'','','','left',$this->_translator->getMessage('USER_SKYPE').':<span style="font-size: 1px;">&nbsp;</span>','',false,'');
         $this->_form->combine('horizontal');
         $this->_form->addTextField('yahoo','',$this->_translator->getMessage('USER_YAHOO'),'','','18',false,'','','','left','&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$this->_translator->getMessage('USER_YAHOO').':','',false,'');
         $this->_form->combine();
         $this->_form->addText('messenger_text',$this->_translator->getMessage('USER_MESSENGER_NUMBERS'),$this->_translator->getMessage('USER_MESSENGER_NUMBERS_TEXT'));

         ##################################################
         # messenger - END
         ##################################################

         $this->_form->addTextField('homepage','',$this->_translator->getMessage('USER_HOMEPAGE'),'','','30',false);
         $this->_form->addTextArea('description','',$this->_translator->getMessage('USER_DESCRIPTION'),'','58','10',false);
         $this->_form->addEmptyline();
         $this->_form->addHidden('with_picture','');
         $this->_form->addImage('upload','',$this->_translator->getMessage('USER_PICTURE_UPLOADFILE'),$this->_translator->getMessage('USER_PICTURE_FILE_DESC'));
         if ( $this->_with_picture ) {
            $this->_form->combine();
            $this->_form->addCheckbox('deletePicture',$this->_translator->getMessage('USER_DEL_PIC'),false,$this->_translator->getMessage('USER_DEL_PIC'),$this->_translator->getMessage('USER_DEL_PIC_BUTTON'),'');
         }

          if ($this->_environment->inPortal()) {
              $this->_form->addEmptyline();

              if (isset($this->_item) && !$this->_item->isModerator()) {
                  $auth_source_standard_setting = '';
                  if (isset($this->_item)) {
                      $auth_source_manager = $this->_environment->getAuthSourceManager();
                      $auth_source_item = $auth_source_manager->getItem($this->_item->getAuthSource());
                      if ($auth_source_item->isUserAllowedToCreateContext()) {
                          $auth_source_standard_setting .= $this->_translator->getMessage('COMMON_YES');
                      } else {
                          $auth_source_standard_setting .= $this->_translator->getMessage('COMMON_NO');
                      }
                  }

                  $radio_values = array();
                  $radio_values[0]['text'] = $this->_translator->getMessage('USER_IS_ALLOWED_TO_CREATE_CONTEXT_AUTH_SOURCE_SETTING') . ' (' . $auth_source_standard_setting . ')';
                  $radio_values[0]['value'] = 'standard';
                  $radio_values[1]['text'] = $this->_translator->getMessage('COMMON_YES');
                  $radio_values[1]['value'] = 1;
                  $radio_values[2]['text'] = $this->_translator->getMessage('COMMON_NO');
                  $radio_values[2]['value'] = -1;

                  $this->_form->addRadioGroup('user_is_allowed_to_create_context',
                      $this->_translator->getMessage('USER_IS_ALLOWED_TO_CREATE_CONTEXT'),
                      $this->_translator->getMessage('USER_IS_ALLOWED_TO_CREATE_CONTEXT'),
                      $radio_values,
                      '',
                      true,
                      false
                  );
              } else {
                  $this->_form->addText('allowed_to_create_context_text',$this->_translator->getMessage('USER_IS_ALLOWED_TO_CREATE_CONTEXT'), $this->_translator->getMessage('COMMON_YES').' ('.$this->_translator->getMessage('USER_IS_ALLOWED_TO_CREATE_CONTEXT_IS_PORTAL_MODERATOR').')');
              }
          }

          if ($this->_environment->inPortal()) {
              $this->_form->addEmptyline();

              $portal_standard_setting = $this->_translator->getMessage($this->_environment->getCurrentPortalItem()->getConfigurationCalDAV());

              $radio_values = array();
              $radio_values[0]['text'] = $this->_translator->getMessage('USER_IS_ALLOWED_TO_USE_CALDAV_PORTAL_SETTING') . ' (' . $portal_standard_setting . ')';
              $radio_values[0]['value'] = 'standard';
              $radio_values[1]['text'] = $this->_translator->getMessage('COMMON_YES');
              $radio_values[1]['value'] = 1;
              $radio_values[2]['text'] = $this->_translator->getMessage('COMMON_NO');
              $radio_values[2]['value'] = -1;

              $this->_form->addRadioGroup('user_is_allowed_to_use_caldav',
                  $this->_translator->getMessage('USER_IS_ALLOWED_TO_USE_CALDAV'),
                  $this->_translator->getMessage('USER_IS_ALLOWED_TO_USE_CALDAV'),
                  $radio_values,
                  '',
                  true,
                  false
              );
          }
      }
      $context_item = $this->_environment->getCurrentContextItem();

      // rubric connections
//      $this->_setFormElementsForConnectedRubrics();

      // buttons
      $id = 0;
      if (isset($this->_item)) {
         $id = $this->_item->getItemID();
      } elseif (isset($this->_form_post)) {
         if (isset($this->_form_post['iid'])) {
            $id = $this->_form_post['iid'];
         }
      }
      $this->_form->addButtonBar('option',$this->_translator->getMessage('USER_CHANGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','');
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();
      if (isset($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['title'] = $this->_item->getTitle();          // no encode here
         $this->_values['telephone'] = $this->_item->getTelephone();  // encode in form_views
         $this->_values['birthday'] = $this->_item->getBirthday();
         $this->_values['cellularphone'] = $this->_item->getCellularphone();
         $this->_values['homepage'] = $this->_item->getHomepage();
         $this->_values['email'] = $this->_item->getEmail();
         $this->_values['street'] = $this->_item->getStreet();
         $this->_values['zipcode'] = $this->_item->getZipcode();
         $this->_values['city'] = $this->_item->getCity();
         $this->_values['room'] = $this->_item->getRoom();
         $this->_values['description'] = $this->_item->getDescription();
         $this->_values['firstname'] = $this->_item->getFirstname();
         $this->_values['firstname_hidden'] = $this->_item->getFirstname();
         $this->_values['fullname_hidden'] = $this->_item->getFullname();
         $this->_values['fullname'] = $this->_item->getFullname();
         $this->_values['lastname'] = $this->_item->getLastname();
         $this->_values['lastname_hidden'] = $this->_item->getLastname();
         $this->_values['icq'] = $this->_item->getICQ();
         $this->_values['msn'] = $this->_item->getMSN();
         $this->_values['skype'] = $this->_item->getSkype();
         $this->_values['jabber'] = $this->_item->getJabber();
         $this->_values['yahoo'] = $this->_item->getYahoo();
         $this->_setValuesForRubricConnections();

         if ($this->_item->isModerator()) {
            $this->_values['want_mail_get_account'] = $this->_item->getAccountWantMail();
            $this->_values['is_moderator'] = true;
         } else {
            $this->_values['is_moderator'] = false;
         }

         $picture = $this->_item->getPicture();
       $this->_values['upload'] = $picture;
         if (!empty($picture)) {
            $this->_values['with_picture'] = true;
         } else {
            $this->_values['with_picture'] = false;
         }

       if (!$this->_item->isEmailVisible()) {
          $this->_values['email_visibility'] = 'checked';
       }

       if ($this->_environment->inPortal()) {
           $this->_values['user_is_allowed_to_create_context'] = $this->_item->getIsAllowedToCreateContext();
       }

       if ($this->_environment->inPortal()) {
           $this->_values['user_is_allowed_to_use_caldav'] = $this->_item->getIsAllowedToUseCalDAV();
       }

      } elseif (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
         if ( !$this->_environment->inPrivateRoom()
              and isset($this->_values['fullname_hidden'])
            ) {
            $this->_values['fullname'] = $this->_values['fullname_hidden'];
         }
      }

   }
}
?>