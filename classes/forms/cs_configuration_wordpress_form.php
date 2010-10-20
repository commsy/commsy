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

  /** constructor
   * the only available constructor
   *
   * @param array params array of parameter
   */
  function __construct ($params) {
    $this->cs_rubric_form($params);
    $this->_translator = $this->_environment->getTranslationObject();
  }

  function setDeletionValues(){
    $this->_set_deletion_values = true;
  }

  function setSkinArray($array){
    $this->_skin_array = $array;
  }

  /** init data for form, INTERNAL
   * this methods init the data for the form, for example groups
   */
  function _initForm () {
    $this->_item = $this->_environment->getCurrentContextItem();
    $this->_array_info_text = array();
    if(!empty($this->_skin_array)){
	    foreach($this->_skin_array as $skin){
	      $temp_array = array();
	      $temp_array['text']  = $skin;
	      $temp_array['value'] = $skin; //hvv
	      $this->_array_info_text[$skin] = $temp_array;
	    }
    }
    ksort($this->_array_info_text);
  }

  /** create the form, INTERNAL
   * this methods creates the form with the form definitions
   */
  function _createForm () {
    global $c_wordpress_path_url;
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
    if ( !empty($this->_form_post['skin_choice']) ) {
      $desc = '<img src="'.$c_wordpress_path_url.'/default/wp-content/themes/'.$this->_form_post['skin_choice'].'/screenshot.png" alt="'.$this->_translator->getMessage('COMMON_SKIN').'" style=" border:1px solid black; vertical-align: middle;"/>';
      $this->_form->addText('example','',$desc);
    }elseif( isset($this->_item) and !$this->_set_deletion_values) {
      $skin = $this->_item->getWordpressSkin();
      if (!empty ($skin) ){
        $desc = '<img src="'.$c_wordpress_path_url.'/default/wp-content/themes/'.$this->_item->getWordpressSkin().'/screenshot.png" alt="'.$this->_translator->getMessage('COMMON_SKIN').'" style=" border:1px solid black; vertical-align: middle;"/>';
        $this->_form->addText('example','',$desc);
      }else{
        $desc = '<img src="'.$c_wordpress_path_url.'/default/wp-content/themes/'.$this->_translator->getMessage('COMMON_SKIN').'" style=" border:1px solid black; vertical-align: middle;"/>';
        $this->_form->addText('example','',$desc);
      }
    }else{
      $desc = '<img src="'.$c_wordpress_path_url.'/default/wp-content/themes/'.$this->_item->getWordpressSkin().'/screenshot.png" style=" border:1px solid black; vertical-align: middle;"/>';
      $this->_form->addText('example','',$desc);
    }

    $wordpress_manager = $this->_environment->getWordpressManager();


    // comments
    $this->_form->addEmptyline();
    if (!$this->_item->isPortal()){
      $this->_form->addCheckbox('use_comments',1,'',$this->_translator->getMessage('COMMON_CONFIGURATION_COMMENTS'),$this->_translator->getMessage('COMMON_CONFIGURATION_WORDPRESS_USE_COMMENTS'),$this->_translator->getMessage('COMMON_CONFIGURATION_WORDPRESS_COMMENTS_DESC'),false,false,'','',true,false);
      $this->_form->combine();
      $this->_form->addCheckbox('use_comments_moderation',1,'',$this->_translator->getMessage('COMMON_CONFIGURATION_COMMENTS'),$this->_translator->getMessage('COMMON_CONFIGURATION_WORDPRESS_USE_COMMENTS_MODERATION'),$this->_translator->getMessage('COMMON_CONFIGURATION_WORDPRESS_COMMENTS_DESC'),false,false,'','',true,false);
       
    }

    // plugins
    $this->_form->addEmptyline();
    if (!$this->_item->isPortal()){
      $this->_form->addCheckbox('use_calendar',1,'',$this->_translator->getMessage('COMMON_CONFIGURATION_PLUGIN'),$this->_translator->getMessage('COMMON_CONFIGURATION_PLUGIN_CALENDAR'),$this->_translator->getMessage('COMMON_CONFIGURATION_PLUGIN_DESC'),false,false,'','',true,false);
      $this->_form->combine();
      $this->_form->addCheckbox('use_tagcloud',1,'',$this->_translator->getMessage('COMMON_CONFIGURATION_PLUGIN'),$this->_translator->getMessage('COMMON_CONFIGURATION_PLUGIN_TAGCLOUD'),$this->_translator->getMessage('COMMON_CONFIGURATION_PLUGIN_DESC'),false,false,'','',true,false);
       
    }

    $this->_form->addEmptyline();
    if (!$this->_item->isPortal()){
       
      $this->_form->addCheckbox('wordpresslink',1,'',$this->_translator->getMessage('COMMON_CONFIGURATION_WORDPRESS'),$this->_translator->getMessage('COMMON_CONFIGURATION_WORDPRESS_HOMELINK_VALUE'),$this->_translator->getMessage('COMMON_CONFIGURATION_WORDPRESS_DESC'),false,false,'','',true,false);
       
       
    }

    global $c_wordpress_path_file;


     
     

    // /new features

    // buttons
    if ( isset($this->_item) and $this->_item->existWordpress() )  {
      $this->_form->addButtonBar('option',$this->_translator->getMessage('COMMON_CHANGE_BUTTON'),'',$this->_translator->getMessage('WORDPRESS_DELETE_BUTTON'),'','');
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
      if ($use_comments=='1'){
        $this->_values['use_comments'] = 1;
      }
      $use_comments_moderation = $this->_item->getWordpressUseCommentsModeration();
      if ($use_comments_moderation=='1'){
        $this->_values['use_comments_moderation'] = 1;
      }

      $use_calendar = $this->_item->getWordpressUseCalendar();
      if ($use_calendar=='1'){
        $this->_values['use_calendar'] = 1;
      }
      $use_tagcloud = $this->_item->getWordpressUseTagCloud();
      if ($use_tagcloud=='1'){
        $this->_values['use_tagcloud'] = 1;
      }

      $wordpresslink = $this->_item->getWordpressHomeLink();
      if ($wordpresslink=='1'){
        $this->_values['wordpresslink'] = 1;
      }
      $this->_values['skin_choice'] = $this->_item->getWordpressSkin();
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
    }
  }

  function _checkValues () {
    $context_item = $this->_environment->getCurrentContextItem();
    $discussion_array = $context_item->getWordpressDiscussionArray();

    if ( !empty($this->_form_post['enable_discussion'])
    and !empty($this->_form_post['new_discussion'])
    and isset($discussion_array[0])
    ) {
      $wordpress_manager = $this->_environment->getWordpressManager();
      $tempDiscussion = $wordpress_manager->getDiscussionWordpressName($this->_form_post['new_discussion']);

      $exists = false;

      foreach($discussion_array as $discussion){
        $discussion = $wordpress_manager->getDiscussionWordpressName($discussion);
        if ($discussion == $tempDiscussion){
          $exists = true;
        }
      }

      if($exists){
        $this->_error_array[] = $this->_translator->getMessage('WORDPRESS_DISCUSSION_EXISTS_ERROR');
        $this->_form->setFailure('new_discussion','');
      }

    }
    if ( empty($this->_form_post['enable_discussion'])
    and (!empty($this->_form_post['enable_discussion_notification']) or !empty($this->_form_post['enable_discussion_notification_groups']))
    ) {
      $this->_error_array[] = $this->_translator->getMessage('WORDPRESS_DISCUSSION_NOT_SELECTED_ERROR');
      $this->_form->setFailure('enable_discussion','');
    }

    if ( !empty($this->_form_post['enable_discussion'])
    and empty($this->_form_post['enable_discussion_notification'])
    and !empty($this->_form_post['enable_discussion_notification_groups'])
    ) {
      $this->_error_array[] = $this->_translator->getMessage('WORDPRESS_DISCUSSION_NOTIFICATION_NOT_SELECTED_ERROR');
      $this->_form->setFailure('enable_discussion_notification','');
    }


    if ( empty($this->_form_post['community_read_access'])
    and (!empty($this->_form_post['community_write_access']))
    ) {
      $this->_error_array[] = $this->_translator->getMessage('WORDPRESS_COOMUNITY_NO_READ_ACCESS_ERROR');
      $this->_form->setFailure('community_read_access','');
    }
  }
}
?>