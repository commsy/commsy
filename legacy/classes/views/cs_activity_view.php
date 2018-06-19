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
      cs_view::__construct($params);
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
      $html .= '<div class="right_box_title" style="font-weight:bold;">'.$this->_translator->getMessage('COMMON_ROOM_INFORMATION').'</div>';
      $html .= '<div class="right_box_main" style="'.$width.' font-size:10pt; padding-top:2px; padding-bottom:3px; padding-left:5px;">'.LF;


      $environment = $this->getEnvironment();
      $context = $environment->getCurrentContextItem();
      $time_spread = $context->getTimeSpread();
      $html .= LF.'<!-- BEGIN OF ACTIVITY VIEW -->'.LF;
      $active = $context->getActiveMembers($time_spread);
      $all_users = $context->getAllUsers();
      $percentage = round($active / $all_users * 100);
      $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
      $html .= '         <tr>'.LF;
      $html .= '         <td style="width:175px;">'.LF;
      $html .= '      <div id="room_information_activity_description" class="infocolor">'.$this->_translator->getMessage('ACTIVITY_ACTIVE_MEMBERS_DESC',$time_spread).':</div>'.LF;
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
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/pmwiki.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_WIKI_LINK').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/pmwiki.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_WIKI_LINK').'"/>';
         }
         $title = $this->_translator->getMessage('COMMON_WIKI_LINK').': '.$current_context->getWikiTitle();
         $url_session_id = '';
         if ( $current_context->withWikiUseCommSyLogin() ) {
            $session_item = $this->_environment->getSessionItem();
            $url_session_id = '?commsy_session_id='.$session_item->getSessionID();
            unset($session_item);
         }
         $html .= ' '.'<a title="'.$title.'" href="'.$c_pmwiki_path_url.'/wikis/'.$current_context->getContextID().'/'.$current_context->getItemID().'/'.$url_session_id.'" target="_blank">'.$image.'</a>'.LF;
      }
      if ( $current_context->showWordpressLink() and $current_context->existWordpress() and $current_context->issetWordpressHomeLink() ) {
      	$current_portal_item = $this->_environment->getCurrentPortalItem();
         $wordpress_path_url = $current_portal_item->getWordpressUrl();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/wordpress.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_WORDPRESS_LINK').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/wordpress.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_WORDPRESS_LINK').'"/>';
         }
         $title = $this->_translator->getMessage('COMMON_WORDPRESS_LINK').': '.$current_context->getWordpressTitle();
         $url_session_id = '';
         if ( $current_context->withWordpressUseCommSyLogin() ) {
            $session_item = $this->_environment->getSessionItem();
            $url_session_id = '?commsy_session_id='.$session_item->getSessionID();
            unset($session_item);
         }
         $html .= ' '.'<a title="'.$title.'" href="'.$wordpress_path_url.'/'.$current_context->getContextID().'_'.$current_context->getItemID().'/'.$url_session_id.'" target="_blank">'.$image.'</a>'.LF;
      }
      if ( $current_context->showHomepageLink() ) {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/commsy_homepage.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('HOMEPAGE_HOMEPAGE').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/commsy_homepage.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('HOMEPAGE_HOMEPAGE').'"/>';
         }
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
               if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                  $image = '<img src="images/commsyicons_msie6/22x22/etchat_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CHAT').'"/>';
               } else {
                  $image = '<img src="images/commsyicons/22x22/etchat_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CHAT').'"/>';
               }
               $html .= ' '.$image;
               // TBD: icon ausgrauen
            } else {
               if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                  $image = '<img src="images/commsyicons_msie6/22x22/etchat.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CHAT').'"/>';
               } else {
                  $image = '<img src="images/commsyicons/22x22/etchat.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CHAT').'"/>';
               }
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
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/config.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/config.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION').'"/>';
         }
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'index',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_CONFIGURATION')).LF;
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
               if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                  $image = '<img src="images/commsyicons_msie6/22x22/config/account_'.$count_new_accounts.'.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK',$count_new_accounts).'"/>';
               } else {
                  $image = '<img src="images/commsyicons/22x22/config/account_'.$count_new_accounts.'.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK',$count_new_accounts).'"/>';
               }
            }else{
               if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                  $image = '<img src="images/commsyicons_msie6/22x22/config/account_16.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK',$count_new_accounts).'"/>';
               } else {
                  $image = '<img src="images/commsyicons/22x22/config/account_16.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK',$count_new_accounts).'"/>';
               }
            }
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'account',
                                       'index',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK',$count_new_accounts)).LF;
         }else{
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/config_account.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/config_account.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('HOME_LOGIN_NEW_ACCOUNT_LINK').'"/>';
            }
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'account',
                                       'index',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('ACCOUNT_INDEX')).LF;
         }
         $html .= '         </td>'.LF;
         $html .= '         </tr>'.LF;
         $html .= '         </table>'.LF;
      }

/*      $conf = $context->getHomeConf();
      if ( !empty($conf) ) {
         $rubrics = explode(',', $conf);
      } else {
         $rubrics = array();
      }
      $current_user = $this->_environment->getCurrentUserItem();
      $html .='<div class="listinfoborder">'.LF;
      $html .='</div>'.LF;
      $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
      $html .= '         <tr>'.LF;
      $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
      $html .= $this->_translator->getMessage('HOME_NEW_ENTRY').': ';
      $html .= '         </td>'.LF;
      $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
      foreach ( $rubrics as $rubric ) {
         $rubric_array = explode('_', $rubric);
         if ($rubric_array[1] != 'none'){
            $action_params = array();
            $action_params['id'] = 'NEW';
            $image_text = $this->_translator->getMessage('COMMON_ENTRY_NEW');
            switch ($rubric_array[0]){
               case CS_DATE_TYPE:
                  $image_text = $this->_translator->getMessage('COMMON_ENTER_NEW_DATE');
                 break;
                 case CS_ANNOUNCEMENT_TYPE:
                    $image_text = $this->_translator->getMessage('COMMON_ENTER_NEW_ANNOUNCEMENT');
                  break;
               case CS_MATERIAL_TYPE:
                  $image_text = $this->_translator->getMessage('COMMON_ENTER_NEW_MATERIAL');
                  break;
               case CS_TODO_TYPE:
                  $image_text = $this->_translator->getMessage('COMMON_ENTER_NEW_TODO');
                  break;
               case CS_DISCUSSION_TYPE:
                  $image_text = $this->_translator->getMessage('COMMON_ENTER_NEW_DISCUSSION');
                  break;
               case CS_TOPIC_TYPE:
                  $image_text = $this->_translator->getMessage('COMMON_ENTER_NEW_TOPIC');
                  break;
               case CS_GROUP_TYPE:
                  $image_text = $this->_translator->getMessage('COMMON_ENTER_NEW_GROUP');
                  break;
               case CS_INSTITUTION_TYPE:
                  $image_text = $this->_translator->getMessage('COMMON_ENTER_NEW_INSTITUTION');
                  break;

            }
            if ($rubric_array[0] != CS_USER_TYPE and $rubric_array[0] != CS_PROJECT_TYPE and $current_user->isUser()){
               $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                                $image_text,
                                                 'edit',
                                                 $action_params,
                                                 '<img src="images/commsyicons/22x22/'.$rubric_array[0].'.png" style="vertical-align:bottom; padding-right:3px;" alt="'.$image_text.'"/>',
                                                 $image_text);

            }elseif($rubric_array[0] != CS_USER_TYPE and $rubric_array[0] != CS_PROJECT_TYPE){
               $image = '<img title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$image_text).'" src="images/commsyicons/22x22/'.$rubric_array[0].'_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$image_text).'"/>';
               $html .= ' '.$image;
            }

         }
      }
      $html .= '         </td>'.LF;
      $html .= '         </tr>'.LF;
      $html .= '         </table>'.LF;*/



      $html .= '         </div>'.LF;
      $html .='<div style="clear:both;">'.LF;

      $html .='</div>'.LF;
      $html .= '         </div>'.LF;
      $html .= '<!-- END OF ACTIVITY VIEW -->';
      return $html;
   }


}
?>