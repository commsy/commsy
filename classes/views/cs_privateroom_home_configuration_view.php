<?php
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
include_once('functions/date_functions.php');
include_once('classes/cs_link.php');

/**
 *  generic upper class for CommSy homepage-views
 */
class cs_privateroom_home_configuration_view extends cs_view {

var  $_config_boxes = false;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_privateroom_home_configuration_view ($params) {
      $this->cs_view($params);
      $this->_view_title = $this->_translator->getMessage('COMMON_CONFIGURATION');
      $this->setViewName('configuration');
   }

   function asHTML () {
     $current_context = $this->_environment->getCurrentContextItem();
     $html  = '';
     if (
         ( $current_context->showWikiLink() and $current_context->existWiki() and $current_context->issetWikiHomeLink() )
         or ( $current_context->showChatLink() )
         or ( $current_context->showHomepageLink() )
         ){

      $html .='<div id="'.get_class($this).'" class="listinfoborder">'.LF;
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
         $html .='<div id="cs_privateroom_home_configuration_view" class="listinfoborder">'.LF;
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
     return $html;
   }
}
?>