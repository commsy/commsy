<?PHP
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez, Johannes Schultze
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

include_once('classes/cs_rubric_form.php');

$_skin_array = array();

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_wiki_form extends cs_rubric_form {

   var $_set_deletion_values = false;

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct ($environment) {
      $this->cs_rubric_form($environment);
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
      foreach($this->_skin_array as $skin){
         $temp_array = array();
         $temp_array['text']  = $skin;
         $temp_array['value'] = $skin; //hvv
         $this->_array_info_text[$skin] = $temp_array;
      }
      ksort($this->_array_info_text);
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      // form fields
      $this->_form->addHidden('iid','');
      $this->_form->addTextfield('wikititle','',getMessage('COMMON_TITLE'),getMessage('DATES_TITLE_DESC'),200,28,true);


      $this->_form->addSelect( 'skin_choice',
                               $this->_array_info_text,
                               '',
                               getMessage('CONFIGURATION_SKIN_FORM_CHOOSE_TEXT'),
                               '',
                               '',
                               '',
                               '',
                               true,
                               getMessage('COMMON_CHOOSE_BUTTON'),
                               'option',
               '',
               '',
               '15',
                               true);
      $this->_form->combine();
      if ( !empty($this->_form_post['skin_choice']) ) {
         $desc = '<img src="images/wiki/'.$this->_form_post['skin_choice'].'.gif" alt="'.getMessage('COMMON_SKIN').'" style=" border:1px solid black; vertical-align: middle;"/>';
         $this->_form->addText('example','',$desc);
      }elseif( isset($this->_item) and !$this->_set_deletion_values) {
         $skin = $this->_item->getWikiSkin();
         if (!empty ($skin) ){
            $desc = '<img src="images/wiki/'.$this->_item->getWikiSkin().'.gif" alt="'.getMessage('COMMON_SKIN').'" style=" border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example','',$desc);
         }else{
            $desc = '<img src="images/wiki/pmwiki.gif" alt="'.getMessage('COMMON_SKIN').'" style=" border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example','',$desc);
         }
      }else{
         $desc = '<img src="images/wiki/pmwiki.gif" alt="'.getMessage('COMMON_SKIN').'" style=" border:1px solid black; vertical-align: middle;"/>';
         $this->_form->addText('example','',$desc);
      }

      $this->_form->addTextField('admin','',getMessage('COMMON_WIKI_PWS'),getMessage('COMMON_WIKI_PWS_DESC'),200,10,true,'','','','left',getMessage('COMMON_WIKI_ADMIN_PW'),'',false,'','10',true,false);
      $this->_form->combine();
      $this->_form->addTextField('edit','',getMessage('COMMON_WIKI_EDIT_PW'),'',200,10,false,'','','','left',getMessage('COMMON_WIKI_EDIT_PW'));
      $this->_form->combine();
      $this->_form->addTextField('read','',getMessage('COMMON_WIKI_READ_PW'),'',200,10,false,'','','','left',getMessage('COMMON_WIKI_READ_PW'));

      $this->_form->addEmptyline();
      if (!$this->_item->isPortal()){
         $this->_form->addCheckbox('wikilink',1,'',getMessage('COMMON_CONFIGURATION_WIKI'),getMessage('COMMON_CONFIGURATION_WIKI_HOMELINK_VALUE'),getMessage('COMMON_CONFIGURATION_WIKI_DESC'),false,false,'','',true,false);
         $this->_form->combine();
         $this->_form->addCheckbox('wikilink2',1,'',getMessage('COMMON_CONFIGURATION_WIKI'),getMessage('COMMON_CONFIGURATION_WIKI_PORTALLINK_VALUE'),'');
         $this->_form->combine();
      }
      $this->_form->addCheckbox('show_login_box',1,'',getMessage('COMMON_CONFIGURATION_WIKI_SHOW_LOGIN_BOX'),getMessage('COMMON_CONFIGURATION_WIKI_SHOW_LOGIN_BOX_VALUE'),'');


      $this->_form->addEmptyline();
      // already available features - added to form

      global $c_pmwiki_path_file;

      $features_media_available = array();

      if (file_exists($c_pmwiki_path_file.'/cookbook/swf.php')) {
        // SWF
        $features_media_available[] = array('enable_swf',1,'','COMMON_CONFIGURATION_WIKI_ENABLE_MEDIA','COMMON_CONFIGURATION_WIKI_ENABLE_SWF_VALUE','COMMON_CONFIGURATION_WIKI_ENABLE_MEDIA_DESC');
      }
      if (file_exists($c_pmwiki_path_file.'/cookbook/wmplayer.php')) {
        // WMPlayer
        $features_media_available[] = array('enable_wmplayer',1,'','COMMON_CONFIGURATION_WIKI_ENABLE_MEDIA','COMMON_CONFIGURATION_WIKI_ENABLE_WMPLAYER_VALUE','COMMON_CONFIGURATION_WIKI_ENABLE_MEDIA_DESC');
      }
      if (file_exists($c_pmwiki_path_file.'/cookbook/quicktime.php')) {
        // Quicktime
        $features_media_available[] = array('enable_quicktime',1,'','COMMON_CONFIGURATION_WIKI_ENABLE_MEDIA','COMMON_CONFIGURATION_WIKI_ENABLE_QUICKTIME_VALUE','COMMON_CONFIGURATION_WIKI_ENABLE_MEDIA_DESC');
      }
      if (file_exists($c_pmwiki_path_file.'/cookbook/swf-sites2.php')) {
        // Google, Youtube, Vimeo
        $features_media_available[] = array('enable_youtube_google_vimeo',1,'','COMMON_CONFIGURATION_WIKI_ENABLE_MEDIA','COMMON_CONFIGURATION_WIKI_ENABLE_YOUTUBE_GOOGLE_VIMEO_VALUE','COMMON_CONFIGURATION_WIKI_ENABLE_MEDIA_DESC');
      }

      for ($index = 0; $index < sizeof($features_media_available); $index++) {
            $array_element = $features_media_available[$index];
            $this->_form->addCheckbox($array_element[0], $array_element[1], $array_element[2], getMessage($array_element[3]), getMessage($array_element[4]), getMessage($array_element[5]),false,false,'','',true,false);
            if($index < sizeof($features_media_available)-1){
                $this->_form->combine();
            }
       }
       if(sizeof($features_media_available) > 0){
            $this->_form->addEmptyline();
       }

//      $this->_form->addCheckbox('enable_swf',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_MEDIA'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_SWF_VALUE'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_MEDIA_DESC'),false,false,'','',true,false);
//      $this->_form->combine();
//      $this->_form->addCheckbox('enable_wmplayer',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_WMPLAYER'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_WMPLAYER_VALUE'),'',false,false,'','',true);
//      $this->_form->combine();
//      $this->_form->addCheckbox('enable_quicktime',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_QUICKTIME'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_QUICKTIME_VALUE'),'',false,false,'','',true);
//      $this->_form->combine();
//      $this->_form->addCheckbox('enable_youtube_google_vimeo',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_YOUTUBE_GOOGLE_VIMEO'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_YOUTUBE_GOOGLE_VIMEO_VALUE'),'');

      //  new features
      $this->_form->addCheckbox('enable_search',1,'',getMessage('COMMON_CONFIGURATION_WIKI_BASIC_FUNCTIONS'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_SEARCH_VALUE'),getMessage('COMMON_CONFIGURATION_WIKI_BASIC_FUNCTIONS_DESC'),false,false,'','',true,false);
      $this->_form->combine();
      $this->_form->addCheckbox('enable_sitemap',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_SITEMAP'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_SITEMAP_VALUE'),'');
      // extension: section_edit

      if (file_exists($c_pmwiki_path_file.'/cookbook/sectionedit.php')) {
         $this->_form->combine();
         $this->_form->addCheckbox('wiki_section_edit',1,'',getMessage('WIKI_CONFIGURATION_SECTION_EDIT'),getMessage('WIKI_CONFIGURATION_SECTION_EDIT_VALUE'),'');
         $this->_form->combine();
         $this->_form->addCheckbox('wiki_section_edit_header',1,'','',getMessage('WIKI_CONFIGURATION_SECTION_HEADER_VALUE'),'');
      }
      $this->_form->addEmptyline();

      $features_available = array();

      if (file_exists($c_pmwiki_path_file.'/cookbook/edit_fckeditor.php')) {
        // FCKEditor
        $features_available[] = array('enable_fckeditor',1,'','COMMON_CONFIGURATION_WIKI_EXTRAS','COMMON_CONFIGURATION_WIKI_ENABLE_FCKEDITOR_VALUE','COMMON_CONFIGURATION_WIKI_EXTRAS_DESC');
      }
      if ((file_exists($c_pmwiki_path_file.'/cookbook/totalcounter.php')) && (file_exists($c_pmwiki_path_file.'/cookbook/totalcounterlink.php'))) {
        // Statistic
        $features_available[] = array('enable_statistic',1,'','COMMON_CONFIGURATION_WIKI_EXTRAS','COMMON_CONFIGURATION_WIKI_ENABLE_STATISTIC_VALUE','COMMON_CONFIGURATION_WIKI_EXTRAS_DESC');
      }
      if (file_exists($c_pmwiki_path_file.'/cookbook/feedlinks.php')) {
        // RSS
        $features_available[] = array('enable_rss',1,'','COMMON_CONFIGURATION_WIKI_EXTRAS','COMMON_CONFIGURATION_WIKI_ENABLE_RSS_VALUE','COMMON_CONFIGURATION_WIKI_EXTRAS_DESC');
      }
      if (file_exists($c_pmwiki_path_file.'/cookbook/wikilog.php')) {
        // Calendar
        if((($this->_environment->getCurrentContextItem()->getLanguage() == "de") && (file_exists($c_pmwiki_path_file.'/cookbook/wikilog-i18n-de.php'))) ||
           (($this->_environment->getCurrentContextItem()->getLanguage() == "en") && (file_exists($c_pmwiki_path_file.'/cookbook/wikilog-i18n-en.php')))){
        	   $features_available[] = array('enable_calendar',1,'','COMMON_CONFIGURATION_WIKI_EXTRAS','COMMON_CONFIGURATION_WIKI_ENABLE_CALENDAR_VALUE','COMMON_CONFIGURATION_WIKI_EXTRAS_DESC');
        }
      }
      if (file_exists($c_pmwiki_path_file.'/cookbook/gallery.php')) {
        // Gallery
        $features_available[] = array('enable_gallery',1,'','COMMON_CONFIGURATION_WIKI_EXTRAS','COMMON_CONFIGURATION_WIKI_ENABLE_GALLERY_VALUE','COMMON_CONFIGURATION_WIKI_EXTRAS_DESC');
      }
      if (file_exists($c_pmwiki_path_file.'/cookbook/postitnotes.php')) {
        // Notice
        $features_available[] = array('enable_notice',1,'','COMMON_CONFIGURATION_WIKI_EXTRAS','COMMON_CONFIGURATION_WIKI_ENABLE_NOTICE_VALUE','COMMON_CONFIGURATION_WIKI_EXTRAS_DESC');
      }
      if ((file_exists($c_pmwiki_path_file.'/cookbook/pmwiki2pdf/pmwiki2pdf.php')) && (file_exists($c_pmwiki_path_file.'/cookbook/pmwiki2pdflink.php'))) {
        // PDF
        $features_available[] = array('enable_pdf',1,'','COMMON_CONFIGURATION_WIKI_EXTRAS','COMMON_CONFIGURATION_WIKI_ENABLE_PDF_VALUE','COMMON_CONFIGURATION_WIKI_EXTRAS_DESC');
      }

      for ($index = 0; $index < sizeof($features_available); $index++) {
		      $array_element = $features_available[$index];
            $this->_form->addCheckbox($array_element[0], $array_element[1], $array_element[2], getMessage($array_element[3]), getMessage($array_element[4]), getMessage($array_element[5]),false,false,'','',true,false);
            if($index < sizeof($features_available)-1){
            	$this->_form->combine();
            }
	   }

 	  $this->_form->addEmptyline();

	  $this->_form->addCheckbox('enable_discussion',1,'',getMessage('COMMON_WIKI_DISCUSSION'),getMessage('COMMON_WIKI_DISCUSSION_DESC'),getMessage('COMMON_WIKI_DISCUSSION_ENABLE'),false,false,'','',true,false);
      $this->_form->combine();
      
      $context_item = $this->_environment->getCurrentContextItem();
      $discussion_array = $context_item->getWikiDiscussionArray();
     if (isset($discussion_array[0])){
     	$first = true;
        $current_discussions = getMessage('COMMON_WIKI_EXISTING_DISCUSSIONS') . ': ';
        foreach($discussion_array as $discussion){
        	if(!$first){
        		$discussion = ', ' . $discussion;
        	}
        	if($first){
        		$first = false;
        	}
        	$current_discussions .= $discussion;
        }
     } else {
     	$current_discussions = getMessage('COMMON_NO_ENTRIES');
     }
      
      $this->_form->addText('wiki_existing_discussions','',$current_discussions);
      $this->_form->combine();
      $this->_form->addTextField('new_discussion','',getMessage('COMMON_WIKI_DISCUSSION_NEW'),'',200,10,false,'','','','left',getMessage('COMMON_WIKI_DISCUSSION_NEW'));
      
      

//      $this->_form->addCheckbox('enable_fckeditor',1,'',getMessage('COMMON_CONFIGURATION_WIKI_EXTRAS'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_FCKEDITOR_VALUE'),getMessage('COMMON_CONFIGURATION_WIKI_EXTRAS_DESC'),false,false,'','',true,false);
//      $this->_form->combine();
//      $this->_form->addCheckbox('enable_statistic',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_STATISTIC'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_STATISTIC_VALUE'),'');
//      $this->_form->combine();
//      $this->_form->addCheckbox('enable_rss',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_RSS'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_RSS_VALUE'),'');
//      $this->_form->combine();
//      $this->_form->addCheckbox('enable_calendar',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_CALENDAR'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_CALENDAR_VALUE'),'');
//      $this->_form->combine();
//      $this->_form->addCheckbox('enable_gallery',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_GALLERY'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_GALLERY_VALUE'),'');
//      $this->_form->combine();
//      $this->_form->addCheckbox('enable_notice',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_NOTICE'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_NOTICE_VALUE'),'');
//      $this->_form->combine();
//      $this->_form->addCheckbox('enable_pdf',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_PDF'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_PDF_VALUE'),'');


      // /new features

      // buttons
      if ( isset($this->_item) and $this->_item->existWiki() )  {
         $this->_form->addButtonBar('option',getMessage('COMMON_CHANGE_BUTTON'),'',getMessage('WIKI_DELETE_BUTTON'),'','');
      } else {
         $this->_form->addButtonBar('option',getMessage('WIKI_SAVE_BUTTON'));
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
         $this->_values['wikititle'] = $this->_item->getWikiTitle();
         $home_link = $this->_item->getWikiHomeLink();
         if ($home_link=='1'){
            $this->_values['wikilink'] = 1;
         }
         $portal_link = $this->_item->getWikiPortalLink();
         if ($portal_link=='1'){
            $this->_values['wikilink2'] = 1;
         }
         if ($this->_item->WikiShowCommSyLogin() == "1"){
            $this->_values['show_login_box'] = 1;
         }
         //  new features
         if ($this->_item->WikiEnableFCKEditor() == "1"){
            $this->_values['enable_fckeditor'] = 1;
         }
         if ($this->_item->WikiEnableSitemap() == "1"){
            $this->_values['enable_sitemap'] = 1;
         }
         if ($this->_item->WikiEnableStatistic() == "1"){
            $this->_values['enable_statistic'] = 1;
         }
         if ($this->_item->WikiEnableSearch() == "1"){
            $this->_values['enable_search'] = 1;
         }
         if ($this->_item->WikiEnableRss() == "1"){
            $this->_values['enable_rss'] = 1;
         }
         if ($this->_item->WikiEnableCalendar() == "1"){
            $this->_values['enable_calendar'] = 1;
         }
         if ($this->_item->WikiEnableGallery() == "1"){
            $this->_values['enable_gallery'] = 1;
         }
         if ($this->_item->WikiEnableNotice() == "1"){
            $this->_values['enable_notice'] = 1;
         }
         if ($this->_item->WikiEnablePdf() == "1"){
            $this->_values['enable_pdf'] = 1;
         }

         if ($this->_item->WikiEnableSwf() == "1"){
            $this->_values['enable_swf'] = 1;
         }
         if ($this->_item->WikiEnableWmplayer() == "1"){
            $this->_values['enable_wmplayer'] = 1;
         }
         if ($this->_item->WikiEnableQuicktime() == "1"){
            $this->_values['enable_quicktime'] = 1;
         }
         if ($this->_item->WikiEnableYoutubeGoogleVimeo() == "1"){
            $this->_values['enable_youtube_google_vimeo'] = 1;
         }
         if ($this->_item->WikiEnableDiscussion() == "1"){
            $this->_values['enable_discussion'] = 1;
         }
         $this->_values['new_discussion'] = '';
         // /new features
         if ( $this->_item->wikiWithSectionEdit() ) {
            $this->_values['wiki_section_edit'] = 1;
         }
         if ( $this->_item->wikiWithHeaderForSectionEdit() ) {
            $this->_values['wiki_section_edit_header'] = 1;
         }
         $this->_values['skin_choice'] = $this->_item->getWikiSkin();
         $this->_values['admin'] = $this->_item->getWikiAdminPW();
         $this->_values['edit'] = $this->_item->getWikiEditPW();
         $this->_values['read'] = $this->_item->getWikiReadPW();
      } else {
         $this->_values['wikititle'] = $this->_item->getWikiTitle();
         $this->_values['skin_choice'] = 'pmwiki';
         $this->_values['admin'] = 'admin';
         $this->_values['edit'] = 'edit';
         $this->_values['read'] = '';
         $this->_values['show_login_box'] = '1';
      }
   }
   
   function _checkValues () {
   	  $context_item = $this->_environment->getCurrentContextItem();
   	  $discussion_array = $context_item->getWikiDiscussionArray();
      if ( !empty($this->_form_post['enable_discussion'])
           and empty($this->_form_post['new_discussion'])
           and !isset($discussion_array[0])
         ) {
         $this->_error_array[] = getMessage('WIKI_DISCUSSION_EMPTY_ERROR');
         $this->_form->setFailure('new_discussion','');
      }
      if ( !empty($this->_form_post['enable_discussion'])
           and !empty($this->_form_post['new_discussion'])
           and isset($discussion_array[0])
         ) {
        $tempDiscussion = $this->checkDiscussion($this->_form_post['new_discussion']);

        $exists = false;
        
        foreach($discussion_array as $discussion){
            $discussion = $this->checkDiscussion($discussion);
            pr($discussion);
            pr($tempDiscussion);
            echo '----';
        	if ($discussion == $tempDiscussion){
        		$exists = true;
        	}
        }

        if($exists){
        	$this->_error_array[] = getMessage('WIKI_DISCUSSION_EXISTS_ERROR');
            $this->_form->setFailure('new_discussion','');
        }

      }
   }
   
   function checkDiscussion($discussion){
   	    $discussionArray = explode (' ', $discussion);
        for ($index = 0; $index < sizeof($discussionArray); $index++) {
            $discussionArray[$index] = str_replace("ä", "ae", $discussionArray[$index]);
            $discussionArray[$index] = str_replace("Ä", "Ae", $discussionArray[$index]);
            $discussionArray[$index] = str_replace("ö", "oe", $discussionArray[$index]);
            $discussionArray[$index] = str_replace("Ö", "Oe", $discussionArray[$index]);
            $discussionArray[$index] = str_replace("ü", "ue", $discussionArray[$index]);
            $discussionArray[$index] = str_replace("Ü", "Ue", $discussionArray[$index]);
            $discussionArray[$index] = str_replace("ß", "ss", $discussionArray[$index]);
            $first_letter = substr($discussionArray[$index], 0, 1);
            $rest = substr($discussionArray[$index], 1);
            $first_letter = strtoupper($first_letter);
            $discussionArray[$index] = $first_letter . $rest;
        }
        $discussion = implode('',$discussionArray);
        return $discussion;
   }
}
?>