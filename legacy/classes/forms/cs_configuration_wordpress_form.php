<?PHP
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Manuel Gonzalez Vazquez, Johannes Schultze
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

$_skin_array = array();

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_wordpress_form extends cs_rubric_form {

  var $_set_deletion_values = false;
  private $_saving_allowed = true;

  /** constructor
   * the only available constructor
   *
   * @param array params array of parameter
   */
  function __construct ($params) {
    cs_rubric_form::__construct($params);
    $this->_translator = $this->_environment->getTranslationObject();
  }

  function setDeletionValues() {
    $this->_set_deletion_values = true;
  }

  function setSkinArray($array) {
    $this->_skin_array = $array;
  }

  /** init data for form, INTERNAL
   * this methods init the data for the form, for example groups
   */
  function _initForm () {
    $this->_item = $this->_environment->getCurrentContextItem();
    $this->_array_info_text = array();
    if(!empty($this->_skin_array)) {
      foreach($this->_skin_array as $name => $skin) {
        $temp_array = array();
        $temp_array['text']  = $name;
        $temp_array['value'] = $skin;
        $this->_array_info_text[$skin] = $temp_array;
      }
    }
    asort($this->_array_info_text);

    $wordpress_manager = $this->_environment->getWordPressManager();
    $current_user = $this->_environment->getCurrentUserItem();
    if ( isset($this->_item)
         and !$wordpress_manager->isUserAllowedToConfig($this->_item->getWordpressId(),$current_user->getUserID())
       ) {
       $this->_saving_allowed = false;
       $this->_error_array[] = $this->_translator->getMessage('WORDPRESS_CONFIG_ACCESS_NOT_GRANTED');
       $this->_form->setFailure('option','');
    }
    unset($wordpress_manager);
    unset($current_user);
  }

  /** create the form, INTERNAL
   * this methods creates the form with the form definitions
   */
  function _createForm () {
    $wordpress_path_url = $current_portal_item->getWordpressUrl();
    // form fields
    $this->_form->addHidden('iid','');
    $this->_form->addTextfield('wordpresstitle','',$this->_translator->getMessage('COMMON_TITLE'),$this->_translator->getMessage('DATES_TITLE_DESC'),200,28,true);
    $this->_form->addTextfield('wordpressdescription','',$this->_translator->getMessage('COMMON_DESCRIPTION'),$this->_translator->getMessage('DATES_TITLE_DESC'),200,28,true);



    $this->_form->addSelect( 'skin_choice',
            $this->_array_info_text,
            '',
            $this->_translator->getMessage('CONFIGURATION_SKIN_FORM_CHOOSE_TEXT'),
            '',
            '',
            '',
            '',
            true,
            $this->_translator->getMessage('COMMON_CHOOSE_BUTTON'),
            'option',
            '',
            '',
            '15',
            true);
    $this->_form->combine();

    // screenshot filename
    $wp_manager = $this->_environment->getWordpressManager();
    if ( !empty($this->_form_post['skin_choice']) ) {
       $screenshot_file_name = $wp_manager->getScreenshotFilenameForTheme($this->_form_post['skin_choice']);
    } elseif ( isset($this->_item) ) {
       $screenshot_file_name = $wp_manager->getScreenshotFilenameForTheme($this->_item->getWordpressSkin());
    } else {
       $screenshot_file_name = 'screenshot.png';
    }
    unset($wp_manager);

    if ( !empty($this->_form_post['skin_choice']) ) {
      $desc = '<img src="'.$wordpress_path_url.'wp-content/themes/'.$this->_form_post['skin_choice'].'/'.$screenshot_file_name.'" alt="'.$this->_translator->getMessage('COMMON_SKIN').'" style=" border:1px solid black; vertical-align: middle;"/>';
      $this->_form->addText('example','',$desc);
    }elseif( isset($this->_item) and !$this->_set_deletion_values) {
      $skin = $this->_item->getWordpressSkin();
      if (!empty ($skin) ) {
        $desc = '<img src="'.$wordpress_path_url.'wp-content/themes/'.$this->_item->getWordpressSkin().'/'.$screenshot_file_name.'" alt="'.$this->_translator->getMessage('COMMON_SKIN').'" style=" border:1px solid black; vertical-align: middle;"/>';
        $this->_form->addText('example','',$desc);
      }else {
        $desc = '<img src="'.$wordpress_path_url.'wp-content/themes/'.$this->_translator->getMessage('COMMON_SKIN').'" style=" border:1px solid black; vertical-align: middle;"/>';
        $this->_form->addText('example','',$desc);
      }
    }else {
      $desc = '<img src="'.$wordpress_path_url.'/wp-content/themes/'.$this->_item->getWordpressSkin().'/'.$screenshot_file_name.'" style=" border:1px solid black; vertical-align: middle;"/>';
      $this->_form->addText('example','',$desc);
    }

    // description for themes
    $this->_form->combine();
    if ( isset($this->_item) and $this->_item->existWordpress() ) {
       $title = $this->_translator->getMessage('COMMON_WORDPRESS_LINK').': '.$this->_item->getWordpressTitle();
       $session_item = $this->_environment->getSessionItem();
       $url_session_id = '?commsy_session_id='.$session_item->getSessionID();
       unset($session_item);
       # direkte Verlinkung geht nicht, da die Authentifizierung über die Session zu spät erfolgt
       $url = '<a title="'.$title.'" href="'.$wordpress_path_url.'/'.$this->_item->getContextID().'_'.$this->_item->getItemID().'/'.$url_session_id.'" target="_blank">'.$this->_translator->getMessage('COMMON_WORDPRESS_LINK').'</a>';
    } else {
       $url = $this->_translator->getMessage('COMMON_WORDPRESS_LINK');
    }
    $text = $this->_translator->getMessage('WORDPRESS_SKIN_DESCRIPTION',$url);
    $this->_form->addText('skin_desc','','<span class="disabled">'.$text.'</span>');


    $this->_form->addEmptyline();

    // member role in wordpress
    $this->_form->addSelect( 'member_role',
            array(
            array('text' => $this->_translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE_SUBSCRIBER'), 'value' => 'subscriber'),
            array('text' => $this->_translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE_AUTHOR'), 'value' => 'author'),
            array('text' => $this->_translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE_EDITOR'), 'value' => 'editor'),
            array('text' => $this->_translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE_ADMINISTRATOR'), 'value' => 'administrator'),
            ),
            '',
            $this->_translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE'),
            '',
            '',
            '',
            '',
            false,
            '',
            '',
            '',
            '',
            '15',
            false);
    $wordpress_manager = $this->_environment->getWordpressManager();
    $this->_form->combine();
    $this->_form->addText('member_role_desc','','<span class="disabled">'.$this->_translator->getMessage('WORDPRESS_SELECT_MEMBER_ROLE_DESCRIPTION').'</span>');

    // comments
    $this->_form->addEmptyline();
    if (!$this->_item->isPortal()) {
      $this->_form->addCheckbox('use_comments',1,'',$this->_translator->getMessage('WORDPRESS_CONFIGURATION_COMMENTS'),$this->_translator->getMessage('WORDPRESS_CONFIGURATION_USE_COMMENTS'),$this->_translator->getMessage('WORDPRESS_CONFIGURATION_COMMENTS_DESC'),false,false,'','',true,false);
      $this->_form->combine();
      $this->_form->addCheckbox('use_comments_moderation',1,'',$this->_translator->getMessage('WORDPRESS_CONFIGURATION_COMMENTS'),$this->_translator->getMessage('WORDPRESS_CONFIGURATION_USE_COMMENTS_MODERATION'),$this->_translator->getMessage('WORDPRESS_CONFIGURATION_WORDPRESS_COMMENTS_DESC'),false,false,'','',true,false);

    }

    $this->_form->addEmptyline();
    if (!$this->_item->isPortal()) {
      $this->_form->addCheckbox('wordpresslink',1,'',$this->_translator->getMessage('WORDPRESS_CONFIGURATION_COMMON'),$this->_translator->getMessage('WORDPRESS_CONFIGURATION_SHOW_HOMELINK'),$this->_translator->getMessage('WORDPRESS_CONFIGURATION_COMMON_DESC'),false,false,'','',true,false);
    }

    // buttons
    if ( isset($this->_item) and $this->_item->existWordpress() ) {
      $this->_form->addButtonBar('option',$this->_translator->getMessage('COMMON_CHANGE_BUTTON'),'',$this->_translator->getMessage('WORDPRESS_DELETE_BUTTON'),'','','',!$this->_saving_allowed);
    } else {
      $this->_form->addButtonBar('option',$this->_translator->getMessage('WORDPRESS_SAVE_BUTTON'));
    }
  }



  /** loads the selected and given values to the form
   * this methods loads the selected and given values to the form from the context item or the form_post data
   */
  function _prepareValues () {
    $this->_values = array();
    if (isset($this->_form_post) and !$this->_set_deletion_values) {
      $this->_values = $this->_form_post;
      $this->_values['new_discussion'] = '';
    } elseif (isset($this->_item) and !$this->_set_deletion_values) {
      $this->_values['iid'] = $this->_item->getItemID();
      $this->_values['wordpresstitle'] = $this->_item->getWordpressTitle();
      $this->_values['wordpressdescription'] = $this->_item->getWordpressDescription();

      $use_comments = $this->_item->getWordpressUseComments();
      if ($use_comments=='1') {
        $this->_values['use_comments'] = 1;
      }
      $use_comments_moderation = $this->_item->getWordpressUseCommentsModeration();
      if ($use_comments_moderation=='1') {
        $this->_values['use_comments_moderation'] = 1;
      }

      $use_calendar = $this->_item->getWordpressUseCalendar();
      if ($use_calendar=='1') {
        $this->_values['use_calendar'] = 1;
      }
      $use_tagcloud = $this->_item->getWordpressUseTagCloud();
      if ($use_tagcloud=='1') {
        $this->_values['use_tagcloud'] = 1;
      }

      $wordpresslink = $this->_item->getWordpressHomeLink();
      if ($wordpresslink=='1') {
        $this->_values['wordpresslink'] = 1;
      }
      $this->_values['skin_choice'] = $this->_item->getWordpressSkin();
      $this->_values['member_role'] = $this->_item->getWordpressMemberRole();
    } else {
      $this->_values['wordpresstitle'] = $this->_item->getWordpressTitle();
      $this->_values['skin_choice'] = 'twentyten';
      $this->_values['admin'] = 'admin';
      $this->_values['edit'] = 'edit';
      $this->_values['read'] = 'read';
      $this->_values['show_login_box'] = '1';
      $this->_values['wordpresslink'] = '1';
      $this->_values['use_comments'] = '1';
      $this->_values['use_comments_moderation'] = '1';
      $this->_values['use_calendar'] = '1';
      $this->_values['use_tagcloud'] = '1';
      $this->_values['use_commsy_login'] = '1';
      $this->_values['member_role'] = 'subscriber';
    }
  }

  function _checkValues () {
    $context_item = $this->_environment->getCurrentContextItem();
//    $discussion_array = $context_item->getWordpressDiscussionArray();
//
//    if ( !empty($this->_form_post['enable_discussion'])
//            and !empty($this->_form_post['new_discussion'])
//            and isset($discussion_array[0])
//    ) {
//      $wordpress_manager = $this->_environment->getWordpressManager();
//      $tempDiscussion = $wordpress_manager->getDiscussionWordpressName($this->_form_post['new_discussion']);
//
//      $exists = false;
//
//      foreach($discussion_array as $discussion) {
//        $discussion = $wordpress_manager->getDiscussionWordpressName($discussion);
//        if ($discussion == $tempDiscussion) {
//          $exists = true;
//        }
//      }
//
//      if($exists) {
//        $this->_error_array[] = $this->_translator->getMessage('WORDPRESS_DISCUSSION_EXISTS_ERROR');
//        $this->_form->setFailure('new_discussion','');
//      }
//
//    }
//    if ( empty($this->_form_post['enable_discussion'])
//            and (!empty($this->_form_post['enable_discussion_notification']) or !empty($this->_form_post['enable_discussion_notification_groups']))
//    ) {
//      $this->_error_array[] = $this->_translator->getMessage('WORDPRESS_DISCUSSION_NOT_SELECTED_ERROR');
//      $this->_form->setFailure('enable_discussion','');
//    }
//
//    if ( !empty($this->_form_post['enable_discussion'])
//            and empty($this->_form_post['enable_discussion_notification'])
//            and !empty($this->_form_post['enable_discussion_notification_groups'])
//    ) {
//      $this->_error_array[] = $this->_translator->getMessage('WORDPRESS_DISCUSSION_NOTIFICATION_NOT_SELECTED_ERROR');
//      $this->_form->setFailure('enable_discussion_notification','');
//    }
//
//
//    if ( empty($this->_form_post['community_read_access'])
//            and (!empty($this->_form_post['community_write_access']))
//    ) {
//      $this->_error_array[] = $this->_translator->getMessage('WORDPRESS_COOMUNITY_NO_READ_ACCESS_ERROR');
//      $this->_form->setFailure('community_read_access','');
//    }
  }
}
?>