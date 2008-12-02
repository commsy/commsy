<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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
class cs_profile_form extends cs_rubric_form {

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

   var $_profile_page_name = 'account';

   var $_link_item_array = array();

   var $_link_item_check_array = array();

   var $_user = NULL;

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function cs_user_form($environment) {
      $this->cs_rubric_form($environment);
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

   function setProfilePageName($name){
    $this->_profile_page_name = $name;
   }
   function getProfilePageName(){
    return $this->_profile_page_name;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {
      if ($this->getProfilePageName() == 'user'){
         // headline
         if (!empty($this->_item)) {
            $this->_headline = getMessage('USER_EDIT_FORM_TITLE');
         } elseif (!empty($this->_form_post)) {
            if (!empty($this->_form_post['iid'])) {
               $this->_headline = getMessage('USER_EDIT_FORM_TITLE');
            }
         } else {
            $this->_headline = '';
         }
         $this->setHeadline($this->_headline);
      }elseif($this->getProfilePageName() == 'room_list'){
         $room_manager = $this->_environment->getRoomManager();
         $own_room_item = $this->_environment->getCurrentUserItem()->getOwnRoom();
         $checked_item_array = $own_room_item->getCustomizedRoomIDArray();
         $room_list = $room_manager->getRelatedRoomListForUser($this->_environment->getCurrentUserItem());
         $room_item = $room_list->getFirst();
         $unchecked_link_item_array = array();
         $tmp_link_item_array = array();
         if (isset($checked_item_array[0])){
            $customized_list_exists = true;
         }else{
            $customized_list_exists = false;
         }
         while($room_item){
            if (!$room_item->isPrivateRoom()){
               $temp_array = array();
               $temp_array['text']  = $room_item->getTitle();
               $temp_array['value'] = $room_item->getItemID();
               if (in_array($room_item->getItemID(),$checked_item_array)){
                  $tmp_link_item_array[$room_item->getItemID()] = $room_item->getTitle();
                  $this->_link_item_check_array[] = $room_item->getItemID();
               }else{
                  if (!$customized_list_exists){
                     $this->_link_item_check_array[] = $room_item->getItemID();
                  }
                  $unchecked_link_item_array[] = $temp_array;
               }
            }
            $room_item = $room_list->getNext();
         }
         $count_sep = 0;
         foreach ( $checked_item_array as $value ) {
            if ( $value < 0 ) {
               $this->_link_item_check_array[] = $value;
               $tmp_link_item_array[$value] = '----------------------------';
               $count_sep++;
            }
         }
         for ( $i=$count_sep+1; $i<$count_sep+4; $i++ ) {
            $temp_array = array();
            $temp_array['text']  = '----------------------------';
            $temp_array['value'] = -$i;
            $unchecked_link_item_array[] = $temp_array;
         }
         foreach($checked_item_array as $id){
            $temp_array = array();
            $temp_array['text']  = $tmp_link_item_array[$id];
            $temp_array['value'] = $id;
            $this->_link_item_array[] = $temp_array;
         }
         $this->_link_item_array = array_merge($this->_link_item_array,$unchecked_link_item_array);
      }else{
         $this->_user = $this->_environment->getPortalUserItem();
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {
      if ($this->getProfilePageName() == 'user'){
         $this->_form->addHidden('uid','');
         $this->_form->addTextField('title','',$this->_translator->getMessage('USER_TITLE'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('title_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('birthday','',$this->_translator->getMessage('USER_BIRTHDAY'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('birthday_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('street','',$this->_translator->getMessage('USER_STREET'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('street_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('zipcode','',$this->_translator->getMessage('USER_ZIPCODE'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('zipcode_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('city','',$this->_translator->getMessage('USER_CITY'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('city_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('room','',$this->_translator->getMessage('USER_ROOM'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('room_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('telephone','',$this->_translator->getMessage('USER_TELEPHONE'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('telephone_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('cellularphone','',$this->_translator->getMessage('USER_CELLULARPHONE'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('cellularphone_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('email','',$this->_translator->getMessage('USER_EMAIL'),'','','30',true);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('email_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addTextField('homepage','',$this->_translator->getMessage('USER_HOMEPAGE'),'','','30',false);
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('homepage_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
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
         $this->_form->combine('horizontal');
         $this->_form->addCheckbox('messenger_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         ##################################################
         # messenger - END
         ##################################################
         $this->_form->addTextArea('description','',$this->_translator->getMessage('USER_DESCRIPTION'),'','40','10',false);
         $this->_form->combine();
         $this->_form->addCheckbox('description_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         $this->_form->addEmptyline();
         $this->_form->addImage('upload','',$this->_translator->getMessage('USER_PICTURE_UPLOADFILE'), $this->_translator->getMessage('USER_PICTURE_FILE_DESC'));

         //delete picture
         if ( $this->_with_picture) {
            $this->_form->combine();
            $this->_form->addCheckbox('deletePicture',$this->_translator->getMessage('USER_DEL_PIC'),false,getMessage('USER_DEL_PIC'),$this->_translator->getMessage('USER_DEL_PIC_BUTTON'),'');
         }
         $this->_form->combine();
         $this->_form->addCheckbox('picture_change_all',$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),false,$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),$this->_translator->getMessage('USER_CHANGE_IN_ALL_ROOMS'),'');
         $id = 0;
         if (isset($this->_item)) {
            $id = $this->_item->getItemID();
         } elseif (isset($this->_form_post)) {
            if (isset($this->_form_post['uid'])) {
               $id = $this->_form_post['uid'];
            }
         }
         $this->_form->addEmptyline();
         $this->_form->addButtonBar('option',$this->_translator->getMessage('COMMON_CHANGE_BUTTON'),'','','','');
      }elseif($this->getProfilePageName() == 'newsletter'){
         $radio_values = array();
         $radio_values[0]['text'] = getMessage('CONFIGURATION_NEWSLETTER_NONE');
         $radio_values[0]['value'] = '1';
         $radio_values[1]['text'] = getMessage('CONFIGURATION_NEWSLETTER_WEEKLY');
         $radio_values[1]['value'] = '2';
         $radio_values[2]['text'] = getMessage('CONFIGURATION_NEWSLETTER_DAILY');
         $radio_values[2]['value'] = '3';
         $this->_form->addRadioGroup('newsletter',getMessage('CONFIGURATION_NEWSLETTER'),'',$radio_values,'',true,false);

         $this->_form->addText('newsletter_note', getMessage('CONFIGURATION_NEWSLETTER_NOTE_LABEL'), getMessage('CONFIGURATION_NEWSLETTER_NOTE'));
         $this->_form->addEmptyline();
         $this->_form->addButtonBar('option',getMessage('PREFERENCES_SAVE_BUTTON'),'');

      }elseif($this->getProfilePageName() == 'room_list'){
#         $this->_form->addHidden('place_array',$this->_link_item_place_array);
#         $this->_form->combine('vertical');
         $this->_form->addText('activate_path','',getMessage('PROFILE_ROOMLIST_CUSTOMIZING_DESCRIPTION'));
         $this->_form->addCheckboxGroup('sorting',$this->_link_item_array,$this->_link_item_check_array,'','','','','','','','',50,true,false,true);
         $this->_form->addEmptyline();
         $this->_form->addButtonBar('option',getMessage('PREFERENCES_SAVE_BUTTON'),'');
      }else{

         // headline and hidden fields
         $this->setHeadline($this->_headline);
         $this->_form->addHidden('iid','');

         $this->_form->addHidden('uid','');
         $this->_form->addTextField('firstname','',$this->_translator->getMessage('USER_FIRSTNAME'),'','','30',true);
         $this->_form->addTextField('lastname','',$this->_translator->getMessage('USER_LASTNAME'),'','','30',true);
         $this->_form->addHidden('lastname_hidden','');
         $this->_form->addHidden('firstname_hidden','');


         $this->_form->addHidden('user_id','');
              // content form fields
         $this->_form->addTextField('user_id','',getMessage('USER_USER_ID'),'',100,'30',true);
         $this->_form->addPassword('password','',getMessage('PROFILE_USER_PASSWORD'),'','','20',false);
         $this->_form->addPassword('password2','',getMessage('PROFILE_USER_PASSWORD2'),'','','20',false);

         $i=0;
         $options = array();
         $options[$i]['value'] = 'browser';
         $options[$i]['text'] = getMessage('USER_BROWSER_LANGUAGE');
         $i++;
         $options[$i]['value'] = 'disabled';
         $options[$i]['text'] = '------------------';
         $i++;
         $languages = $this->_environment->getAvailableLanguageArray();
         foreach ($languages as $language) {
            $options[$i]['value'] = $language;
            $options[$i]['text'] = $this->_translator->getLanguageLabelOriginally($language);
            $i++;
         }
         $this->_form->addSelect('language',$options,'',getMessage('USER_LANGUAGE'),'','','',true,'','','','','','11.5');
         if ($this->_user->isModerator()){
            $this->_form->addCheckbox('email_account_want','1',false,getMessage('USER_EMAIL'),getMessage('USER_MAIL_GET_ACCOUNT'),'','','','','');
            $this->_form->addCheckbox('email_room_want','1',false,getMessage('USER_EMAIL'),getMessage('USER_MAIL_OPEN_ROOM_PO'),'','','','','');
         }

         // buttons
         $this->_form->addEmptyline();
         $this->_form->addButtonBar('option',getMessage('PREFERENCES_SAVE_BUTTON'));
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      if ($this->getProfilePageName() == 'user'){
         $this->_values = array();
         if (isset($this->_item)) {
            $this->_values['uid'] = $this->_item->getItemID();
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
            $this->_values['firstname_hidden'] = $this->_item->getFirstname();
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

         } elseif (isset($this->_form_post)) {
            $this->_values = $this->_form_post;
            if ( !$this->_environment->inPrivateRoom()
                 and isset($this->_values['fullname_hidden'])
               ) {
               $this->_values['fullname'] = $this->_values['fullname_hidden'];
            }
        }
      }elseif($this->getProfilePageName() == 'newsletter'){
           if (isset($this->_form_post)) {
            $this->_values = $this->_form_post;
         }else{
            $room = $this->_environment->getCurrentUserItem()->getOwnRoom();
            $newlsetter = $room->getPrivateRoomNewsletterActivity();
            if ($newlsetter == 'weekly'){
               $this->_values['newsletter'] ='2';
            }elseif ($newlsetter == 'daily'){
               $this->_values['newsletter'] ='3';
            }else{
               $this->_values['newsletter'] ='1';

            }
         }

      }elseif($this->getProfilePageName() == 'room_list'){
      }else{
         if (!empty($this->_form_post)) {
            $this->_values = $this->_form_post;
            $this->_values['user_id_text'] = $this->_values['user_id'];
         } elseif (!empty($this->_item)) {
            if ($this->_user->getAccountWantMail() == 'yes') {
               $this->_values['email_account_want'] = 1;
            }
            if ($this->_user->getOpenRoomWantMail() == 'yes') {
               $this->_values['email_room_want'] = 1;
            }
            $this->_values['auth_source'] = $this->_user->getAuthSource();
            $this->_values['user_id'] = $this->_user->getUserID();
            if ( $this->_user->isRoot() ) {
               $this->_values['user_id_text'] = $this->_user->getUserID();
            }
            $this->_values['iid'] = $this->_item->getItemID();
            $this->_values['firstname'] = $this->_item->getFirstname();
            $this->_values['firstname_hidden'] = $this->_item->getFirstname();
            $this->_values['lastname'] = $this->_item->getLastname();
            $this->_values['lastname_hidden'] = $this->_item->getLastname();
            $this->_values['user_id'] = $this->_item->getUserID();
            $this->_values['user_id_text'] = $this->_item->getUserID();
         } else {
            include_once('functions/error_functions.php');trigger_error('lost values',E_USER_WARNING);
         }
      }
   }

   function _checkValues () {
      if ($this->getProfilePageName() == 'account'){
         if (isset($this->_form_post['password']) and $this->_form_post['password'] != $this->_form_post['password2']) {
            $this->_error_array[] = getMessage('USER_PASSWORD_ERROR');
            $this->_form->setFailure('password');
            $this->_form->setFailure('password2');
         }
      }
      if ( !empty($this->_form_post['user_id']) ) {
         $current_user = $this->_environment->getCurrentUserItem();
         $auth_source = $current_user->getAuthSource();
         if ( !empty($auth_source) ) {
            $authentication = $this->_environment->getAuthenticationObject();
            $this->_user = $this->_environment->getPortalUserItem();
            if ($this->_user->getUserID() != $this->_form_post['user_id'] and !$authentication->is_free($this->_form_post['user_id'],$auth_source)) {
               $this->_error_array[] = getMessage('USER_USER_ID_ERROR',$this->_form_post['user_id']);
               $this->_form->setFailure('user_id','');
            } elseif ( withUmlaut($this->_form_post['user_id']) ) {
               $this->_error_array[] = getMessage('USER_USER_ID_ERROR_UMLAUT',$this->_form_post['user_id']);
               $this->_form->setFailure('user_id','');
            }
         } else {
            $this->_error_array[] = getMessage('USER_AUTH_SOURCE_ERROR');
         }
      }
   }


}
?>