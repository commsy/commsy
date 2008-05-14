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

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct ($environment) {
      $this->cs_rubric_form($environment);
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
      $this->_form->addTextField('admin','',getMessage('COMMON_WIKI_ADMIN_PW'),'',200,10,true);
      $this->_form->addTextField('edit','',getMessage('COMMON_WIKI_EDIT_PW'),'',200,10,false);
      $this->_form->addTextField('read','',getMessage('COMMON_WIKI_READ_PW'),'',200,10,false);
      $this->_form->addEmptyline();

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
      }elseif( isset($this->_item) ) {
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

      // extension: section_edit
      global $c_pmwiki_path_file;
      if (file_exists($c_pmwiki_path_file.'/cookbook/sectionedit.php')) {
         $this->_form->addEmptyline();
         $this->_form->addCheckbox('wiki_section_edit',1,'',getMessage('WIKI_CONFIGURATION_SECTION_EDIT'),getMessage('WIKI_CONFIGURATION_SECTION_EDIT_VALUE'),'');
         $this->_form->combine();
         $this->_form->addCheckbox('wiki_section_edit_header',1,'','',getMessage('WIKI_CONFIGURATION_SECTION_HEADER_VALUE'),'');
      }

      $this->_form->addEmptyline();
      if (!$this->_item->isPortal()){
         $this->_form->addCheckbox('wikilink',1,'',getMessage('COMMON_CONFIGURATION_WIKI'),getMessage('COMMON_CONFIGURATION_WIKI_HOMELINK_VALUE'),'');
         $this->_form->combine();
         $this->_form->addCheckbox('wikilink2',1,'',getMessage('COMMON_CONFIGURATION_WIKI'),getMessage('COMMON_CONFIGURATION_WIKI_PORTALLINK_VALUE'),'');
      }
      $this->_form->addCheckbox('show_login_box',1,'',getMessage('COMMON_CONFIGURATION_WIKI_SHOW_LOGIN_BOX'),getMessage('COMMON_CONFIGURATION_WIKI_SHOW_LOGIN_BOX_VALUE'),'');
        
      //  new features
      $this->_form->addCheckbox('enable_fckeditor',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_FCKEDITOR'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_FCKEDITOR_VALUE'),'');
      $this->_form->addCheckbox('enable_sitemap',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_SITEMAP'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_SITEMAP_VALUE'),'');
      $this->_form->addCheckbox('enable_statistic',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_STATISTIC'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_STATISTIC_VALUE'),'');
      $this->_form->addCheckbox('enable_search',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_SEARCH'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_SEARCH_VALUE'),'');
      $this->_form->addCheckbox('enable_rss',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_RSS'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_RSS_VALUE'),'');
      
      // already available features - added to form
      $this->_form->addCheckbox('enable_swf',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_SWF'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_SWF_VALUE'),'');
      $this->_form->addCheckbox('enable_wmplayer',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_WMPLAYER'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_WMPLAYER_VALUE'),'');
      $this->_form->addCheckbox('enable_quicktime',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_QUICKTIME'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_QUICKTIME_VALUE'),'');
      $this->_form->addCheckbox('enable_youtube_google_vimeo',1,'',getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_YOUTUBE_GOOGLE_VIMEO'),getMessage('COMMON_CONFIGURATION_WIKI_ENABLE_YOUTUBE_GOOGLE_VIMEO_VALUE'),'');  
      
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
      if (isset($this->_form_post)) {
         $this->_values = $this->_form_post;
      } elseif (isset($this->_item)) {
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
         if ($this->_item->WikiShowCommSyLogin()){
            $this->_values['show_login_box'] = 1;
         }
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
         $this->_values['skin_choice'] = 'pmwiki';
         $this->_values['admin'] = 'admin';
         $this->_values['edit'] = 'edit';
         $this->_values['read'] = '';
         $this->_values['show_login_box'] = '1';
      }
   }
}
?>