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


/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_privateroom_home_form extends cs_rubric_form {

   var $_set_deletion_values = false;
   var $_shown_rss_array = array();
   var $_session_rss_array = array();

   /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct ($params) {
      cs_rubric_form::__construct($params);
      $this->_translator = $this->_environment->getTranslationObject();
   }

   function setSessionRSSArray ($value) {
      $this->_session_rss_array = (array)$value;
   }


   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      $this->_item = $this->_environment->getCurrentContextItem();
      $rss_array = array();
      if (!empty($this->_session_rss_array)){
         $rss_array = $this->_session_rss_array;
      }else{
         $rss_array = $this->_item->getPortletRssArray();
      }
      $shown_rss_array = array();
      foreach ($rss_array as $rss){
         $temp_array['text'] = $rss['title'].': '.$rss['adress'];
         $temp_array['value'] = $rss['title'];
         $shown_rss_array[] = $temp_array;
      }
      $this->_shown_rss_array = $shown_rss_array;
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      // form fields
      $this->_form->addHidden('iid','');

      $radio_values = array();
      $desc = $this->_translator->getMessage('PORTLET_CONFIGURATION_COLUMN_COUNT_DESCRIPTION');
      $radio_values[0]['text'] = $this->_translator->getMessage('PORTLET_CONFIGURATION_COLUMN_COUNT_2');
      $radio_values[0]['value'] = 2;
      $radio_values[1]['text'] =  $this->_translator->getMessage('PORTLET_CONFIGURATION_COLUMN_COUNT_3');
      $radio_values[1]['value'] = 3;
      $this->_form->addRadioGroup('column_count',$this->_translator->getMessage('PORTLET_CONFIGURATION_COLUMN_COUNT'),$desc,$radio_values,'',true,true,'','',false,' style="vertical-align:top;"');

      $this->_form->addCheckbox('new_entry_list',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_NEWEST_ENTRIES'),$this->_translator->getMessage('PORTLET_CONFIGURATION_NEWEST_ENTRIES_VALUE'),$this->_translator->getMessage('PORTLET_CONFIGURATION_NEWEST_ENTRIES_DESC'));
      $this->_form->combine('horizontal');
      $count_array = array();
      $count_array[0]['text'] = '10';
      $count_array[0]['value'] = 10;
      $count_array[1]['text'] = '15';
      $count_array[1]['value'] = 15;
      $count_array[2]['text'] = '20';
      $count_array[2]['value'] = 20;
      $this->_form->addSelect( 'new_entry_list_count',
                               $count_array,
                               '',
                               $this->_translator->getMessage('PORTLET_CONFIGURATION_NEWEST_ENTRIES'),
                               '',
                               '',
                               '',
                               '',
                               false,
                               '',
                               '',
                               '',
                               '',
                               '');

      $this->_form->addCheckbox('active_rooms',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_ACTIVE_ROOMS'),$this->_translator->getMessage('PORTLET_CONFIGURATION_ACTIVE_ROOMS_VALUE'),$this->_translator->getMessage('PORTLET_CONFIGURATION_ACTIVE_ROOMS_DESC'));
      $this->_form->combine('horizontal');
      $count_array = array();
      $count_array[0]['text'] = '2';
      $count_array[0]['value'] = 2;
      $count_array[1]['text'] = '4';
      $count_array[1]['value'] = 4;
      $count_array[2]['text'] = '6';
      $count_array[2]['value'] = 6;
      $count_array[3]['text'] = '8';
      $count_array[3]['value'] = 8;
      $this->_form->addSelect( 'active_rooms_count',
                               $count_array,
                               '',
                               $this->_translator->getMessage('PORTLET_CONFIGURATION_ACTIVE_ROOMS'),
                               '',
                               '',
                               '',
                               '',
                               false,
                               '',
                               '',
                               '',
                               '',
                               '');

      $this->_form->addCheckbox('search_box',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_SEARCH_BOX'),$this->_translator->getMessage('PORTLET_CONFIGURATION_SHOW_ON_HOME'),$this->_translator->getMessage('PORTLET_CONFIGURATION_SEARCH_BOX_DESC'));
      $this->_form->addCheckbox('roomwide_search_box',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_ROOMWIDE_SEARCH_BOX'),$this->_translator->getMessage('PORTLET_CONFIGURATION_SHOW_ON_HOME'),$this->_translator->getMessage('PORTLET_CONFIGURATION_ROOMWIDE_SEARCH_BOX_DESC'));
      #$this->_form->addCheckbox('dokuverser_box',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_DOKUVERSER_BOX'),$this->_translator->getMessage('PORTLET_CONFIGURATION_DOKUVERSER_BOX_DESC'),$this->_translator->getMessage('PORTLET_CONFIGURATION_DOKUVERSER_BOX_DESC'));
      $this->_form->addCheckbox('buzzword_box',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_BUZZWORD_BOX'),$this->_translator->getMessage('PORTLET_CONFIGURATION_SHOW_ON_HOME'),$this->_translator->getMessage('PORTLET_CONFIGURATION_BUZZWORD_BOX_DESC'));
      $this->_form->addCheckbox('configuration_box',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_CONFIGURATION_BOX'),$this->_translator->getMessage('PORTLET_CONFIGURATION_SHOW_ON_HOME'),$this->_translator->getMessage('PORTLET_CONFIGURATION_CONFIGURATION_BOX_DESC'));
      $this->_form->addCheckbox('new_item_box',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_NEW_ITEM_BOX'),$this->_translator->getMessage('PORTLET_CONFIGURATION_SHOW_ON_HOME'),$this->_translator->getMessage('PORTLET_CONFIGURATION_NEW_ITEM_BOX_DESC'));
      $this->_form->addCheckbox('weather_box',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_WEATHER_BOX'),$this->_translator->getMessage('PORTLET_CONFIGURATION_SHOW_ON_HOME'),$this->_translator->getMessage('PORTLET_CONFIGURATION_WEATHER_BOX_DESC'));

      $this->_form->addCheckbox('clock_box',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_CLOCK_BOX'),$this->_translator->getMessage('PORTLET_CONFIGURATION_SHOW_ON_HOME'),$this->_translator->getMessage('PORTLET_CONFIGURATION_CLOCK_BOX_DESC'));

      $this->_form->addCheckbox('youtube',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_YOUTUBE'),$this->_translator->getMessage('PORTLET_CONFIGURATION_YOUTUBE_VALUE'),$this->_translator->getMessage('PORTLET_CONFIGURATION_YOUTUBE_DESC'));
      $this->_form->combine('horizontal');
      $this->_form->addTextfield('youtube_account','',$this->_translator->getMessage('PORTLET_CONFIGURATION_YOUTUBE_ACCOUNT'),$this->_translator->getMessage('PORTLET_CONFIGURATION_YOUTUBE_DESC'),100,28);

      $this->_form->addCheckbox('flickr',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_FLICKR'),$this->_translator->getMessage('PORTLET_CONFIGURATION_FLICKR_VALUE'),$this->_translator->getMessage('PORTLET_CONFIGURATION_FLICKR_DESC'));
      $this->_form->combine('horizontal');
      $this->_form->addTextfield('flickr_id','',$this->_translator->getMessage('PORTLET_CONFIGURATION_FLICKR_ID'),$this->_translator->getMessage('PORTLET_CONFIGURATION_FLICKR_DESC'),100,28);

      $this->_form->addCheckbox('twitter',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_TWITTER'),$this->_translator->getMessage('PORTLET_CONFIGURATION_TWITTER_VALUE'),$this->_translator->getMessage('PORTLET_CONFIGURATION_TWITTER_DESC'));
      $this->_form->combine('horizontal');
      $this->_form->addTextfield('twitter_account','',$this->_translator->getMessage('PORTLET_CONFIGURATION_TWITTER_ACCOUNT'),$this->_translator->getMessage('PORTLET_CONFIGURATION_TWITTER_DESC'),100,28);


      $this->_form->addCheckbox('show_rss',1,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_RSS'),$this->_translator->getMessage('PORTLET_CONFIGURATION_SHOW_ON_HOME'),$this->_translator->getMessage('PORTLET_CONFIGURATION_RSS_DESC'));
      $this->_form->combine();
      $context_item = $this->_environment->getCurrentContextItem();
      if ( !empty ($this->_shown_rss_array) ) {
         $this->_form->addCheckBoxGroup('rsslist',$this->_shown_rss_array,'',$this->_translator->getMessage('PORTLET_CONFIGURATION_RSS'),'',false,false);
         $this->_form->combine();
      }
      $this->_form->addText('rss_text',$this->_translator->getMessage('PORTLET_CONFIGURATION_RSS'),$this->_translator->getMessage('PORTLET_CONFIGURATION_RSS_DESCRIPTION'));
      $this->_form->combine('horizontal');
      $this->_form->addTextfield('rss_title','',$this->_translator->getMessage('PORTLET_CONFIGURATION_RSS_TITLE'),$this->_translator->getMessage('PORTLET_CONFIGURATION_RSS_TITLE_DESC'),200,10,'');
      $this->_form->combine('horizontal');
      $this->_form->addTextfield('rss_adress','',$this->_translator->getMessage('PORTLET_CONFIGURATION_RSS_ADRESS'),$this->_translator->getMessage('PORTLET_CONFIGURATION_RSS_ADRESS_DESC'),200,32,'');
/*      $this->_form->combine('horizontal');
      $count_array = array();
      $count_array[0]['text'] = $this->_translator->getMessage('PORTLET_CONFIGURATION_RSS_SHORT');
      $count_array[0]['value'] = 1;
      $count_array[1]['text'] = $this->_translator->getMessage('PORTLET_CONFIGURATION_RSS_LONG');
      $count_array[1]['value'] = 2;
      $this->_form->addSelect( 'rss_display',
                               $count_array,
                               '',
                               $this->_translator->getMessage('PORTLET_CONFIGURATION_RSS_LENGTH'),
                               '',
                               '',
                               '',
                               '',
                               false,
                               '',
                               '',
                               '',
                               '',
                               '');*/
      $this->_form->combine('horizontal');
      $this->_form->addButton('option',$this->_translator->getMessage('PORTLET_CONFIGURATION_RSS_ADD_BUTTON'),'','',80);
      $this->_form->addHidden('rss_display','1');
      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'');

   }



   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the context item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if (isset($this->_form_post)){
         $this->_values = $this->_form_post;
      }elseif (isset($this->_item)){
         $this->_values['column_count'] = $this->_item->getPortletColumnCount();
         if ($this->_item->getPortletShowNewEntryList()){
            $this->_values['new_entry_list'] = '1';
         }else{
            $this->_values['new_entry_list'] = '0';
         }
         $this->_values['new_entry_list_count'] = $this->_item->getPortletNewEntryListCount();
         if ($this->_item->getPortletShowActiveRoomList()){
            $this->_values['active_rooms'] = '1';
         }else{
            $this->_values['active_rooms'] = '0';
         }
         $this->_values['active_rooms_count'] = $this->_item->getPortletActiveRoomCount();
         if ($this->_item->getPortletShowSearchBox()){
            $this->_values['search_box'] = '1';
         }else{
            $this->_values['search_box'] = '0';
         }
         if ($this->_item->getPortletShowRoomWideSearchBox()){
            $this->_values['roomwide_search_box'] = '1';
         }else{
            $this->_values['roomwide_search_box'] = '0';
         }
         if ($this->_item->getPortletShowDokuverserBox()){
            $this->_values['dokuverser_box'] = '1';
         }else{
            $this->_values['dokuverser_box'] = '0';
         }
         if ($this->_item->getPortletShowBuzzwordBox()){
            $this->_values['buzzword_box'] = '1';
         }else{
            $this->_values['buzzword_box'] = '0';
         }
         if ($this->_item->getPortletShowConfigurationBox()){
            $this->_values['configuration_box'] = '1';
         }else{
            $this->_values['configuration_box'] = '0';
         }
         if ($this->_item->getPortletShowNewItemBox()){
            $this->_values['new_item_box'] = '1';
         }else{
            $this->_values['new_item_box'] = '0';
         }
         if ($this->_item->getPortletShowWeatherBox()){
            $this->_values['weather_box'] = '1';
         }else{
            $this->_values['weather_box'] = '0';
         }
         if ($this->_item->getPortletShowClockBox()){
            $this->_values['clock_box'] = '1';
         }else{
            $this->_values['clock_box'] = '0';
         }
         if ($this->_item->getPortletShowTwitter()){
            $this->_values['twitter'] = '1';
         }else{
            $this->_values['twitter'] = '0';
         }
         $this->_values['twitter_account'] = $this->_item->getPortletTwitterAccount();;
         if ($this->_item->getPortletShowYouTube()){
            $this->_values['youtube'] = '1';
         }else{
            $this->_values['youtube'] = '0';
         }
         $this->_values['youtube_account'] = $this->_item->getPortletYouTubeAccount();
         if ($this->_item->getPortletShowFlickr()){
            $this->_values['flickr'] = '1';
         }else{
            $this->_values['flickr'] = '0';
         }
         $this->_values['flickr_id'] = $this->_item->getPortletFlickrID();

         $rss_array = $this->_item->getPortletRssArray();
         $shown_rss_array = array();
         foreach ($rss_array as $rss){
            $shown_rss_array[] = $rss['title'];
         }
         $this->_values['rsslist'] = $shown_rss_array;
         if ($this->_item->getPortletShowRSS()){
            $this->_values['show_rss'] = '1';
         }else{
            $this->_values['show_rss'] = '0';
         }
      }else{
         $this->_values['column_count'] = '3';
         $this->_values['new_entry_list'] = '1';
         $this->_values['new_entry_list_count'] = '15';
         $this->_values['active_rooms'] = '1';
         $this->_values['active_rooms_count'] = '4';
         $this->_values['search_box'] = '1';
         $this->_values['roomwide_search_box'] = '0';
         $this->_values['buzzword_box'] = '1';
         $this->_values['configuration_box'] = '1';
         $this->_values['new_item_box'] = '1';
         $this->_values['clock_box'] = '1';
         $this->_values['weather_box'] = '1';
         $this->_values['dokuverser_box'] = '1';
         $this->_values['twitter'] = '0';
         $this->_values['twitter_account'] = '';
         $this->_values['youtube'] = '0';
         $this->_values['youtube_account'] = '';
         $this->_values['flickr'] = '0';
         $this->_values['flickr_id'] = '';
         $this->_values['show_rss'] = '0';
      }
   }

   function _checkValues () {
      $context_item = $this->_environment->getCurrentContextItem();
      if ( !empty($this->_form_post['twitter'])
           and empty($this->_form_post['twitter_account'])
         ) {
         $this->_error_array[] = $this->_translator->getMessage('PORTLET_CONFIGURATION_TWITTER_WITHOUT_ACCOUNT');
         $this->_form->setFailure('twitter_account','');
      }
      if ( !empty($this->_form_post['youtube'])
           and empty($this->_form_post['youtube_account'])
         ) {
         $this->_error_array[] = $this->_translator->getMessage('PORTLET_CONFIGURATION_YOUTUBE_WITHOUT_ACCOUNT');
         $this->_form->setFailure('youtube_account','');
      }
      if ( !empty($this->_form_post['flickr'])
           and empty($this->_form_post['flickr_id'])
         ) {
         $this->_error_array[] = $this->_translator->getMessage('PORTLET_CONFIGURATION_FLICKR_WITHOUT_ID');
         $this->_form->setFailure('flickr_id','');
      }
      if ( !empty($this->_form_post['rss_title'])
           and empty($this->_form_post['rss_adress'])
         ) {
         $this->_error_array[] = $this->_translator->getMessage('PORTLET_CONFIGURATION_RSS_WITHOUT_ADRESS');
         $this->_form->setFailure('rss_adress','');
      }
      if ( !empty($this->_form_post['rss_adress'])
           and empty($this->_form_post['rss_title'])
         ) {
         $this->_error_array[] = $this->_translator->getMessage('PORTLET_CONFIGURATION_RSS_WITHOUT_TITLE');
         $this->_form->setFailure('rss_title','');
      }
   }
}
?>