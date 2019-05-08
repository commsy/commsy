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

$this->includeClass(PAGE_VIEW);

/** language_functions are needed for language specific display
 */
include_once('functions/language_functions.php');

/** curl_functions are needed for actions
 */
include_once('functions/curl_functions.php');

/** date_functions are needed for language specific display
 */
include_once('functions/date_functions.php');

/** misc_functions are needed for display the commsy version
 */
include_once('functions/misc_functions.php');
include_once('functions/text_functions.php');

/** class for a page view of commsy
 * this class implements a page view of commsy
 */
class cs_page_room_view extends cs_page_view {

   /**
    * string - containing the parameter of the page
    */
   var $_current_parameter = '';

   var $_form_tags =false;

   var $_form_action= '';

   var $_with_delete_box = false;

   var $_delete_box_action_url = '';

   var $_delete_box_mode = 'detail';

   var $_delete_box_ids = NULL;

   var $_delete_box_hidden_values = array();

   /**
    * array - containing the hyperlinks for the page
    */
   var $_links = array();

   var $_space_between_views=true;

   var $_blank_page = false;

   var $_blank_page_content ='';

   /**
    * boolean - containing the flag for displaying the CommSy header
    * standard = true
    */
   var $_with_commsy_header = true;

   /**
    * boolean - containing the flag for displaying a personal area for root (e.g. page commsy overview)
    * standard = false
    */
   var $_with_root_personal_area = false;

   /**
    * boolean - containing the flag for displaying a navigation bar for root (e.g. page commsy overview)
    * standard = false
    */
   var $_with_root_navigation_links = false;


   var $_bold_rubric = '';

   var $_shown_as_printable = false;

   var $_style_image_path = 'images/layout/';

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the context
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   public function __construct ($params) {
      cs_page_view::__construct($params);
      if (file_exists('htdocs/'.$this->_environment->getCurrentPortalID().'/commsy.css') ){
         $this->_style_image_path = $this->_environment->getCurrentPortalID().'/images/';
      }
   }

   function setBlankPage () {
      $this->_blank_page = true;
   }

   function setBlankPageContent ($content) {
      $this->_blank_page_content = $content;
   }

   function unsetBlankPage () {
      $this->_blank_page = false;
   }

   function setSpace () {
      $this->_space_between_views = true;
   }

   function unsetSpace () {
      $this->_space_between_views = false;
   }

   function setContextID ($value) {
      $this->_context_id = (int)($value);
   }

   function setBoldRubric($value){
      $this->_bold_rubric = $value;
   }

   function setPrintableView(){
      $this->_shown_as_printable = true;
   }
   function isPrintableView(){
      return $this->_shown_as_printable;
   }

   /** so page will be displayed without the CommSy header
    * this method skip a flag, so that the CommSy header will not be shown
    *
    * @author CommSy Development Group
    */
   function withoutCommSyHeader () {
      $this->_with_commsy_header = false;
   }

   /** so page will be displayed without the personal area
    */
   function setWithoutPersonalArea () {
      $this->_with_personal_area = false;
   }

   /** so page will be displayed with the personal area for root user
    */
   function setWithRootPersonalArea () {
      $this->_with_root_personal_area = true;
   }


   /** so page will be displayed with the navigation bar for root user
    */
   function setWithRootNavigationLinks () {
      $this->_with_root_navigation_links = true;
   }

   function addFormTags($action){
      $this->_form_tags = true;
      $this->_form_action = $action;
   }

   /** add an action to the page
    * this method adds an action (hyperlink) to the page view
    *
    * @param string  title        title of the action
    * @param string  explanantion explanation of the action
    * @param string  module       module of the action
    * @param string  function     function in module of the action
    * @param string  parameter    get parameter of the action
    *
    * @author CommSy Development Group
    */
   function addAction ($title, $explanation = '', $module = '', $function = '', $parameter = '') {
      $action['title'] = $title;
      $action['module'] = $module;
      $action['function'] = $function;
      $action['parameter'] = $parameter;
      $action['explanation'] = $explanation;
      $this->_links[] = $action;
   }


   /** get the linkbar as HTML
    * this method returns the linkbar as HTML - internal, do not use
    *
    * @return string linkbar as HTML
    */
   function _getLinkRowAsHTML ($bottom=false) {
      $html  = LF.'<!-- BEGIN TABS -->'.LF;
      $html .= '<div id="tabs_frame" >'.LF;
      if ($bottom){
         $html .= '<div class="tabs_bottom">'.LF;
      }else{
         $html .= '<div id="tabs">'.LF;
      }
      $html .= '<div style="float:right; margin:0px; padding:0px 12px;">'.LF;

     // configuration
     $context_user = $this->_environment->getCurrentUserItem();
      if ( $context_user->isModerator()
           and !$context_user->isOnlyReadUser()
         ) {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/config_home.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION').'"/>';
         } else {
            $image = '<img src="images/config_home.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_CONFIGURATION').'"/>';
         }
         $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'index',
                                       '',
                                       $image,
                                       $this->_translator->getMessage('COMMON_CONFIGURATION')).LF;
        }

        // Wiki, Chat

        $current_context = $this->_environment->getCurrentContextItem();
        if (
            ( $current_context->showWikiLink() and $current_context->existWiki() and $current_context->issetWikiHomeLink() )
            or ( $current_context->showChatLink() )
            ){

         $current_context = $this->_environment->getCurrentContextItem();
         if ( $current_context->showWikiLink() and $current_context->existWiki() and $current_context->issetWikiHomeLink() ) {
            global $c_pmwiki_path_url;
            $image = '<img src="images/pmwiki_home.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_WIKI_LINK').'"/>';
            $title = $this->_translator->getMessage('COMMON_WIKI_LINK').': '.$current_context->getWikiTitle();
            $url_session_id = '';
            if ( $current_context->withWikiUseCommSyLogin() ) {
               $session_item = $this->_environment->getSessionItem();
               $url_session_id = '?commsy_session_id='.$session_item->getSessionID();
               unset($session_item);
            }
            $html .= ' '.'<a title="'.$title.'" href="'.$c_pmwiki_path_url.'/wikis/'.$current_context->getContextID().'/'.$current_context->getItemID().'/'.$url_session_id.'" target="_blank">'.$image.'</a>'.LF;
         }
         if ( $current_context->showChatLink() ) {
            global $c_etchat_enable;
            if ( !empty($c_etchat_enable)
                 and $c_etchat_enable
               ) {
               $current_user = $this->_environment->getCurrentUserItem();
               if ( isset($current_user) and $current_user->isReallyGuest() ) {
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $image = '<img src="images/commsyicons_msie6/etchat_grey_home.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CHAT').'" title="'.$this->_translator->getMessage('CHAT_CHAT').'"/>';
                  } else {
                     $image = '<img src="images/etchat_grey_home.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CHAT').'" title="'.$this->_translator->getMessage('CHAT_CHAT').'"/>';
                  }
                  $html .= ' '.$image;
                  // TBD: icon ausgrauen
               } else {
                  if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                     $image = '<img src="images/commsyicons_msie6/etchat_home.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CHAT').'" title="'.$this->_translator->getMessage('CHAT_CHAT').'"/>';
                  } else {
                     $image = '<img src="images/etchat_home.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('CHAT_CHAT').'" title="'.$this->_translator->getMessage('CHAT_CHAT').'"/>';
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
                                      'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=970, height=600\');"').LF;
               }
            }
         }
      }


      // Wordpress
      $current_context = $this->_environment->getCurrentContextItem();
        if (
            ( $current_context->showWordpressLink() and $current_context->existWordpress() and $current_context->issetWordpressHomeLink() )
            or ( $current_context->showChatLink() )
            ){

         $current_context = $this->_environment->getCurrentContextItem();
         if ( $current_context->showWordpressLink() and $current_context->existWordpress() and $current_context->issetWordpressHomeLink() ) {
            $wordpress_path_url = $context_item->getWordpressUrl();
         	if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/wordpress_home.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_WORDPRESS_LINK').'"/>';
            } else {
               $image = '<img src="images/wordpress_home.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_WORDPRESS_LINK').'"/>';
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
      }


      // plugins for moderators an users
      $html .= plugin_hook_output_all('getExtraActionAsHTML',array(),LF).LF;

      // rss link
      $current_context_item = $this->_environment->getCurrentContextItem();
      $current_user_item = $this->_environment->getCurrentUserItem();
      $show_rss_link = false;
      if ( $current_context_item->isLocked()
           or $current_context_item->isClosed()
         ) {
         // do nothing
      } elseif ( $current_context_item->isOpenForGuests() ) {
         $show_rss_link =  true;
      } elseif ( $current_user_item->isUser() ) {
         $show_rss_link =  true;
      }
      $hash_string = '';
      if ( !$current_context_item->isOpenForGuests()
           and $current_user_item->isUser()
         ) {
         $hash_manager = $this->_environment->getHashManager();
         $hash_string = '&amp;hid='.$hash_manager->getRSSHashForUser($current_user_item->getItemID());
      }
      if (!$current_context_item->isRSSOn()){
         $show_rss_link =  false;
      }
      if ( $show_rss_link ) {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $html .= '<a href="rss.php?cid='.$current_context_item->getItemID().$hash_string.'" target="_blank"><img src="images/rss.gif" style="vertical-align:bottom;" alt="' . $this->_translator->getMessage('RSS_SUBSCRIBE_LINK') . '" title="' . $this->_translator->getMessage('RSS_SUBSCRIBE_LINK') . '"/></a>';
         } else {
            $html .= '<a href="rss.php?cid='.$current_context_item->getItemID().$hash_string.'" target="_blank"><img src="images/rss.png" style="vertical-align:bottom;" alt="' . $this->_translator->getMessage('RSS_SUBSCRIBE_LINK') . '" title="' . $this->_translator->getMessage('RSS_SUBSCRIBE_LINK') . '"/></a>';
         }
      }

      // my profile(if user rubric is not active)
      $available_rubrics = $current_context_item->getAvailableRubrics();
      if(!in_array('user', $available_rubrics)) {
         // user rubric is not active, so add link in tablist
         if(!$current_context_item->isOpenForGuests() && $current_user_item->isUser() && $this->_with_modifying_actions) {
            $params = array();
            $params['iid'] = $current_user_item->getItemID();
            $image = '<img src="images/user.png" style="vertical-align:bottom; padding-left: 2px;" alt="'.$this->_translator->getMessage('USER_OWN_INFORMATION').'"/>';
            $html .= ahref_curl(   $current_context_item->getItemID(),
                                   CS_USER_TYPE,
                                   'detail',
                                   $params,
                                   $image,
                                   $this->_translator->getMessage('USER_OWN_INFORMATION')).LF;
            unset($params);
            //$html .= '<a href="commsy.php?cid=' . $current_context_item->getItemID() . '&mod=user&fct=detail&iid=' . $current_user_item->getItemID() . '">keks</a>';
         }
      }

      $html .= '</div>'.LF;
      $html .= '<div id="tablist">'.LF;
      $session = $this->_environment->getSession();
      $history = $session->getValue('history');
      if ( isset($history) and isset($history[1]) and !empty($history[1]) ) {
         $h_module = $history[1]['module'];
      } else {
         $h_module ='';
      }
      $first = true;
      $with_icons = false;
      foreach ( $this->_links as $link ) {
         $link_title = '';
         if ($current_context_item->isPrivateRoom()){
            switch ($link['module']) {
               case 'topic':
                  if ( $with_icons ) {
                     if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                        $link_title .= '<img src="images/commsyicons_msie6/16x16/topic.gif" style="vertical-align:bottom;"/>';
                     } else {
                        $link_title .= '<img src="images/commsyicons/16x16/topic.png" style="vertical-align:bottom;"/>';
                     }
                  }
                  $link_title .= $link['title'];
                  break;
               case 'material':
                  if ( $with_icons ) {
                     if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                        $link_title .= '<img src="images/commsyicons_msie6/16x16/material.gif" style="vertical-align:bottom;"/>';
                     } else {
                        $link_title .= '<img src="images/commsyicons/16x16/material.png" style="vertical-align:bottom;"/>';
                     }
                  }
                  $link_title .= $link['title'];
                  break;
               case 'date':
                  if ( $with_icons ) {
                     if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                        $link_title .= '<img src="images/commsyicons_msie6/16x16/date.gif" style="vertical-align:bottom;"/>';
                     } else {
                        $link_title .= '<img src="images/commsyicons/16x16/date.png" style="vertical-align:bottom;"/>';
                     }
                  }
                  $link_title .= $link['title'];
                  break;
               case 'myroom':
                  if ( $with_icons ) {
                     if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                        $link_title .= '<img src="images/commsyicons_msie6/16x16/room.gif" style="vertical-align:bottom;"/>';
                     } else {
                        $link_title .= '<img src="images/commsyicons/16x16/room.png" style="vertical-align:bottom;"/>';
                     }
                  }
                  $link_title .= $this->_translator->getMessage('PRIVATEROOMS');
                  break;
               case 'todo':
                  if ( $with_icons ) {
                     if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                        $link_title .= '<img src="images/commsyicons_msie6/16x16/todo.gif" style="vertical-align:bottom;"/>';
                     } else {
                        $link_title .= '<img src="images/commsyicons/16x16/todo.png" style="vertical-align:bottom;"/>';
                     }
                  }
                  $link_title .= $link['title'];
                  break;
               default:
                  $link_title = '';
                  $text = '';
                  if ( $this->_environment->isPlugin($link['module']) ) {
                     if ( $with_icons ) {
                        $icon_plugin = plugin_hook_output($link['module'],'getRubricNavIcon');
                        if ( !empty($icon_plugin) ) {
                           $text .= '<img src="'.$icon_plugin.'" style="vertical-align:bottom;"/>';
                        }
                     }
                     $text .= plugin_hook_output($link['module'],'getDisplayName');
                  }
                  if ( !empty($text) ) {
                     $link_title .= $text;
                  } else {
                     $link_title .= $link['title'];
                  }
                  break;
            }
         } else {
            if ( $with_icons ) {
               if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                  switch ($link['module']) {
                     case 'user':
                        $link_title = '<img src="images/commsyicons_msie6/16x16/user.gif" style="vertical-align:bottom;"/>';
                        break;
                     case 'discussion':
                        $link_title = '<img src="images/commsyicons_msie6/16x16/discussion.gif" style="vertical-align:bottom;"/>';
                        break;
                     case 'material':
                        $link_title = '<img src="images/commsyicons_msie6/16x16/material.gif" style="vertical-align:bottom;"/>';
                        break;
                     case 'date':
                        $link_title = '<img src="images/commsyicons_msie6/16x16/date.gif" style="vertical-align:bottom;"/>';
                        break;
                     case 'announcement':
                        $link_title = '<img src="images/commsyicons_msie6/16x16/announcement.gif" style="vertical-align:bottom;"/>';
                        break;
                     case 'group':
                        $link_title = '<img src="images/commsyicons_msie6/16x16/group.gif" style="vertical-align:bottom;"/>';
                        break;
                     case 'institution':
                        $link_title = '<img src="images/commsyicons_msie6/16x16/group.gif" style="vertical-align:bottom;"/>';
                        break;
                     case 'todo':
                        $link_title = '<img src="images/commsyicons_msie6/16x16/todo.gif" style="vertical-align:bottom;"/>';
                        break;
                     case 'topic':
                        $link_title = '<img src="images/commsyicons_msie6/16x16/topic.gif" style="vertical-align:bottom;"/>';
                        break;
                     case 'project':
                        $link_title = '<img src="images/commsyicons_msie6/16x16/room.gif" style="vertical-align:bottom;"/>';
                        break;
                     default:
                        $link_title = '';
                  }
               } else {
                  switch ($link['module']) {
                     case 'user':
                        $link_title = '<img src="images/commsyicons/16x16/user.png" style="vertical-align:bottom;"/>';
                        break;
                     case 'discussion':
                        $link_title = '<img src="images/commsyicons/16x16/discussion.png" style="vertical-align:bottom;"/>';
                        break;
                     case 'material':
                        $link_title = '<img src="images/commsyicons/16x16/material.png" style="vertical-align:bottom;"/>';
                        break;
                     case 'date':
                        $link_title = '<img src="images/commsyicons/16x16/date.png" style="vertical-align:bottom;"/>';
                        break;
                     case 'announcement':
                        $link_title = '<img src="images/commsyicons/16x16/announcement.png" style="vertical-align:bottom;"/>';
                        break;
                     case 'group':
                        $link_title = '<img src="images/commsyicons/16x16/group.png" style="vertical-align:bottom;"/>';
                        break;
                     case 'institution':
                        $link_title = '<img src="images/commsyicons/16x16/group.png" style="vertical-align:bottom;"/>';
                        break;
                     case 'todo':
                        $link_title = '<img src="images/commsyicons/16x16/todo.png" style="vertical-align:bottom;"/>';
                        break;
                     case 'topic':
                        $link_title = '<img src="images/commsyicons/16x16/topic.png" style="vertical-align:bottom;"/>';
                        break;
                     case 'project':
                        $link_title = '<img src="images/commsyicons/16x16/room.png" style="vertical-align:bottom;"/>';
                        break;
                     default:
                        $link_title = '';
                  }
               }
            }
            $link_title .= $link['title'];
         }

         if ($this->_environment->inPrivateRoom() and ($link['module'] == 'date' or $link['module'] == 'todo') ){
            $link_title = '<img src="images/commsyicons/16x16/date.png" style="vertical-align:bottom;"/>';
            $link_title = $this->_translator->getMessage('MYCALENDAR_INDEX');
         }
         if ($first){
            $first = false;
         }
         if( $this->_module == 'buzzwords' ){
            // Get linked rubric
            if ( isset($_GET['module']) and !empty($_GET['module']) ) {
               $linked_rubric = $_GET['module'];
               $session->setValue($this->_environment->getCurrentModule().'_linked_rubric',$linked_rubric);
            } elseif ( $session->issetValue($this->_environment->getCurrentModule().'_linked_rubric') ) {
               $linked_rubric = $session->getValue($this->_environment->getCurrentModule().'_linked_rubric');
            } else {
               $linked_rubric = '';
            }
         }
         if( $this->_module == 'tag' ){
            // Get linked rubric
            if ( isset($_GET['module']) and !empty($_GET['module']) ) {
               $linked_rubric = $_GET['module'];
               $session->setValue($this->_environment->getCurrentModule().'_linked_rubric',$linked_rubric);
            } elseif ( $session->issetValue($this->_environment->getCurrentModule().'_linked_rubric') ) {
               $linked_rubric = $session->getValue($this->_environment->getCurrentModule().'_linked_rubric');
            } else {
               $linked_rubric = '';
            }
         }

         $current_context_id = $this->_environment->getCurrentContextID();
         $current_portal_id = $this->_environment->getCurrentPortalID();

         if ( $current_context_item->isOpenForGuests() or $this->_current_user->isUser() ) {
            if ( $this->_module == $link['module']
                 or ($this->_module == 'discarticle' and $link['module'] == 'discussion')
                 or ($this->_module == 'material' and $link['module'] == 'search')
                 or ($this->_module == 'section' and $link['module'] == 'material')
                 or ($this->_module == 'version' and $link['module'] == 'material')
                 or ($this->_module == 'buzzwords' and $link['module'] == $linked_rubric)
                 or ($this->_module == 'tag' and $link['module'] == $linked_rubric)
                 or ($this->_module == 'labels' and $link['module'] == 'material')
                 or ($this->_module == 'version_material' and $link['module'] == 'material')
                 or ($this->_module == 'version_archive' and $link['module'] == 'search')
                 or ($this->_module == 'clipboard' and $link['module'] == 'material')
                 or ($this->_module == 'auth' and $link['module'] == 'contact')
                 or ($this->_module == 'user' and $link['module'] == 'contact')
                 or ($this->_module == 'rubric' and $this->_function =='mail' and $link['module'] == $h_module )
                 or ( $this->_module == 'todo'
                      and $link['module'] == 'date'
                      and $this->_environment->inPrivateRoom()
                    )
                 or ( $this->_module == 'discussion'
                      and $link['module'] == 'entry'
                      and $this->_environment->inPrivateRoom()
                    )
                 or ( $this->_module == 'material'
                      and $link['module'] == 'entry'
                      and $this->_environment->inPrivateRoom()
                    )
                 or ( $this->_module == 'announcement'
                      and $link['module'] == 'entry'
                      and $this->_environment->inPrivateRoom()
                    )
                 or ( $this->_module == 'topic'
                      and $link['module'] == 'entry'
                      and $this->_environment->inPrivateRoom()
                    )
            ) {
               $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist_current"');
            } elseif ( $this->_module =='annotation' ) {
               if ( isset($history) and isset($history[1]) and !empty($history[1]) ){
                  $h_module = $history[1]['module'];
               } else {
                  $h_module ='';
               }
               if ( $session->issetValue('annotation_history_module') ){
                  $h_module = $session->getValue('annotation_history_module');
               }
               if ($link['module']== $h_module) {
                  $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist_current"');
               } else {
                  $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist"');
               }
            } elseif ( $this->_module == 'context' and $this->_function =='info_text_edit' ) {
               if ( isset($history) and isset($history[0]) and !empty($history[0]) ) {
                  $h_module = $history[0]['module'];
               } else {
                  $h_module ='';
               }
               if ( $link['module'] == $h_module ) {
                  $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist_current"');
               } else {
                  $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist"');
               }
            } else {
               $ahref = ahref_curl($this->_environment->getCurrentContextID(), $link['module'], $link['function'], $link['parameter'], $link_title, $link['explanation'],'','','','','','class="navlist"');
            }
            if (($link['module']!='todo' or $current_context_item->withRubric(CS_TODO_TYPE)) ){
               $html .= $ahref;
            }
         }else {
            $html .= '     <span >'.$link_title.'</span>'.LF;
         }
      }
//      CommSy 7 -> '+'-Link in Rubrileiste fuer Moderatoren
//      if($current_user_item->isModerator()){
//         $html .= ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'rubric_options', '', '+', '','','','','','','class="navlist"');
//      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<!-- END TABS -->'.LF;
      unset($current_user_item);
      unset($current_context_item);
      return $html;
   }


   function _getBlankLinkRowAsHTML () {
      $html  = LF.'<!-- BEGIN TABS -->'.LF;
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      unset($session);
      $html .= '<div id="tabs_frame" >'.LF;
      $html .= '<div class="tabs">'.LF;
      if ( $this->_environment->getCurrentModule() == 'agb'
           and $this->_environment->getCurrentFunction() == 'index'
         ) {
         $html .= '<div style="float:right; margin:0px; padding: 0px 2px 0px 0px;">'.LF;
      } else {
         $html .= '<div style="float:right; margin:0px; padding:0px;">'.LF;
      }

      // rss link
      $current_context_item = $this->_environment->getCurrentContextItem();
      $current_user_item = $this->_environment->getCurrentUserItem();
      $show_rss_link = false;
      if ( $current_context_item->isLocked() ) {
         // do nothing
      } elseif ( $current_context_item->isOpenForGuests() ) {
         $show_rss_link =  true;
      } elseif ( $current_user_item->isUser() ) {
         $show_rss_link =  true;
      }
      $hash_string = '';
      if ( !$current_context_item->isOpenForGuests()
           and $current_user_item->isUser()
         ) {
         $hash_manager = $this->_environment->getHashManager();
         $hash_string = '&amp;hid='.$hash_manager->getRSSHashForUser($current_user_item->getItemID());
      }
      if ( $show_rss_link ) {
         $html .= '<a href="rss.php?cid='.$current_context_item->getItemID().$hash_string.'" target="_blank"><img src="images/rss.png" width="15" height="15" style="vertical-align:bottom;" alt="RSS-Feed dieses Raumes abonnieren"/></a>';
      }
      unset($current_user_item);
      unset($current_context_item);

      $html .= '</div>'.LF;
      $text = '&nbsp;';
      if ( $this->_environment->getCurrentModule() == 'agb'
           and $this->_environment->getCurrentFunction() == 'index'
         ) {
         $text .= $this->_translator->getMessage('AGB_CONFIRMATION');
         $html .= '<div style="margin:0px; padding: 2px 0px 2px 0px;">'.LF;
      } else {
         $html .= '<div style="margin:0px; padding:0px;">'.LF;
      }
      $html .= '<span class="navlist">'.$text.'</span>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '<!-- END TABS -->'.LF;
      return $html;
   }


   function _getLogoAsHTML(){
      $html  = '';
      $logo_filename = '';
      $context_item = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html .='<table summary="layout" style="padding:0px; border-collapse:collapse;">'.LF;
      $html .= '<tr>'.LF;
      if ( $this->_environment->inCommunityRoom()
           or $this->_environment->inProjectRoom()
           or $this->_environment->inPrivateRoom()
           or $this->_environment->inGroupRoom()
         ) {
         $logo_filename = $context_item->getLogoFilename();
         if ( !empty($logo_filename) ) {
            $params = array();
            $params['picture'] = $context_item->getLogoFilename();
            $curl = curl($this->_environment->getCurrentContextID(), 'picture', 'getfile', $params,'');
            unset($params);
            $html .= '<td style="padding:0px; height:55px; width:10%;">'.LF;
            $html .= '<div class="logo" style="vertical-align:top; padding:5px 5px 0px 0px; margin:0px;">'.LF;
            $html .= '     <img style="height:45px; padding: 0px; margin:0px;" src="'.$curl.'" alt="'.$this->_translator->getMessage('COMMON_LOGO').'" border="0"/>'.LF;
            $html .= '</div>'.LF;
            $html .= '</td>'.LF;
            $html .= '<td style="vertical-align:middle; padding: 5px 0px 0px 0px;">'.LF;
         }else{
            $html .= '<td colspan="2" style="height:55px; vertical-align:middle; padding:0px;">'.LF;

         }
      }else{
         $html .= '<td colspan="2" style="height:55px; border:1px solid green; vertical-align:bottom; padding:5px 0px 0px 0px;">'.LF;

      }
      $length = mb_strlen($context_item->getTitle());
      $title = $context_item->getTitle();
      if ($length < 25){
        $size = 'style="font-size:24pt; vertical-align:bottom; padding:5px 0px 0px 0px; margin:0px;"';
      }elseif($length < 30){
        $size = 'style="font-size:20pt;"';
      }elseif($length < 35){
        $size = 'style=" font-size:18pt;"';
      }elseif($length < 40){
        $size = 'style="font-size:16pt;"';
      }elseif($length < 50){
        $size = 'style="font-size:12pt;"';
      }else{
         $size = 'style="font-size:12pt;"';
         $title = chunkText($title,50);
      }
      if ($context_item->showTitle()){
         $html .= '<h1 '.$size.'>'.$this->_text_as_html_short($title).'</h1>'.LF;
      }else{
         $html .= '<h1 '.$size.'>'.'&nbsp;'.'</h1>'.LF;
      }
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
      $authentication = $this->_environment->getAuthenticationObject();
      $external_view = false;
      if (isset($_GET['iid'])){
         $current_user = $this->_environment->getCurrentUserItem();
         $external_view = $authentication->_isExternalUserAllowedToSee($current_user->getUserID(),$_GET['iid']);
      }

      if ( !isset($this->_with_navigation_links) or $this->_with_navigation_links or $external_view) {
         $html .= '<tr class="header_room_path">'.LF;
         $html .= '<td colspan="2" style="padding:0px; margin:0px; vertical-align:bottom;">'.LF;
         $breadcrump = '';
         $params = array();
         if ($current_user->isRoot()){
            $server_item = $this->_environment->getServerItem();
            $breadcrump.= ahref_curl($server_item->getItemID(),'home','index',$params,$this->_text_as_html_short($server_item->getTitle()),'','','','','','','style="color:#800000"');
            $breadcrump .= ' &gt; ';
         }
         $portal_item = $this->_environment->getCurrentPortalItem();

         if ($this->_environment->inProjectRoom() or $this->_environment->inCommunityRoom()){
            $params['room_id'] = $context_item->getItemID();
         }
         $breadcrump.= ahref_curl($portal_item->getItemID(),'home','index',$params,$this->_text_as_html_short($portal_item->getTitle()),'','','','','','','style="color:#800000"');
         if ($this->_environment->inProjectRoom()){
            $community_list = $context_item->getCommunityList();
            $community_item = $community_list->getFirst();
            if (!empty($community_item)){
               $breadcrump.= ' &gt; '.ahref_curl($community_item->getItemID(),'home','index','',$this->_text_as_html_short($community_item->getTitle()),'','','','','','','style="color:#800000"');
            }
            $breadcrump.= ' &gt; '.chunkText($this->_text_as_html_short($context_item->getTitle()),50);
         }elseif($this->_environment->inGroupRoom()){
            $project_item = $context_item->getLinkedProjectItem();
            $community_list = $project_item->getCommunityList();
            $community_item = $community_list->getFirst();
            if (!empty($community_item)){
                $breadcrump.= ' &gt; '.ahref_curl($community_item->getItemID(),'home','index','',$this->_text_as_html_short($community_item->getTitle()),'','','','','','','style="color:#800000"');
            }
            $breadcrump.= ' &gt; '.ahref_curl($project_item->getItemID(),'home','index','',$this->_text_as_html_short($project_item->getTitle()),'','','','','','','style="color:#800000"');
            $breadcrump.= ' &gt; '.chunkText($this->_text_as_html_short($context_item->getTitle()),50);
         }elseif($this->_environment->inCommunityRoom() or $this->_environment->inPrivateRoom()){
            $breadcrump.= ' &gt; '.chunkText($this->_text_as_html_short($context_item->getTitle()),50);
         }
         $html .= '<span style="height: 20px;font-size:8pt; font-weight:normal;">'.$breadcrump.'</span>'.BRLF;
         $html .= '</td>'.LF;
         $html .= '</tr>'.LF;
      }
      $html .= '</table>'.LF;
      return $html;
   }


   function asHTMLFirstPart () {
      $html ='';
      $session = $this->_environment->getSession();
      if (!empty($session)) {
         $session_id = $session->getSessionID();
      } else {
         $session_id = '';
      }
      unset($session);
      $html .= $this->_getHTMLHeadAsHTML();
      if ( !$this->_blank_page ) {
         $html .= '<body';
         $current_function = $this->_environment->getCurrentFunction();
         if ( ($this->_focus_onload and $current_function != 'index' and $this->_environment->getCurrentModule() != 'help') or ($this->_with_delete_box) ) {
            $html .= ' onload="';
            if ( $this->_focus_onload and $current_function != 'index' and $this->_environment->getCurrentModule() != 'help'){
               $html .=' window.focus();setfocus();';
            }
            if ($this->_with_delete_box){
               $html .= ' initDeleteLayer();';
            }
            if (isset($_GET['show_copies']) or isset($_GET['show_profile'])){
               $html .= ' initLayer("profile");';
            }
            if(isset($_GET['attach_view']) and isset($_GET['attach_type']) and $_GET['attach_type'] == 'buzzword'){
                $html .= ' initLayer("buzzword");';
            }
            if(isset($_GET['attach_view']) and isset($_GET['attach_type']) and $_GET['attach_type'] == 'item'){
                $html .= ' initLayer("item");';
            }
            $html .= ' "';
         }elseif(isset($_GET['show_copies']) or isset($_GET['show_profile'])){
            $html .= ' onload="';
            $html .= ' initLayer(\'profile\');';
            $html .= ' "';
         }elseif(isset($_GET['attach_view'])
                 and isset($_GET['attach_type'])
                 and $_GET['attach_type'] == 'buzzword'
                 and !isset($_POST['return_attach_buzzword_list'])
                 ){
            $html .= ' onload="';
            $html .= ' initLayer(\'buzzword\');';
            $html .= ' "';
         }elseif(isset($_GET['attach_view'])
                 and isset($_GET['attach_type'])
                 and $_GET['attach_type'] == 'tag'
                 and !isset($_POST['return_attach_tag_list'])
                 ){
            $html .= ' onload="';
            $html .= ' initLayer(\'tag\');';
            $html .= ' "';
         }elseif ( (isset($_GET['attach_view'])
              and ($_GET['attach_view'] == 'yes')
              and isset($_GET['attach_type'])
              and !empty($_GET['attach_type'])
              and $_GET['attach_type'] == 'item')
              or(
                 isset($_POST['right_box_option'])
                 and (
                      isOption($_POST['right_box_option'], $this->_translator->getMessage('COMMON_ITEM_NEW_ATTACH')) or
                      isOption($_POST['right_box_option'], $this->_translator->getMessage('COMMON_GROUP_ATTACH')) or
                      isOption($_POST['right_box_option'], $this->_translator->getMessage('COMMON_INSTITUTION_ATTACH'))
                 )
                 and (!isset($_POST['return_attach_tag_list']))
              )
            ) {
            $html .= ' onload="';
            $html .= ' initLayer(\'item_list\');';
            $html .= ' "';
            }
         $views = array_merge($this->_views, $this->_views_left, $this->_views_right, $this->_views_overlay);
         $view = reset($views);
         while ($view) {
            $html .= $view->getInfoForBodyAsHTML();
            $view = next($views);
         }
         unset($views);
         unset($view);
         $html .= ' class="body">'.LF;
         $html .= '<a id="top" name="top"></a>'.LF;

         $html_id = '';
         $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
         $current_browser_version = $this->_environment->getCurrentBrowserVersion();
         if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
            $html_id = 'id="ie"';
         }
         $style = '';
         if ( $this->_environment->getCurrentModule() == 'agb'
              and $this->_environment->getCurrentFunction() == 'index'
            ) {
            $style = ' width: 550px;';
         }
         $html .= '<div '.$html_id.' class="commsy_body" style="padding:0px; margin:0px;'.$style.'">'.LF;

      }

      $html .= '<div id="page_header">';
      $authentication = $this->_environment->getAuthenticationObject();
      $external_view = false;
      if (isset($_GET['iid'])){
         $current_user = $this->_environment->getCurrentUserItem();
         $external_view = $authentication->_isExternalUserAllowedToSee($current_user->getUserID(),$_GET['iid']);
      }
      if ( !isset($this->_with_navigation_links) or $this->_with_navigation_links or $external_view) {
         $html .= '<div class="page_header_personal_area">'.LF;
         $html .= '<div style="float:right;">'.LF;
         $html .= $this->getMyAreaAsHTML().LF;
         $html .= '</div>'.LF;
         $html .= '<div style="clear:both;">'.LF;
         $html .= '</div>'.LF;
         $current_user = $this->_environment->getCurrentUserItem();
         if ( empty($current_user)
              or !$current_user->isRoot()
            ) {
            $browser = $this->_environment->getCurrentBrowser();
            $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
            $current_browser_version = $this->_environment->getCurrentBrowserVersion();
            if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
               $html .='<div style="float:right; padding-top:22px; white-space:nowrap;">'.LF;
            }elseif ($current_browser == 'msie') {
               $html .='<div style="float:right; padding-top:22px; white-space:nowrap;">'.LF;
            }else{
               $html .='<div style="float:right; padding-top:28px; white-space:nowrap;">'.LF;
            }
            $html .= '<div style="float:right; vertical-align:bottom;">'.LF;
            $html .= '<table style="font-size:8pt; padding:0px; margin:0px; border-collapse:collapse;">'.LF;
            $html .= '<tr>'.LF;
            $html .= '<td style="vertical-align:middle;">'.LF;
            $html .= $this->_translator->getMessage('MYAREA_CHANGE_MY_ACTUAL_ROOMS').'&nbsp;'.LF;
            $html .= '</td>'.LF;
            $html .= '<td>'.LF;
            $html .= $this->_getUserPersonalAreaAsHTML().LF;
            $html .= '</td>'.LF;
            $html .= '</tr>'.LF;
            $html .= '</table>'.LF;
            $html .= '</div>'.LF;
            $html .= '</div>'.LF;
         }
         $html .= '<div style="clear:both;">'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF.LF;
      }
      $html .= '<div id="page_header_logo">'.LF;
      $html .= $this->_getLogoAsHTML().LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $this->_send_first_html_part = true;
      return $html;
   }


   function _getCustomizedRoomListForCurrentUser(){
      $retour = array();
      $current_user = $this->_environment->getCurrentUserItem();
      $current_context_id = $this->_environment->getCurrentContextID();
      $own_room_item = $current_user->getOwnRoom();
      $temp_array = array();
      $temp_array['title'] = '----------------------------';
      $temp_array['item_id'] = '-1';
      $retour[] = $temp_array;
      $customized_room_list = $own_room_item->getCustomizedRoomList();
      if ( isset($customized_room_list) ) {
         $room_item = $customized_room_list->getFirst();
         while ($room_item) {
            $temp_array = array();
            if ( $room_item->isGrouproom() ) {
               $temp_array['title'] = '- '.$room_item->getTitle();
            } else {
               $temp_array['title'] = $room_item->getTitle();
            }
#            if ( mb_strlen($temp_array['title']) > 40 ) {
#               $temp_array['title'] = mb_substr($temp_array['title'],0,40);
#               $temp_array['title'] .= '...';
#            }
            $temp_array['item_id'] = $room_item->getItemID();
            if ($current_context_id == $temp_array['item_id']){
               $temp_array['selected'] = true;
            }
            $retour[] = $temp_array;
            $room_item = $customized_room_list->getNext();
         }
      }
      return $retour;
   }


   function _getUserPersonalAreaAsHTML () {
      $retour  = '';
      $retour .= '   <form style="margin:0px; padding:0px;" method="post" action="'.curl($this->_environment->getCurrentContextID(),'room','change','').'" name="room_change">'.LF;
      // jQuery
      //$retour .= '         <select size="1" style="font-size:8pt; width:220px;" name="room_id" onChange="javascript:document.room_change.submit()">'.LF;
      $retour .= '         <select size="1" style="font-size:8pt; width:220px;" name="room_id" id="submit_form">'.LF;
      // jQuery
      $context_array = array();
      
      // archive
      $toggle_archive_mode = false;
      if ( $this->_environment->isArchiveMode() ) {
      	$this->_environment->deactivateArchiveMode();
      	$toggle_archive_mode = true;
      }
      
      $context_array = $this->_getAllOpenContextsForCurrentUser();
      
      // archive
      if ( $toggle_archive_mode ) {
      	$this->_environment->activateArchiveMode();
      	$temp_array[0]['item_id'] = -1; 
      	$temp_array[0]['title'] = ''; 
      	$temp_array[1]['item_id'] = -1; 
      	$temp_array[1]['title'] = strtoupper($this->_translator->getMessage('COMMON_CLOSED'));
      	$context_array_archive = $this->_getAllOpenContextsForCurrentUser();
      	$context_array = array_merge($context_array,$temp_array,$context_array_archive);
      	unset($context_array_archive);
      }
      unset($toggle_archive_mode);
      
      $current_portal = $this->_environment->getCurrentPortalItem();
      if ( !$this->_environment->inServer() ) {
         $title = $this->_environment->getCurrentPortalItem()->getTitle();
         $title .= ' ('.$this->_translator->getMessage('COMMON_PORTAL').')';
         $additional = '';
         if ($this->_environment->inPortal()){
            $additional = 'selected="selected"';
         }
         $retour .= '            <option value="'.$this->_environment->getCurrentPortalID().'" '.$additional.'>'.$title.'</option>'.LF;

         $current_portal_item = $this->_environment->getCurrentPortalItem();
         if ( $current_portal_item->showAllwaysPrivateRoomLink() ) {
            $link_active = true;
         } else {
            $current_user_item = $this->_environment->getCurrentUserItem();
            if ( $current_user_item->isRoomMember() ) {
               $link_active = true;
            } else {
               $link_active = false;
            }
            unset($current_user_item);
         }
         unset($current_portal_item);

         if ( $link_active ) {
            $retour .= '            <option value="-1" class="disabled" disabled="disabled">------------------------------------</option>'.LF;
            $additional = '';
            $user = $this->_environment->getCurrentUser();
            $private_room_manager = $this->_environment->getPrivateRoomManager();
            $own_room = $private_room_manager->getRelatedOwnRoomForUser($user,$this->_environment->getCurrentPortalID());
            if ( isset($own_room) ) {
               $own_cid = $own_room->getItemID();
               $additional = '';
               if ($own_room->getItemID() == $this->_environment->getCurrentContextID()) {
                  $additional = ' selected="selected"';
               }
               $retour .= '            <option value="'.$own_cid.'"'.$additional.'>'.$this->_translator->getMessage('COMMON_PRIVATEROOM').'</option>'.LF;
            }
            unset($own_room);
            unset($private_room_manager);
         }
      }

      $first_time = true;
      foreach ($context_array as $con) {
         $title = $this->_text_as_html_short($con['title']);
         $additional = '';
         if (isset($con['selected']) and $con['selected']) {
            $additional = ' selected="selected"';
         }
         if ($con['item_id'] == -1) {
            $additional = ' class="disabled" disabled="disabled"';
            if (!empty($con['title'])) {
               $title = '----'.$this->_text_as_html_short($con['title']).'----';
            } else {
               $title = '&nbsp;';
            }
         }
         if ($con['item_id'] == -2) {
            $additional = ' class="disabled" disabled="disabled" style="font-style:italic;"';
            if (!empty($con['title'])) {
               $title = $this->_text_as_html_short($con['title']);
            } else {
               $title = '&nbsp;';
            }
            $con['item_id'] = -1;
            if ($first_time) {
               $first_time = false;
            } else {
               $retour .= '            <option value="'.$con['item_id'].'"'.$additional.'>&nbsp;</option>'.LF;
            }
         }
         $retour .= '            <option value="'.$con['item_id'].'"'.$additional.'>'.$title.'</option>'.LF;
      }

      if (!$this->_current_user->isUser() and $this->_current_user->getUserID() != "guest") {
         $context = $this->_environment->getCurrentContextItem();
         if (!empty($context_array)) {
            $retour .= '            <option value="-1" class="disabled" disabled="disabled">&nbsp;</option>'.LF;
         }
         $retour .= '            <option value="-1" class="disabled" disabled="disabled">----'.$this->_translator->getMessage('MYAREA_CONTEXT_GUEST_IN').'----</option>'.LF;
         $retour .= '            <option value="'.$context->getItemID().'" selected="selected">'.$context->getTitle().'</option>'."\n";
      }
      $retour .= '         </select>'.LF;
      $retour .= '         <noscript><input type="submit" style="margin-top:3px; font-size:10pt; width:12.6em;" name="room_change" value="'.$this->_translator->getMessage('COMMON_GO_BUTTON').'"/></noscript>'.LF;
      $retour .= '   </form>'.LF;
      unset($context_array);
      return $retour;
   }


   function asHTMLSecondPart () {
      $html = '';
      if ( !$this->_send_first_html_part ) {
         $html .= $this->asHTMLFirstPart();
      }
      return $html;
   }

  function getProfileBoxAsHTML(){
     $html = '';
     global $profile_view;
     if ( !empty($profile_view) ) {
        $environment = $this->_environment;
        $html  = '<div style="position:absolute; left:0px; top:0px; z-index:1000; width:100%; height: 100%;">'.LF;
        $html .= '<div style="z-index:1000; margin-top:40px; margin-bottom:0px; margin-left: 20%; width:60%; text-align:left; background-color:#FFFFFF;">';
        $html .= $profile_view->asHTML();
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div id="profile" style="position: absolute; left:0px; top:0px; z-index:900; width:100%; height: 850px; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">'.LF;
        $html .= '</div>';
     }
     return $html;
  }

  function getCopyBoxAsHTML(){
     $html = '';
     $environment = $this->_environment;
     $html  = '<div style="position:absolute; left:0px; top:0px; z-index:1000; width:100%; height: 100%;">'.LF;
     $html .= '<div style="z-index:1000; margin-top:40px; margin-bottom:10px; margin-left: 15%; width:70%; text-align:left; background-color:#FFFFFF;">';
     global $copy_view;
     $html .= $copy_view->asHTML();
     $html .= '</div>';
     $html .= '</div>';
     $html .= '<div id="profile" style="position: absolute; left:0px; top:0px; z-index:900; width:100%; height: 850px; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">'.LF;
     $html .= '</div>';
     return $html;
  }

  function getBuzzwordBoxAsHTML(){
     $html = '';
     $environment = $this->_environment;
     $html  = '<div style="position:absolute; left:0px; top:0px; z-index:1000; width:100%; height: 100%;">'.LF;
     $html .= '<div style="z-index:1000; margin-top:40px; margin-bottom:10px; margin-left: 20%; width:60%; text-align:left; background-color:#FFFFFF;">';
     global $buzzword_view;
     $html .= $buzzword_view->asHTML();
     $html .= '</div>';
     $html .= '</div>';
     $html .= '<div id="buzzword" style="position: absolute; left:0px; top:0px; z-index:900; width:100%; height: 850px; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">'.LF;
     $html .= '</div>';
     return $html;
  }

  function getTagBoxAsHTML(){
     $html = '';
     $environment = $this->_environment;
     $html  = '<div style="position:absolute; left:0px; top:0px; z-index:1000; width:100%; height: 100%;">'.LF;
     $html .= '<div style="z-index:1000; margin-top:40px; margin-bottom:10px; margin-left: 30%; width:40%; text-align:left; background-color:#FFFFFF;">';
     global $tag_view;
     $html .= $tag_view->asHTML();
     $html .= '</div>';
     $html .= '</div>';
     $html .= '<div id="tag" style="position: absolute; left:0px; top:0px; z-index:900; width:100%; height: 850px; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">'.LF;
     $html .= '</div>';
     return $html;
  }

  function getItemListBoxAsHTML(){
     $html = '';
     global $item_attach_index_view;
     if ( isset($item_attach_index_view) ) {
        $html  = '<div style="position:absolute; left:0px; top:0px; z-index:1000; width:100%; height: 100%;">'.LF;
        $html .= '<div style="z-index:1000; margin-top:40px; margin-bottom:10px; margin-left: 15%; width:70%; text-align:center;">';
        $html .= $item_attach_index_view->asHTML();
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div id="item_list" style="position: absolute; left:0px; top:0px; z-index:900; width:100%; height: 850px; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">'.LF;
        $html .= '</div>';
     }
     return $html;
  }

   function getDeleteBoxAsHTML(){
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');

      // background div
      $html = '<div style="position: fixed;
                        z-index: 1000;
                        top: 0px;
                        left: 0px;
                        width: 100%;
                        height: 100%;
                        background-color: white;
                        opacity: 0.7;
                        filter: alpha(opacity=70);
                        -moz-opacity: 0.7;
                        -khtml-opacity: 0.7;"></div>'.LF;

      // box div
      $html .= '<div style="position: fixed;
                           z-index: 1100;
                           margin-top: 100px;
                           margin-left: 30%;
                           width: 400px;
                           padding: 20px;
                           background-color: white;
                           border: 2px solid red;
                           top: 0px;">'.LF;

      //$left = '0em';
      //$width = '100%';
      //$html  = '<div style="position: absolute; z-index:1000; top:95px; left:'.$left.'; width:'.$width.'; height: 100%;">'.LF;
      $html .= '<center>';
      //$html .= '<div style="position:fixed; left:'.$left.'; z-index:1000; margin-top:10px; margin-left: 30%; width:400px; padding:20px; background-color:#FFF; border:2px solid red;">';
      $html .= '<form style="margin-bottom:50px;" method="post" action="'.$this->_delete_box_action_url.'">'.LF;
      foreach ( $this->_delete_box_hidden_values as $key => $value ) {
         $html .= '<input type="hidden" name="'.$key.'" value="'.$value.'"/>'.LF;
      }

      if ($this->_delete_box_mode == 'index'){
         $html .= '<h2>'.$this->_translator->getMessage('COMMON_DELETE_BOX_INDEX_TITLE');
         $html .= '</h2>'.LF;
         $count = 0;
         if($this->_delete_box_ids){
            $count = count($this->_delete_box_ids);
         }
         if($count == 1){
            $html .= '<p style="text-align:left;">'.$this->_translator->getMessage('COMMON_DELETE_BOX_INDEX_DESCRIPTION_ONE_ENTRY',$count);
            $html .= '</p>'.LF;
         }else{
            $html .= '<p style="text-align:left;">'.$this->_translator->getMessage('COMMON_DELETE_BOX_INDEX_DESCRIPTION',$count);
            $html .= '</p>'.LF;
         }
      } elseif ( $this->_environment->getCurrentFunction() == 'preferences'
                 or
                 ( $this->_environment->getCurrentModule() == 'project'
                   and $this->_environment->getCurrentFunction() == 'detail'
                 )
               ) {
         $html .= '<h2>'.$this->_translator->getMessage('COMMON_DELETE_BOX_TITLE_ROOM');
         $html .= '</h2>'.LF;
         $html .= '<p style="text-align:left;">'.$this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_ROOM');
         $html .= '</p>'.LF;
      } elseif ( $this->_environment->getCurrentModule() == 'material'
                 and $this->_environment->getCurrentFunction() == 'detail'
                 and ( isset($_GET['del_version'])
                       and ( !empty($_GET['del_version'])
                             or $_GET['del_version'] == 0
                           )
                     )
               ) {
         $html .= '<h2>'.$this->_translator->getMessage('COMMON_DELETE_VERSION_TITLE_MATERIAL_VERSION');
         $html .= '</h2>'.LF;
         $html .= '<p style="text-align:left;">'.$this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_MATERIAL_VERSION');
         $html .= '</p>'.LF;
      }elseif ( $this->_environment->getCurrentModule() == 'configuration'
                   and $this->_environment->getCurrentFunction() == 'wiki'
               ) {
         $html .= '<h2>'.$this->_translator->getMessage('COMMON_DELETE_WIKI_TITLE');
         $html .= '</h2>'.LF;
         $html .= '<p style="text-align:left;">'.$this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_WIKI');
         $html .= '</p>'.LF;

      }elseif ( $this->_environment->getCurrentModule() == 'configuration'
                   and $this->_environment->getCurrentFunction() == 'wordpress'
               ) {
         $html .= '<h2>'.$this->_translator->getMessage('COMMON_DELETE_WORDPRESS_TITLE');
         $html .= '</h2>'.LF;
         $html .= '<p style="text-align:left;">'.$this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_WORDPRESS');
         $html .= '</p>'.LF;
      } elseif ( $this->_environment->getCurrentModule() == 'configuration'
                 and ( $this->_environment->getCurrentFunction() == 'room_options'
                       or $this->_environment->getCurrentFunction() == 'account_options'
                     )
               ) {
         $html .= '<h2>'.$this->_translator->getMessage('COMMON_DELETE_BOX_TITLE_ROOM');
         $html .= '</h2>'.LF;
         $html .= '<p style="text-align:left;">'.$this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_ROOM');
         $html .= '</p>'.LF;
      } elseif ( $this->_environment->getCurrentModule() == 'account'
               ) {
         $html .= '<h2>'.$this->_translator->getMessage('USER_CLOSE_FORM');
         $html .= '</h2>'.LF;

         // datenschutz: overwrite or not (03.09.2012 IJ)
   	   $overwrite = true;
   	   global $symfonyContainer;
       $disable_overwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');
   	   if ( !empty($disable_overwrite) and $disable_overwrite === 'TRUE') {
   		   $overwrite = false;
   	   }
   	   if ($overwrite) {
            $html .= '<p style="text-align:left;">'.$this->_translator->getMessage('USER_DELETE_FORM_DESCRIPTION');
         } else {
         	$html .= '<p style="text-align:left;">'.$this->_translator->getMessage('USER_DELETE_FORM_DESCRIPTION_NOT_OVERWRITE');
         }
         $html .= '</p>'.LF;
      } elseif ( $this->_environment->getCurrentModule() == 'group'
                 and $this->_environment->getCurrentFunction() == 'detail'
               ) {
         $iid = $this->_environment->getValueOfParameter('iid');
         $group_manager = $this->_environment->getGroupManager();
         $group_item = $group_manager->getItem($iid);
         if ( $group_item->isGroupRoomActivated() ) {
            $title = $this->_translator->getMessage('COMMON_DELETE_GROUP_WITH_ROOM_TITLE');
            $desc = $this->_translator->getMessage('COMMON_DELETE_GROUP_WITH_ROOM_DESC');
            $desc .= BRLF.BRLF.$this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION');
         } else {
            $title = $this->_translator->getMessage('COMMON_DELETE_BOX_TITLE');
            $desc = $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION');
         }
         $html .= '<h2>'.$title.'</h2>'.LF;
         $html .= '<p style="text-align:left;">'.$desc.'</p>'.LF;
         $user_item = $this->_environment->getCurrentUserItem();
         if( $user_item->isModerator() ) {
            $html .= '<p style="text-align:left;">'.$this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_MODERATOR');
            $html .= '</p>'.LF;
         }
      } elseif ( $this->_environment->getCurrentModule() == 'discussion'
                 and $this->_environment->getCurrentFunction() == 'detail'
               ) {
         $user_item = $this->_environment->getCurrentUserItem();
         $html .= '<h2>'.$this->_translator->getMessage('COMMON_DELETE_BOX_TITLE');
         $html .= '</h2>'.LF;
         $html .= '<p style="text-align:left;">'.$this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_DISCUSSION');
         $html .= '</p>'.LF;
         if( $user_item->isModerator() ) {
            $html .= '<p style="text-align:left;">'.$this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_MODERATOR');
            $html .= '</p>'.LF;
         }
      }else{
         $user_item = $this->_environment->getCurrentUserItem();
         $html .= '<h2>'.$this->_translator->getMessage('COMMON_DELETE_BOX_TITLE');
         $html .= '</h2>'.LF;
         $html .= '<p style="text-align:left;">'.$this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION');
         $html .= '</p>'.LF;
         if( $user_item->isModerator() ) {
            $html .= '<p style="text-align:left;">'.$this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_MODERATOR');
            $html .= '</p>'.LF;
         }
      }
      $html .= '<div>'.LF;
      $html .= '<input style="float:right;" type="submit" name="delete_option" value="'.$this->_translator->getMessage('COMMON_DELETE_BUTTON').'" tabindex="2"/>'.LF;
      $html .= '<input style="float:left;" type="submit" name="delete_option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'" tabindex="2"/>'.LF;
      if(isset($_GET['recurrence_id'])){
         $html .= '<br/><br>' . $this->_translator->getMessage('COMMON_DELETE_RECURRENCE_DESC');
         $html .= '<br/><input style="clear:both" type="submit" name="delete_option" value="'.$this->_translator->getMessage('COMMON_DELETE_RECURRENCE_BUTTON').'" tabindex="2"/>'.LF;
      }
      if ( ( $this->_environment->getCurrentModule() == 'configuration'
             and ( $this->_environment->getCurrentFunction() == 'preferences'
                   or $this->_environment->getCurrentFunction() == 'room_options'
                   or $this->_environment->getCurrentFunction() == 'account_options'
                 )
           )
           or
           ( $this->_environment->getCurrentModule() == 'project'
             and $this->_environment->getCurrentFunction() == 'detail'
           )
         ) {
         $html .= '<input style="float:left;" type="submit" name="delete_option" value="'.$this->_translator->getMessage('ROOM_ARCHIV_BUTTON').'" tabindex="2"/>'.LF;
      } elseif ( $this->_environment->getCurrentModule() == 'account' ) {
         $html .= '<input style="float:left;" type="submit" name="delete_option" value="'.$this->_translator->getMessage('COMMON_USER_REJECT_BUTTON').'" tabindex="2"/>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '</form>'.LF;
      $html .= '</div>'.LF;
      $html .= '</center>'.LF;
      //$html .= '</div>'.LF;
      $html .= '<div id="delete" style="position: absolute; z-index:900; top:95px; left:0em; width:100%; height: 100%; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">';
      $html .= '</div>'.LF;
      return $html;
   }

   function addDeleteBox($url,$mode='detail',$selected_ids = NULL){
      $this->_with_delete_box = true;
      $this->_delete_box_action_url = $url;
      $this->_delete_box_mode = $mode;
      $this->_delete_box_ids = $selected_ids;
   }

   function addDeleteBoxHiddenValues ($array){
      $this->_delete_box_hidden_values = $array;
   }

   function asHTML () {
      $html = '';
      $session = $this->_environment->getSession();
      if (!empty($session)) {
         $session_id = $session->getSessionID();
      } else {
         $session_id = '';
      }
      if ( !$this->_blank_page ) {
         // Link Row
         if ($this->_with_navigation_links and !$this->_shown_as_printable) {
            $html .= $this->_getLinkRowAsHTML();
         } else {
            $html .= $this->_getBlankLinkRowAsHTML();
         }
         $width = 'width:100%;';//not used
         $html .= '<div style="padding:0px; margin:0px;">'.LF;
         $html .= '<div class="content">'.LF;

         // Full Screen Views
         $first = true;
         $html .= '<div class="content_fader">';

         $show_agb_again = false;
         $current_user = $this->_environment->getCurrentUserItem();
         $current_context = $this->_environment->getCurrentContextItem();
         if ( $current_user->isUser()
              and !$current_user->isRoot()
              and $current_context->withAGB()
            ) {
            $user_agb_date = $current_user->getAGBAcceptanceDate();
            $context_agb_date = $current_context->getAGBChangeDate();
            if ($user_agb_date < $context_agb_date) {
               $show_agb_again = true;
            }
         }
         unset($current_user);


         $html .= LF.'<div id="main">'.LF;

         $html .= $this->_getPluginInfosForBeforeContentAsHTML();

         if ($show_agb_again) {
            $html .='&nbsp;';
         }

         if ( !empty($this->_views) ) {
            foreach ($this->_views as $view) {
               if ($first){
                  $first = false;
                  $html .= $view->asHTML();
               }else{
                  $html .= $view->asHTML();
               }
            }
         }
         if ($this->_environment->getCurrentModule()!='home' or isset($view->_title) or $show_agb_again){
            $html .='</div>';
         }

         // Left views
         if ( !empty($this->_views_right) ) {
            if ($this->_environment->getCurrentModule()=='home'){
               $context_item = $this->_environment->getCurrentContextItem();
               $title_string = '';
               $desc_string = '';
               $size_string = '';
               $mod_string = '';
               $config_text = '';
               $html .='<div style="clear:both;">'.LF;
               $html .='</div>'.LF;
               $html .= '<div id="commsy_panels" style="width:100%;">'.LF;
               $html .='<div style="float:right; width:28%; padding-top:5px; padding-left:0px; vertical-align:top; text-align:left;">'.LF;
               $html .='<div style="width:250px;">'.LF;
               if ( $this->_environment->inPrivateRoom() ) {
                  $list_infos = '';
                  foreach ( $this->_views_left as $view ) {
                     if ( method_exists($view,'_getListInfosAsHTML') ) {
                        $list_infos = $view->_getListInfosAsHTML();
                        break;
                     }
                  }
                  $html .= $list_infos.LF;
                  unset($list_infos);
                  unset($view);
               }
               $first = true;
               $count = 1;


               $home_right_conf = $context_item->getHomeRightConf();
               $right_boxes_config_array = array();
               if (!empty($home_right_conf)){
                  $home_right_conf_array = explode(',',$home_right_conf);
                   foreach ($home_right_conf_array as $box_conf) {
                      $box_conf_array = explode('_',$box_conf);
                      $right_boxes_config_array[$box_conf_array[0]]= $box_conf_array[1];
                   }
                }
                $append = '';
                foreach ($this->_views_right as $view) {
                  $view_name = $view->getViewName();
                  if($view_name != 'actions' and $view_name != 'activity' and $view_name != 'usageinfos' and $view_name != 'preferences' and $view_name != 'search' and $view_name != 'homeextratools'
                     and ($view_name != 'netnavigation' or $context_item->withRubric(CS_GROUP_TYPE) or $context_item->withRubric(CS_TOPIC_TYPE) or $context_item->withRubric(CS_INSTITUTION_TYPE) )
                  ){
                     $html .= '<div class="commsy_panel" style="margin-bottom:1px;">'.LF;
                     $html .= $view->asHTML();
                     $title_string .= $append.'"'.$view->getViewTitle().'"';
                     $desc_string  .= $append.'"&nbsp;"';
                     $mod_string  .= $append.'"&nbsp;"';
                     if ($view_name == 'buzzwords'){
                        $buzzword_manager = $this->_environment->getLabelManager();
                        $buzzword_manager->resetLimits();
                        $buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
                        $buzzword_manager->setTypeLimit('buzzword');
                        $size = count($buzzword_manager->getIds());
                        unset($buzzword_manager);
                        $size_string  .= $append.'"'.$size.'"';
                     }elseif($view_name == 'tags'){
                        $tag_manager = $this->_environment->getTagManager();
                        $tag_manager->setContextLimit($this->_environment->getCurrentContextID());
                        $tag_manager->select();
                        $size = $tag_manager->getCountAll();
                        unset($tag_manager);
                        $size_string  .= $append.'"'.$size.'"';
                     }else{
                        $size_string  .= $append.'"10"';
                     }
                     if (
                         ($view_name == 'buzzwords' and $context_item->isBuzzwordShowExpanded() )
                         or ($view_name == 'tags' and $context_item->isTagsShowExpanded() )
                         or ($view_name == 'netnavigation' and $context_item->isNetnavigationShowExpanded() )
                     ) {
                        $config_text .= $append.'true';
                     }else{
                        $config_text .= $append.'false';
                     }
                     $html .= '</div>';
                  }elseif($view_name == 'activity'){
                     $html .= '<div style="margin-bottom:1px;">'.LF;
                     $html .= $view->asHTML();
                     $html .= '</div>';
                  }elseif($view_name == 'usageinfos'){
                     $html .= '<div style="margin-bottom:10px;">'.LF;
                     $html .= $view->asHTML();
                     $html .= '</div>';
                  }
                  if ( $view_name == 'buzzwords' or $view_name == 'tags' or $view_name == 'netnavigation') {
                     $append = ',';
                  }
               }
               $html .= '</div>'.LF;
               $html .= '</div>'.LF;
               $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
               $current_browser_version = $this->_environment->getCurrentBrowserVersion();
               if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
                  $width= ' width:100%; padding-right:10px;';
               }else{
                  $width= '';
               }
               $html .='<div class="content_display_width" style="'.$width.'padding-top:5px; vertical-align:bottom;">'.LF;

               $conf = $context_item->getHomeConf();
               if ( !empty($conf) ) {
                  $rubrics = explode(',', $conf);
               } else {
                  $rubrics = array();
               }
               foreach ( $rubrics as $rubric ) {
                  $rubric_array = explode('_', $rubric);
                  if ( $rubric_array[1] != 'none' and $rubric_array[1] != 'nodisplay') {
                     if ($rubric_array[1] == 'short'){
                        if (empty($config_text)){
                           $config_text .='true';
                        }else{
                           $config_text .= ',true';
                        }
                     }else{
                        if (empty($config_text)){
                           $config_text .='false';
                        }else{
                           $config_text .= ',false';
                        }
                     }
                  }
               }
               foreach ($this->_views_left as $view) {
                  if (!$this->_environment->inPrivateRoom()){
                     if ($view->getViewName() != $this->_translator->getMessage('COMMON_INFORMATION_INDEX')){
                        $html .= '<div class="commsy_panel" style="margin-bottom:20px; border:0px solid black;">'.LF;
                     }else{
                        $html .= '<div id="commsy_no_panel" style="margin-bottom:20px; border:0px solid black;">'.LF;
                     }$desc = $view->_getDescriptionAsHTML();
                     $noscript_title = $view->getViewTitle();
                     if ($view instanceof cs_user_short_view or $view instanceof cs_user_tiny_view){
                        $image_text = 'user';
                     }elseif ($view instanceof cs_announcement_short_view or $view instanceof cs_announcement_tiny_view){
                        $image_text = 'announcement';
                     }elseif ($view instanceof cs_project_short_view or $view instanceof cs_project_tiny_view){
                        $image_text = 'room';
                     }elseif ($view instanceof cs_date_short_view or $view instanceof cs_date_tiny_view){
                        $image_text = 'date';
                     }elseif ($view instanceof cs_group_short_view or $view instanceof cs_group_tiny_view){
                        $image_text = 'group';
                     }elseif ($view instanceof cs_institution_short_view or $view instanceof cs_institution_tiny_view){
                        $image_text = 'group';
                     }elseif ($view instanceof cs_todo_short_view or $view instanceof cs_todo_tiny_view){
                        $image_text = 'todo';
                     }elseif ($view instanceof cs_topic_short_view or $view instanceof cs_topic_tiny_view){
                        $image_text = 'topic';
                     }elseif ($view instanceof cs_institution_short_view or $view instanceof cs_institution_tiny_view){
                        $image_text = 'institution';
                     }elseif ($view instanceof cs_discussion_short_view or $view instanceof cs_discussion_tiny_view){
                        $image_text = 'discussion';
                     }else{
                        $image_text = 'material';
                     }
                     $mod = $image_text;
                     if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
                        $image = '<img src="images/commsyicons_msie6/16x16/'.$image_text.'.gif" style="padding-top:2px; float:left;"/>';
                     } else {
                        $image = '<img src="images/commsyicons/16x16/'.$image_text.'.png" style="padding-top:2px; float:left;"/>';
                     }
                     $title = addslashes($image.' '.$view->getViewTitle());
                     if ($view->getViewName() != $this->_translator->getMessage('COMMON_INFORMATION_INDEX')){
                       $item_list = $view->getList();
                       $size = 10;
                       if ( isset($item_list) ) {
                          $size = $item_list->getCount();
                       }
                       if ( empty($size) ) {
                          $size = 10;
                       }
                       if ( !empty($size) and
                            ( $view instanceof cs_user_short_view
                              or $view instanceof cs_topic_short_view
                              or $view instanceof cs_institution_short_view
                              or $view instanceof cs_group_short_view
                            )
                          ) {
                          $size = round($size/3,0);
                       }
                       if (empty($title_string)){
                          $title_string .= '"'.$title.'"';
                          $desc_string  .= '"'.$desc.'"';
                          $size_string  .= '"'.$size.'"';
                          $mod_string   .= '"'.$mod.'"';
                       }else{
                          $title_string .= ',"'.$title.'"';
                          $desc_string  .= ',"'.$desc.'"';
                          $size_string  .= ',"'.$size.'"';
                          $mod_string   .= ',"'.$mod.'"';
                       }
                     }
                     // jQuery
                     //$html .= '<div>';
                     $html .= '<div id="homeheader">';
                     // jQuery
                     $html .= '<noscript>';
                     $html .= '<div class="homeheader">'.$noscript_title.' '.$desc.'</div>';
                     $html .= '</noscript>';
                  } else {
                     if ($view->getViewName() == $this->_translator->getMessage('COMMON_INFORMATION_INDEX')){
                        $html .= '<div id="commsy_no_panel" style="margin-bottom:20px; border:0px solid black;">'.LF;
                     }
                  }
                  $html .= $view->asHTML();
                  if (!$this->_environment->inPrivateRoom()){
                     $html .= '</div>'.LF;
                     $html .= '</div>'.LF;
                  } else {
                     if ($view->getViewName() == $this->_translator->getMessage('COMMON_INFORMATION_INDEX')){
                        $html .= '</div>'.LF;
                     }
                  }
                  $last_view = $view;
               }

               $html .= '</div>';
               $html .= '</div>';
               $html .='<div style="clear:both;">'.LF;
               $html .='</div>'.LF;
#               $html .= '</form>'."\n";
               $html .='</div>'.LF;
               $html .= '<script type="text/javascript">'.LF;
               /*******************************/
               /* TBD: Die Werte des dritten Arrays setzen, falls die VerÃ¤nderungen*/
               /* gespeichert werden sollen Array("pane1","pane1",...)*/
               /*******************************/
               $title_string = str_replace('</','&COMMSYDHTMLTAG&',$title_string);
               if($this->_with_modifying_actions){
                  $modify = 1;
                  $html .= 'var new_action_message="' . $this->_translator->getMessage('COMMON_NEW_ITEM') . '";'.LF;
                  if ( !$session->issetValue('cookie')
                    or $session->getValue('cookie') == '0' ) {
                    $html .= 'var session_id = "' . $session->getSessionID() . '";' . LF;
                  } else {
                    $html .= 'var session_id = false;' . LF;
                  }
                  // Test auf Gemeinschaftsraum
                  if($context_item->isCommunityRoom()){
                     $html .= 'var is_community_room = true;' . LF;
                  } else {
                     $html .= 'var is_community_room = false;' . LF;
                  }
               } else {
                  $modify = 0;
                  $html .= 'var new_action_message="' . $this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_NEW_ITEM')) . '";'.LF;
               }
               $html .= 'initCommSyPanels(Array('.$title_string.'),Array('.$desc_string.'),Array('.$config_text.'),Array(),Array('.$size_string.'),Array('.$mod_string.'),' . $this->_environment->getCurrentContextID() . ',' . $modify . ');'.LF;
               $html .= '</script>'.LF;
            }else{
               $html .= '<table style="width:100%; padding-top:0px;" summary="Layout">'.LF;
               $html .= '<tr>'.LF;
               $html .= '<td class="leftviews">'.LF;
               $html .= '<div class="infoborder" style="margin-top:1px; padding-bottom:10px;"></div>'.LF;
               foreach ($this->_views_left as $view) {
                  $html .= $view->asHTML();
                  $last_view = $view;
               }
               $html .= '</td>'.LF;
               $html .= '<td class="rightviews">'.LF;
               $html .= '<div class="infoborder" style="margin-top:1px; padding-bottom:10px;"></div>'.LF;
               $first = true;
               foreach ($this->_views_right as $view) {
               if ($first) {
                  $first = false;
               } else {
                  $html .= '<br/>'.LF;
               }
               $html .= $view->asHTML();
               $last_view = $view;
               }
               unset($first);
               $html .= '</td>'.LF;
               $html .= '</tr>'.LF;
               $html .= '</table>'.LF;
            }
         }else{
            $html .= '<div style="clear:both"/></div>'.LF;
         }

         $html .= '<div class="top_of_page">'.LF;
         $html .= '<div style="float:right; padding-right:0px;">'.LF;
         $date = date("Y-m-d");
         $date_array = explode('-',$date);
         $current_time = localtime();
         $month = getLongMonthName($current_time[4]);
         $year = $current_time[5]+1900;
         $language = $this->_environment->getSelectedLanguage();
         if ($language !='en'){
            $text = $date_array[2].'. '.$month.' '.$date_array[0];
         }else{
            $text = $date_array[2].' '.$month.' '.$date_array[0];
         }
         $text .=', ';
         if (mb_strlen($current_time[1])==1){
            $current_time[1] = '0'.$current_time[1];
         }
         $text .= $current_time[2].':'.$current_time[1];
         $html .= '<span>'.$text.'</span>'.LF;
         $html .= '</div>'.LF;

         $html .= '<div class="footer">'.LF;
         $html .= '<a href="#top">'.'<img src="images/browse_left2.gif" alt="&lt;" border="0"/></a>&nbsp;<a href="#top">'.$this->_translator->getMessage('COMMON_TOP_OF_PAGE').'</a>';
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;

/*         if ($this->_with_navigation_links and !$this->_shown_as_printable) {
            $html .= $this->_getBlankLinkRowAsHTML(true);
         } else {
            $html .= $this->_getBlankLinkRowAsHTML();
         }*/
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;


         if ($this->_with_delete_box){
            $html .= $this->getDeleteBoxAsHTML();
         }

         if ( isset($_GET['show_profile']) and $_GET['show_profile'] == 'yes'){
            $html .= $this->getProfileBoxAsHTML();
         }
         if ( isset($_GET['show_copies']) and ($_GET['show_copies'] == 'yes') ) {
            $html .= $this->getCopyBoxAsHTML();
         }
         if ( (isset($_GET['attach_view'])
              and ($_GET['attach_view'] == 'yes')
              and isset($_GET['attach_type'])
              and !empty($_GET['attach_type'])
              and $_GET['attach_type'] == 'buzzword')
              or (
                   isset($_POST['right_box_option'])
                   and isOption($_POST['right_box_option'], $this->_translator->getMessage('COMMON_BUZZWORD_NEW_ATTACH'))
                   and (!isset($_POST['return_attach_buzzword_list']))
                 )
              or ( !empty($_POST['option'])
                   and isOption($_POST['option'], $this->_translator->getMessage('COMMON_BUZZWORD_ADD'))
                 )
            ) {
            $html .= $this->getBuzzwordBoxAsHTML();
         }
         if ( (isset($_GET['attach_view'])
              and ($_GET['attach_view'] == 'yes')
              and isset($_GET['attach_type'])
              and !empty($_GET['attach_type'])
              and $_GET['attach_type'] == 'tag')
              or(
                 isset($_POST['right_box_option'])
                 and isOption($_POST['right_box_option'], $this->_translator->getMessage('COMMON_TAG_NEW_ATTACH'))
                 and (!isset($_POST['return_attach_tag_list']))
              )
            ) {
            $html .= $this->getTagBoxAsHTML();
         }
         if ( ( isset($_GET['attach_view'])
                and ($_GET['attach_view'] == 'yes')
                and isset($_GET['attach_type'])
                and !empty($_GET['attach_type'])
                and $_GET['attach_type'] == 'item'
              )
              or
              ( isset($_POST['right_box_option'])
                and (
                     isOption($_POST['right_box_option'], $this->_translator->getMessage('COMMON_ITEM_NEW_ATTACH')) or
                     isOption($_POST['right_box_option'], $this->_translator->getMessage('COMMON_GROUP_ATTACH')) or
                     isOption($_POST['right_box_option'], $this->_translator->getMessage('COMMON_INSTITUTION_ATTACH'))
                    )
                 and (!isset($_POST['return_attach_tag_list']))
              )
            ) {
            $html .= $this->getItemListBoxAsHTML();
         }

         if ( !empty( $this->_views_overlay ) ) {
            foreach ( $this->_views_overlay as $view ) {
               $html .= $this->_getOverlayBoxAsHTML($view);
            }
         }

         $html .= $this->_getPluginInfosForAfterContentAsHTML();

         if ( $this->_environment->getCurrentModule() == 'agb'
              and $this->_environment->getCurrentFunction() == 'index'
            ) {
            $html .= '<div id="ie_footer" class="commsy_footer" style="width: 550px;">'.LF;
         } else {
            $html .= '<div id="ie_footer" class="commsy_footer">'.LF;
         }
         $html .= '<div class="footer" style="float:right; text-align:right; padding-left:20px; padding-right:12px; padding-top:5px; padding-bottom:10px; ">'.LF;
         $email_to_moderators = '';
         $current_user = $this->_environment->getCurrentUserItem();
         $current_context = $this->_environment->getCurrentContextItem();
         if ( $current_context->showMail2ModeratorLink() ) {
            $email_to_moderators = ahref_curl($this->_environment->getCurrentContextID(),
                                                'mail',
                                                 'to_moderator',
                                                 '',
                                                 $this->_translator->getMessage('COMMON_MAIL_TO_MODERATOR'));
         }

         // service link

         if ( $current_context->withAGB() and $this->_with_agb_link ) {
            $desc_link = ahref_curl($this->_environment->getCurrentContextID(),'agb','index','',$this->_translator->getMessage('COMMON_AGB_CONFIRMATION_LINK_INPUT'),'','agb','','',' onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=yes, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"');
         } else {
            $desc_link ='';
         }
         if ( $current_context->showServiceLink()
              and $current_user->isUser()
              and !$this->_environment->inPrivateRoom()
              and !( $this->_environment->getCurrentModule() =='agb' and $this->_environment->getCurrentFunction()=='index' )
            ) {

            // exernal link: BEGIN
            // Hierarchy of service-email: Set email, test if portal tier has one, then server tier
            $service_link_ext = $current_context->getServiceLinkExternal();

            if ($service_link_ext == '') {
               $portal_item = $this->_environment->getCurrentPortalItem();
               if (isset($portal_item) and !empty($portal_item)) {
                  $service_link_ext = $portal_item->getServiceLinkExternal();
               }
               unset($portal_item);
            }

            if ($service_link_ext == '') {
               $server_item = $this->_environment->getServerItem();
               $service_link_ext = $server_item->getServiceLinkExternal();
            }

            if ( !empty($service_link_ext) ) {
               if ( strstr($service_link_ext,'%') ) {
                  $text_convert = $this->_environment->getTextConverter();
                  $service_link_ext = $text_convert->convertPercent($service_link_ext,false,true);
               }
               $email_to_service = '<a href="'.$service_link_ext.'" title="'.$this->_translator->getMessage('COMMON_MAIL_TO_SERVICE2_LINK_TITLE').'" target="_blank">'.$this->_translator->getMessage('COMMON_MAIL_TO_SERVICE2').'</a>';
            } else {
            // exernal link: END

               $server_item = $this->_environment->getServerItem();
               $link = 'http://www.commsy.net/?n=Software.FAQ&amp;mod=edit';

               //Hierarchy of service-email: Set email, test if portal tier has one, then server tier
               $service_email = $current_context->getServiceEmail();

               if ($service_email == '') {
                  $portal_item = $this->_environment->getCurrentPortalItem();
                  if (isset($portal_item) and !empty($portal_item)) {
                     $service_email = $portal_item->getServiceEmail();
                  }
                  unset($portal_item);
               }

               if ($service_email == '') {
                  $service_email = $server_item->getServiceEmail();
               }

              if ($service_email == '') {
                  $service_email = 'NONE';
               }

               $ip = 'unknown';
               if ( !empty($_SERVER["SERVER_ADDR"]) ) {
                  $ip = $_SERVER["SERVER_ADDR"];
               } elseif ( !empty($_SERVER["HTTP_HOST"]) ) {
                  $ip = $_SERVER["HTTP_HOST"];
               }

               $email_to_service = '<form action="'.$link.'" method="post" name="service" style="margin-bottom: 0px;">'.LF;
               $email_to_service .= '<input type="hidden" name="server_name" value="'.$this->_text_as_html_short($server_item->getTitle()).'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="server_ip" value="'.$this->_text_as_html_short($ip).'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="context_id" value="'.$this->_text_as_html_short($current_context->getItemID()).'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="context_name" value="'.$this->_text_as_html_short($current_context->getTitle()).'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="context_type" value="'.$this->_text_as_html_short($current_context->getType()).'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="user_name" value="'.$this->_text_as_html_short($current_user->getFullname()).'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="user_email" value="'.$this->_text_as_html_short($current_user->getEmail()).'"/>'.LF;
               $email_to_service .= '<input type="hidden" name="service_email" value="'.$this->_text_as_html_short($service_email).'"/>'.LF;
               #$email_to_service .= $this->_translator->getMessage('COMMON_MAIL_TO_SERVICE').' <input type="image" src="images/servicelink.jpg" alt="Link to CommSyService" style="vertical-align:text-bottom;" />'.LF;
               // jQuery
               //$email_to_service .= '<a href="javascript:document.service.submit();" title="'.$this->_translator->getMessage('COMMON_MAIL_TO_SERVICE2_LINK_TITLE').'">'.$this->_translator->getMessage('COMMON_MAIL_TO_SERVICE2').'</a>'.LF;
               $email_to_service .= '<a href="#" id="submit_form" title="'.$this->_translator->getMessage('COMMON_MAIL_TO_SERVICE2_LINK_TITLE').'">'.$this->_translator->getMessage('COMMON_MAIL_TO_SERVICE2').'</a>'.LF;
               // jQuery
               $email_to_service .= '</form>'.LF;
            }
            $html .= '<table style="margin:0px; padding:0px; border-collapse: collapse; border:0px solid black;" summary="Layout">'.LF;
            $html .= '  <tr>'.LF;
            $html .= '     <td style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">'.LF;
            if($email_to_moderators != '') {
               $html .= $email_to_moderators.'&nbsp;-&nbsp;';
            }
            $html .= '     </td>'.LF;
            if ( !empty($desc_link) ){
               $html .= '     <td style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">'.LF;
               $html .= $desc_link.'&nbsp;-&nbsp;';
               $html .= '     </td>'.LF;
            }
            if ( !empty($email_to_service) ){
               $html .= '     <td style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">'.LF;
               $html .= $email_to_service;
               $html .= '     </td>'.LF;
            }
            $html .= '  </tr>'.LF;
            $html .= '</table>'.LF;
         } elseif ( !$this->_environment->inPrivateRoom()
                    and !( $this->_environment->getCurrentModule() =='agb'
                           and $this->_environment->getCurrentFunction()=='index'
                         )
                  ) {
            $html .= '<table style="margin:0px; padding:0px; border-collapse: collapse; border:0px solid black;" summary="Layout">'.LF;
            $html .= '  <tr>'.LF;
            $html .= '     <td style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">'.LF;
            $html .= $email_to_moderators;
            $html .= '</td>'.LF;
            if ( !empty($desc_link) ){
               $html .= '     <td style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">'.LF;
               $html .= '&nbsp;-&nbsp;'.$desc_link;
               $html .= '     </td>'.LF;
            }
            $html .= '  </tr>'.LF;
            $html .= '</table>'.LF;
         }
         $html .= '</div>'.LF;
         $html .= '<div style="padding-top:5px; padding-left:10px;">'.LF;
         $html .= $this->_getSystemInfoAsHTML();
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         unset($current_user);
         unset($current_context);
         unset($server_item);
         $html .= $this->_getFooterAsHTML();
         $html .= '</body>'.LF;
         $html .= '</html>'.LF;
      }
      return $html;
   }

   private function _getFlagsAsHTML () {
      $html = '&nbsp;&nbsp;|&nbsp;&nbsp;';
      if ( !( $this->_environment->getCurrentModule() == 'agb'
              and $this->_environment->getCurrentFunction() == 'index'
            )
         ) {
         // language options
         $selected_language = $this->_environment->getSelectedLanguage();
         $current_context = $this->_environment->getCurrentContextItem();
         $language = $current_context->getLanguage();
         $language_array = $this->_environment->getAvailableLanguageArray();
         unset($current_context);
         foreach ($language_array as $lang) {
            if ( $lang == 'en' ) {
               $flag_lang = 'gb';
            } else {
               $flag_lang = $lang;
            }
            if ( mb_strtolower($selected_language, 'UTF-8') == $lang ) {
               $img = '<img style="vertical-align:bottom;" src="images/flags/'.$flag_lang.'.gif" alt="'.$this->_translator->getMessageInLang($lang,'COMMON_CHANGE_LANGUAGE_WITH_FLAG').'"/>';
               $html .= $img.'&nbsp;&nbsp;';
            } elseif ( $language != 'user' ) {
               $img = '<img style="vertical-align:bottom;" src="images/flags/'.$flag_lang.'_gray.gif" alt="'.$this->_translator->getMessageInLang($lang,'COMMON_CHANGE_LANGUAGE_WITH_FLAG_DISABLED',$this->_translator->getMessageInLang($lang,mb_strtoupper($language, 'UTF-8'))).'" title="'.$this->_translator->getMessageInLang($lang,'COMMON_CHANGE_LANGUAGE_WITH_FLAG_DISABLED',$this->_translator->getMessageInLang($lang,mb_strtoupper($language, 'UTF-8'))).'"/>';
               $html .= $img.'&nbsp;&nbsp;';
            } else {
               $img = '<img style="vertical-align:bottom;" src="images/flags/'.$flag_lang.'.gif" alt="'.$this->_translator->getMessageInLang($lang,'COMMON_CHANGE_LANGUAGE_WITH_FLAG').'"/>';
               $params = array();
               $params['language'] = $lang;
               $html .= ahref_curl($this->_environment->getCurrentContextID(),'language','change',$params,$img,$this->_translator->getMessageInLang($lang,'COMMON_CHANGE_LANGUAGE_WITH_FLAG')).'&nbsp;&nbsp;'.LF;
               unset($params);
            }
            unset($img);
         }
      }
      
      return $html;
   }


   function _getCopyLinkAsHTML(){
      $html = '';
      $context_item = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $session = $this->_environment->getSession();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  array();
      }
      unset($current_room_modules);
      $modules = array();
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none') {
            $modules[] = $link_name[0];
         }
      }
      unset($room_modules);
      $html_array = array();
      $rubric_copy_array = array(CS_ANNOUNCEMENT_TYPE, CS_DATE_TYPE, CS_DISCUSSION_TYPE, CS_MATERIAL_TYPE,CS_TODO_TYPE);
      $count = 0;
      foreach ($rubric_copy_array as $rubric){
         $id_array = $session->getValue($rubric.'_clipboard');
         $count += count($id_array);
      }
      unset($rubric_copy_array);
      unset($context_item);
      if ( $count > 0 and $current_user->isUser()){
         $params = $this->_environment->getCurrentParameterArray();
         $params['show_copies'] = 'yes';
         unset($params['show_profile']);
         $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(), $this->_environment->getCurrentModule(), $this->_environment->getCurrentFunction(), $params,$this->_translator->getMessage('MYAREA_MY_COPIES'),'','','','','','','style="color:#800000"').''.LF;
      }else{
         $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;'.'<span class="disabled">'.$this->_translator->getMessage('MYAREA_MY_COPIES').'</span>'.LF;
      }
      return $html;
   }




   function getMyAreaAsHTML() {

      $get_vars  = $this->_environment->getCurrentParameterArray();
      $post_vars = $this->_environment->getCurrentPostParameterArray();
      $current_context = $this->_environment->getCurrentContextItem();
      $current_portal = $this->_environment->getCurrentPortalItem();
      if (!empty($get_vars['cs_modus'])) {
         $cs_mod = $get_vars['cs_modus'];
      } elseif (!empty($post_vars['cs_modus'])) {
         $cs_mod = $post_vars['cs_modus'];
      } else {
         $cs_mod = '';
      }
      unset($get_vars);
      unset($post_vars);
      $html  = '<div style="white-space:nowrap;">';
      if ( $this->_with_personal_area) {
         if ( !empty($this->_current_user)
              and ( $this->_current_user->getUserID() == 'guest'
                    and $this->_current_user->isGuest()
                   )
              and !$this->_environment->inServer() ) {
            $html .= $this->_translator->getMessage('MYAREA_LOGIN_NOT_LOGGED_IN');
            if ( $current_context->isOpenForGuests() and !$this->_current_user->isUser()
                 and !$this->_environment->inServer()
                 and !$this->_environment->inPortal()
            ) {
               $html .= ' ('.$this->_translator->getMessage('MYAREA_LOGIN_AS_GUEST').') ';
            }
            $params = array();
            $params['room_id'] = $this->_environment->getCurrentContextID();
            $params['login_redirect'] = true;
            $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentPortalID(), 'home', 'index', $params,$this->_translator->getMessage('MYAREA_LOGIN_BUTTON'),'','','','','','','style="color:#800000"').''.LF;
            // @segment-end 77327
            // @segment-begin 69973 no-cs_modus/user=guest:if-logged-in-as-guest
         } elseif ( !$this->_environment->inServer() and !empty($this->_current_user)) {
                  $params = array();
                  $params['iid'] = $this->_current_user->getItemID();
                  $fullname = $this->_current_user->getFullname();
                  $html .= $fullname;
                  if ( $current_context->isOpenForGuests() and !$this->_current_user->isUser()
                       and !$this->_environment->inServer()
                       and !$this->_environment->inPortal()
                  ) {
                     $html .= ' ('.$this->_translator->getMessage('MYAREA_LOGIN_AS_GUEST').') ';
                  }
               $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(), 'context', 'logout', $params,$this->_translator->getMessage('MYAREA_LOGOUT'),'','','','','','','style="color:#800000"').''.LF;
               $params = $this->_environment->getCurrentParameterArray();
               $params['uid'] = $this->_current_user->getItemID();
               $params['show_profile'] = 'yes';
               unset($params['is_saved']);
               unset($params['show_copies']);
               unset($params['profile_page']);
               if ( !empty($params['mode']) ) {
                  unset($params['mode']);
               }
               if ( !empty($params['download']) ) {
                  unset($params['download']);
               }
               global $c_annonymous_account_array;
               if ( empty($c_annonymous_account_array[mb_strtolower($this->_current_user->getUserID(), 'UTF-8').'_'.$this->_current_user->getAuthSource()])
                    and !$this->_current_user->isOnlyReadUser()
                  ) {
                  $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(), $this->_environment->getCurrentModule(), $this->_environment->getCurrentFunction(), $params,$this->_translator->getMessage('MYAREA_PROFILE'),'','','','','','','style="color:#800000"').''.LF;
                  $html .= $this->_getCopyLinkAsHTML();
               }

               // plugins for users
               $plugin_html = plugin_hook_output_all('getMyAreaActionAsHTML',array(),'&nbsp;');
               if ( !empty($plugin_html) ) {
                  $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;'.$plugin_html;
               }

               $html .= $this->_getFlagsAsHTML();
         }
      }
      if ( $this->_with_personal_area and empty($cs_mod)) {
         if ( !($this->_environment->inServer() and $this->_current_user->isGuest()) ) {
            $params = array();
            if ($this->_environment->inServer()) {
               if ( $this->_current_user->isRoot() ) {
                  $html .= '<tr>';
                  $html .= '<td colspan ="2">';
                  $html .= '<span> '.ahref_curl($this->_environment->getServerID(), 'home',
                                        'index',
                                        '',
                                        '<img src="images/door_open_small.gif" style="vertical-align: middle" alt="door open"/>').LF;

                  $html .= ahref_curl($this->_environment->getServerID(), 'home', 'index', '',$this->_translator->getMessage('MYAREA_LOGIN_TO_ALL_PORTALS'),'','','','','','','style="color:#800000"').'</span>'.LF;
                  $html .= '</td>';
                  $html .= '</tr>';
               }
            }
            unset($current_context);
            unset($current_portal);
         }
      }
      $html .= '</div>';
      return $html;
   }

}
?>