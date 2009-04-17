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

$this->includeClass(VIEW);
include_once('functions/text_functions.php');



/**
 *  class for CommSy activity panel
 */
class cs_activity_view extends cs_view {

   var $_percent_active_members = 0;
   var $_active_members = 0;
   var $_count_new_entries = 0;
   var $_count_page_impressions = 0;
   var $_config_boxes = false;

   var $_search_text = NULL;

   /** constructor
    *
    * @param object  environment            environment of the page
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   public function __construct ( $params ) {
      $this->cs_view($params);
      $this->setViewName('activity');
      // Determine time spread
      $context = $this->_environment->getCurrentContextItem();
      if ($this->_environment->inCommunityRoom()){
         $time_spread = 90;
      }else{
         $time_spread = $context->getTimeSpread();
      }
      $this->_view_title = $this->_translator->getMessage('HOME_ACTIVITY_SHORT_HEADER').' ('.$this->_translator->getMessage('HOME_ACTIVITY_SHORT_DESCRIPTION', $time_spread).'):';
   }

   function setPercentActiveMembers ( $value ) {
      $this->_percent_active_members = (int)$value;
   }

   function getPercentActiveMembers () {
      return $this->_percent_active_members;
   }

   function setActiveMembers ( $value ) {
      $this->_active_members = (int)$value;
   }

   function getActiveMembers () {
      return $this->_active_members;
   }

   function setCountNewEntries ( $value ) {
      $this->_count_new_entries = (int)$value;
   }

   function getCountNewEntries () {
      return $this->_count_new_entries;
   }

   function setCountPageImpressions ( $value ) {
      $this->_count_page_impressions = (int)$value;
   }

   function getCountPageImpressions () {
      return $this->_count_page_impressions;
   }


    /** get the value of the search box
    * this method gets the search value of the list
    *
    * @param string  $this->_search_text
    */
    function getSearchText (){
       return $this->_search_text;
    }

    // @segment-begin 8397  setSearchText($search_tex)-sets:_search_text/_search_array
    /** set the value of the search box
    * this method sets the search value of the list
    *
    * @param string  $this->_search_text
    */
    function setSearchText ($search_text){
       $this->_search_text = $search_text;
       $literal_array = array();
       $search_array = array();

       //find all occurances of quoted text and store them in an array
       preg_match_all('~("(.+?)")~u',$search_text,$literal_array);
       //delete this occurances from the original string
       $search_text = preg_replace('~("(.+?)")~u','',$search_text);

       $search_text = preg_replace('~-(\w+)~u','',$search_text);

       //clean up the resulting array from quots
       $literal_array = str_replace('"','',$literal_array[2]);
       //clean up rest of $limit and get an array with entrys
       $search_text = str_replace('  ',' ',$search_text);
       $search_text = trim($search_text);
       $split_array = explode(' ',$search_text);

       //check which array contains search limits and act accordingly
       if ($split_array[0] != '' AND count($literal_array) > 0) {
          $search_array = array_merge($split_array,$literal_array);
       } else {
          if ($split_array[0] != '') {
             $search_array = $split_array;
          } else {
             $search_array = $literal_array;
          }
       }
       $this->_search_array = $search_array;
    }


/*   function _getHomeActionsAsHTML(){
      $current_context = $this->_environment->getCurrentContextItem();
      $html = '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title" style="font-weight:bold;">'.$current_context->getTitle().'</div>';
      $html .= '<div class="right_box_main" style="font-size:10pt; padding-top:2px;padding-bottom:3px; padding-left:5px;">'.LF;
      $html .= '<div style="padding-bottom:0px;">'.LF;
      $html .= '<div style="float:right; padding:2px 0px 0px 0px; margin:0px;">'.LF;
      $html .= '<input type="image" src="images/commsyicons/22x22/search.png" style="vertical-align:top;" alt="'.getMessage('COMMON_SEARCH_BUTTON').'"/>';
      $html .= '<input style="width:110px; font-size:10pt; margin-bottom:5px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>'.LF;
      $html .= '</div>'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      $params['mode']='print';
      $image = '<img src="images/commsyicons/22x22/print.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_LIST_PRINTVIEW').'"/>';
      $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'detail',
                                    $params,
                                    $image,
                                    getMessage('COMMON_LIST_PRINTVIEW')).LF;
      unset($params['mode']);
      $context_user = $this->_environment->getCurrentUserItem();
      if ($context_user->isModerator()){
         $image = '<img src="images/commsyicons/22x22/config.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_CONFIGURATION').'"/>';
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'index',
                                       '',
                                       $image,
                                       getMessage('COMMON_CONFIGURATION')).LF;
      }else{
         $image = '<img src="images/commsyicons/22x22/config_grey.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_CONFIGURATION').'"/>';
         $html .= '<span class="disabled">'.$image.'</span>'.LF;
      }
          $show_user_config = false;
          // tasks
          $manager = $this->_environment->getTaskManager();
          $manager->resetLimits();
          $manager->setContextLimit($this->_environment->getCurrentContextID());
         $manager->setStatusLimit('REQUEST');
          $manager->select();
         $tasks = $manager->get();
         $task = $tasks->getFirst();
         $show_user_config = false;
         $count_new_accounts = 0;
         while($task){
            $mode = $task->getTitle();
            $task = $tasks->getNext();
            if ($mode == 'TASK_USER_REQUEST'){
               $count_new_accounts ++;
               $show_user_config = true;
            }
         }
      if ($context_user->isModerator() and $show_user_config){
         $image = '<img src="images/commsyicons/22x22/config_account.png" style="vertical-align:bottom;" alt="'.getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK').'"/>';
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'index',
                                       '',
                                       $image,
                                       getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK')).LF;
      }else{
         $image = '<img src="images/commsyicons/22x22/config_account_grey.png" style="vertical-align:bottom;" alt="'.getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK').'"/>';
         $html .= '<a  title="'.getMessage('COMMON_NO_ACCESS').'" class="disabled">'.$image.'</a>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }*/



   function asHTML () {

      $environment = $this->getEnvironment();
      $context = $environment->getCurrentContextItem();
      if ($environment->inCommunityRoom()){
         $time_spread = 90;
      }else{
         $time_spread = $context->getTimeSpread();
      }
      $current_context = $this->_environment->getCurrentContextItem();

      $width = '';
      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width = 'width:250px;';
      }

      $html = '<div class="right_box">'.LF;
      $html .= '<div class="right_box_title" style="font-weight:bold;">'.getMessage('COMMON_ROOM_INFORMATION').'</div>';
      $html .= '<div class="right_box_main" style="'.$width.' font-size:10pt; padding-top:2px; padding-bottom:3px; padding-left:5px;">'.LF;


      $environment = $this->getEnvironment();
      $context = $environment->getCurrentContextItem();
      if ($environment->inCommunityRoom()){
         $time_spread = 90;
      }else{
         $time_spread = $context->getTimeSpread();
      }
      $html .= LF.'<!-- BEGIN OF ACTIVITY VIEW -->'.LF;
      $active = $context->getActiveMembers($time_spread);
      $all_users = $context->getAllUsers();
      $percentage = round($active / $all_users * 100);
      $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
      $html .= '         <tr>'.LF;
      $html .= '         <td style="width:175px;">'.LF;
      $html .= '      <div class="infocolor">'.$this->_translator->getMessage('ACTIVITY_ACTIVE_MEMBERS_DESC',$time_spread).':</div>'.LF;
      $html .= '         </td>'.LF;
      $html .= '         <td style="width:60px;">'.LF;
      $html .= '         <div class="gauge" style="margin:0px; float:right; width:65px;">'.LF;
      if ( $percentage >= 5 ) {
         $html .= '            <div class="gauge-bar" style="width:'.$percentage.'%;">'.$active.'</div>'.LF;
      } else {
         $html .= '<div class="gauge-bar" style="float:left; width:'.$percentage.'%;">&nbsp;</div><div style="padding-left:5px;">'.$active.'</div>'.LF;
      }
      $html .= '         </div>'.LF;
      $html .= '         </td>'.LF;
      $html .= '         </tr>'.LF;
      $html .= '         </table>'.LF;

      $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
      $html .= '         <tr>'.LF;
      $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
      $count_total = $context->getNewEntries($time_spread);
      $html .= $this->_translator->getMessage('HOME_ACTIVITY_NEW_ENTRIES').': ';
      $html .= '         </td>'.LF;
      $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.$count_total.LF;
      $html .= '         </td>'.LF;
      $html .= '         </tr>'.LF;
      $html .= '         <tr>'.LF;
      $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
      $count_total = $context->getPageImpressions($time_spread);
      $html .= $this->_translator->getMessage('HOME_ACTIVITY_PAGE_IMPRESSIONS').': ';
      $html .= '         </td>'.LF;
      $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.$count_total.LF;
      $html .= '         </td>'.LF;
      $html .= '         </tr>'.LF;
      $html .= '         </table>'.LF;



     if (
         ( $current_context->showWikiLink() and $current_context->existWiki() and $current_context->issetWikiHomeLink() )
         or ( $current_context->showChatLink() )
         or ( $current_context->showHomepageLink() )
         ){

      $html .='<div class="listinfoborder">'.LF;
      $html .='</div>'.LF;
      $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
      $html .= '         <tr>'.LF;
      $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
      $html .= $this->_translator->getMessage('HOME_EXTRA_TOOLS').': ';
      $html .= '         </td>'.LF;
      $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
      $current_context = $this->_environment->getCurrentContextItem();
      if ( $current_context->showWikiLink() and $current_context->existWiki() and $current_context->issetWikiHomeLink() ) {
         global $c_pmwiki_path_url;
         $image = '<img src="images/commsyicons/22x22/pmwiki.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_WIKI_LINK').'"/>';
         $title = $this->_translator->getMessage('COMMON_WIKI_LINK').': '.$current_context->getWikiTitle();
         $url_session_id = '';
         if ( $current_context->withWikiUseCommSyLogin() ) {
            $session_item = $this->_environment->getSessionItem();
            $url_session_id = '?commsy_session_id='.$session_item->getSessionID();
            unset($session_item);
         }
         $html .= ' '.'<a title="'.$title.'" href="'.$c_pmwiki_path_url.'/wikis/'.$current_context->getContextID().'/'.$current_context->getItemID().'/'.$url_session_id.'" target="_blank">'.$image.'</a>'.LF;
      }
      if ( $current_context->showHomepageLink() ) {
         $image = '<img src="images/commsyicons/22x22/commsy_homepage.png" style="vertical-align:bottom;" alt="'.getMessage('HOMEPAGE_HOMEPAGE').'"/>';
         $title = $this->_translator->getMessage('HOMEPAGE_HOMEPAGE');
         $html .=  ' '.ahref_curl($this->_environment->getCurrentContextID(),
                             'context',
                             'forward',
                             array('tool' => 'homepage'),
                             $image,
                             $this->_translator->getMessage('HOMEPAGE_HOMEPAGE'),
                             'chat',
                             '',
                             '',
                             'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=970, height=600\');"');
      }
      if ( $current_context->showChatLink() ) {
         global $c_etchat_enable;
         if ( !empty($c_etchat_enable)
              and $c_etchat_enable
            ) {
            $current_user = $this->_environment->getCurrentUserItem();
            if ( isset($current_user) and $current_user->isReallyGuest() ) {
               $image = '<img src="images/commsyicons/22x22/etchat_grey.png" style="vertical-align:bottom;" alt="'.getMessage('CHAT_CHAT').'"/>';
               $html = ' '.$image;
               // TBD: icon ausgrauen
            } else {
               $image = '<img src="images/commsyicons/22x22/etchat.png" style="vertical-align:bottom;" alt="'.getMessage('CHAT_CHAT').'"/>';
               $html .=  ' '.ahref_curl($this->_environment->getCurrentContextID(),
                                   'context',
                                   'forward',
                                   array('tool' => 'etchat'),
                                   $image,
                                   '',
                                   'chat',
                                   '',
                                   '',
                                   'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=970, height=600\');"');
            }
         }
      }
      $html .= '         </td>'.LF;
      $html .= '         </tr>'.LF;
      $html .= '         </table>'.LF;
      }
      $context_user = $this->_environment->getCurrentUserItem();
      if ( $context_user->isModerator()
           and !$context_user->isOnlyReadUser()
         ) {
         $html .='<div class="listinfoborder">'.LF;
         $html .='</div>'.LF;
         $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
         $html .= '         <tr>'.LF;
         $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
         $html .= $this->_translator->getMessage('COMMON_PAGETITLE_CONFIGURATION').': ';
         $html .= '         </td>'.LF;
         $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
         $image = '<img src="images/commsyicons/22x22/config.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_CONFIGURATION').'"/>';
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'index',
                                       '',
                                       $image,
                                       getMessage('COMMON_CONFIGURATION')).LF;
         $show_user_config = false;
         // tasks
         $manager = $this->_environment->getTaskManager();
         $manager->resetLimits();
         $manager->setContextLimit($this->_environment->getCurrentContextID());
         $manager->setStatusLimit('REQUEST');
         $manager->select();
         $tasks = $manager->get();
         $task = $tasks->getFirst();
         $show_user_config = false;
         $count_new_accounts = 0;
         while($task){
            $mode = $task->getTitle();
            $task = $tasks->getNext();
            if ($mode == 'TASK_USER_REQUEST'){
               $count_new_accounts ++;
               $show_user_config = true;
            }
         }
         if ($show_user_config){
            $params = array();
            $params['selstatus']='1';
            if ($count_new_accounts < 16){
               $image = '<img src="images/commsyicons/22x22/config/account_'.$count_new_accounts.'.png" style="vertical-align:bottom;" alt="'.getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK',$count_new_accounts).'"/>';
            }else{
               $image = '<img src="images/commsyicons/22x22/config/account_16.png" style="vertical-align:bottom;" alt="'.getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK',$count_new_accounts).'"/>';
            }
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'account',
                                       'index',
                                       $params,
                                       $image,
                                       getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK',$count_new_accounts)).LF;
         }else{
            $image = '<img src="images/commsyicons/22x22/config_account.png" style="vertical-align:bottom;" alt="'.getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK').'"/>';
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'account',
                                       'index',
                                       '',
                                       $image,
                                       getMessage('ACCOUNT_INDEX')).LF;
         }
         $html .= '         </td>'.LF;
         $html .= '         </tr>'.LF;
         $html .= '         </table>'.LF;
      }



      $html .= '         </div>'.LF;
      $html .='<div style="clear:both;">'.LF;

      $html .='</div>'.LF;
      $html .= '         </div>'.LF;
      $html .= '<!-- END OF ACTIVITY VIEW -->';
      return $html;
   }


}
?>