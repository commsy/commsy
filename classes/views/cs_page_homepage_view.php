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

/** upper class of the detail view
 */
$this->includeClass(PAGE_VIEW);

/** class for a page view of commsy
 * this class implements a page view of commsy
 */
class cs_page_homepage_view extends cs_page_view {

   private $_error_views = array();

   private $_shown_homepage_id = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   public function cs_page_homepage_view ($params) {
      $this->cs_page_view($params);
   }

   function setShownHomepageItemID ($value) {
      $this->_shown_homepage_id = (int)$value;
   }

   /** so page will be displayed without the navigation links
    * this method skip a flag, so that the navigation links will not be shown
    */
   function setWithoutNavigationLinks () {
      $this->_with_navigation_links = false;
   }

   /** get page view as HTML
    * this method returns the page view in HTML-Code
    *
    * @return string page view as HMTL
    *
    * @author CommSy Development Group
    */
   function asHTML () {
      $html = '';

      // Header
      $html .= $this->_getHTMLHeadAsHTML();

      // Body
      $html .= '<body';
      if ($this->_focus_onload) {
         $html .= ' onload="window.focus();setfocus();';
         $html .= ' "';
      }
      $views = array_merge($this->_views, $this->_views_left, $this->_views_right);
      $view = reset($views);
      while ($view) {
      $html .= $view->getInfoForBodyAsHTML();
         $view = next($views);
      }
      $html .= '>'.LF;

      $html .= $this->_getPageAsHTML();

      $html .= '</body>'.LF;
      $html .= '</html>'.LF;

      return $html;
   }

   function _getHTMLHeadAsHTML () {
      $module   = $this->_environment->getCurrentModule();
      $function = $this->_environment->getCurrentFunction();

      $retour  = '';
      $retour .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'.LF;
      $retour .= '<html>'.LF;
      $retour .= '<head>'.LF;
      #$retour .= '   <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>'.LF;
      // ------------
      // --->UTF8<---
      $retour .= '   <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>'.LF;
      // --->UTF8<---
      // ------------
      $retour .= '   <meta http-equiv="expires" content="0"/>'.LF;
      $retour .= '   <meta name="MSSmartTagsPreventParsing" content="TRUE"/>'.LF;
      $retour .= '   <link rel="stylesheet" type="text/css" href="homepage_css.php?cid='.$this->_environment->getCurrentContextID().'"/>'.LF;
      $retour .= '   <link rel="SHORTCUT ICON" href="images/favicon.ico"/>'.LF;

      $between = '';
      if ( !empty($this->_name_room) and !empty($this->_name_page)) {
         $between .= ' - ';
      }
      $retour .= '   <title>'.$this->_text_as_html_short($this->_name_room).$between.$this->_text_as_html_short($this->_name_page).'</title>'.LF;
      $views = array_merge($this->_views, $this->_views_left, $this->_views_right);
      $view = reset($views);
      while ($view) {
         $retour .= $view->getInfoForHeaderAsHTML();
         $view = next($views);
      }
      $retour .= '</head>'.LF;
      return $retour;
   }

      function _getPageAsHTML () {
      $retour = '';

      $error = $this->_getErrorAsHTML();
      $header = $this->_getHeaderAsHTML();
      $logo = $this->_getLogoAsHTML();
      $navigation = $this->_getNavigationAsHTML();
      $content = $this->_getContentAsHTML();
      $footer = $this->_getFooterAsHTML();

      $retour .= '<div class="shadow5">'.LF;
      $retour .= '<div class="shadow4">'.LF;
      $retour .= '<div class="shadow3">'.LF;
      $retour .= '<div class="shadow2">'.LF;
      $retour .= '<div class="shadow">'.LF.LF;

      $retour .= '<div class="main">'.LF.LF;
      $retour .= '<table class="homepage" summary="Layout">'.LF;

      $retour .= '<tr>'.LF;
      $retour .= '<td class="header">'.LF;

      if ( !empty($logo) ) {
         $retour .= '<span class="logo">'.LF;
         $retour .= $logo;
         $retour .= LF.'</span>'.LF;
      }

      if ( !empty($header) ) {
         $retour .= '<span class="header">'.LF;
         $retour .= $header;
         $retour .= LF.'</span>'.LF;
      }

      $retour .= '</td>'.LF;
      $retour .= '<td class="empty">'.LF;
      $retour .= '</td>'.LF;
      $retour .= '</tr>'.LF;
      $retour .= '<tr>'.LF;
      $retour .= '<td class="content">'.LF;
      $retour .= '<hr class="content"/>'.LF;

      if ( !empty($error) ) {
         $retour .= '<div class="error">'.LF;
         $retour .= $error;
         $retour .= LF.'</div>'.LF;
      }
      if ( !empty($content) ) {
         $retour .= '<div class="content">'.LF;
         $retour .= $content;
         $retour .= LF.'</div>'.LF;
      }

      $retour .= '</td>'.LF;
      $current_context = $this->_environment->getCurrentContextItem();
      if ($current_context->isServer() or $current_context->isPortal()) {
         $retour .= '<td class="empty">'.LF;
         $retour .= '</td>'.LF;
      } else {
         $retour .= '<td class="navigation">'.LF;

         if ( !empty($navigation) ) {
            $retour .= '<div class="navigation">'.LF;
            $retour .= $navigation;
            $retour .= LF.'</div>'.LF;

             // actions
             $current_user = $this->_environment->getCurrentUser();
             if ($current_user->isUser()) {
               $retour .= BRLF.'<div class="navigation">'.LF;
                $retour .= $this->_getActionsAsHTML();
               $retour .= LF.'</div>'.LF;
             }

            // door
            $retour .= BRLF.'<div class="navigation_login">'.LF;
             if ($current_user->isUser()) {
               $current_context_item = $this->_environment->getCurrentContextItem();
               $retour .= '<div class="navigation_login_box">'.LF;
               $retour .= '<a href="http://www.commsy.net" target="_blank"><img src="images/commsy_logo_transparent.gif" alt="CommSy Logo, Link to CommSy Homepage" width="96" height="23" class="navigation_login_box"/></a><br/>'.LF;
               $actionCurl = ahref_curl( $this->_environment->getCurrentContextID(),
                                       'context',
                                       'forward',
                                       array('tool' => 'commsy'),
                         '<img src="images/door_open_middle.gif" alt="icon door open"/>',
                         '','_blank','','','','','',
                    'homepage.php');
               $retour .= '<table summary="Layout">'.LF;
               $retour .= '<tr>'.LF;
               $retour .= '<td class="navigation_login_box">'.$actionCurl.'</td>'.LF;
               $retour .= '<td class="navigation_login_box">'.$current_context_item->getTitle().LF;
               $retour .= '</tr>'.LF;
               $retour .= '</table>'.LF;
               $retour .= '<div style="padding: 0px 0px 4px 5px;">'.LF;
               $retour .= $current_user->getFullname().BRLF;
               $params = $this->_environment->getCurrentParameterArray();
               $params['back_tool'] = 'homepage';
               $retour .= ahref_curl($this->_environment->getCurrentContextID(), 'context', 'logout', $params,$this->_translator->getMessage('LOGOUT'),'','','','','','','','homepage.php').BRLF;
               $retour .= '</div>'.LF;
               $retour .= '</div>'.LF;
            } else {
               // login box
               $current_context_item = $this->_environment->getCurrentContextItem();
               $retour .= '<div style="width:11.0em;">'.LF;
               $retour .= '<div style="background-color: #F5F5F5; border: 2px solid rgb(213, 213, 213); padding: 0px; margin: 0px;">'.LF;
               $retour .= '<a href = "http://www.commsy.net" target="_blank"><img src="images/commsy_logo_transparent.gif" alt="CommSy Logo, Link to CommSy Homepage" width="96" height="23" style="border-width: 0px; margin-left: 5px; margin-top: 1px;"></a><br/>'.LF;
               $retour .= '<table summary="Layout"><tr><td style="padding: 5px 5px 10px 5px; vertical-align: middle;"><img src="images/door_closed_middle.gif" alt="icon door closed"/></td><td style="padding: 5px 5px 10px 5px">'.$current_context_item->getTitle().'</tr></table>'.LF;
               $retour .= '<div style="margin: 0px 3px; padding=0px;">'.LF;
               $params = array();
               $params['account'] = 'member';
               $params['cs_modus'] = 'portalmember';
               $action_url = ahref_curl($current_context_item->getItemID(),'home','index',$params,$this->_translator->getMessage('HOMEPAGE_LOGIN_NEW_ACCOUNT_LINK_HOMEPAGE'),'','','','','','','style="color:#800000;"','commsy');
               $retour .= '<span style="font-size:10pt;">-'.$action_url.'</span>'.BRLF;
               unset($params);
               $params = array();
               $params['cs_modus'] = 'account_forget';
               $action_url = ahref_curl($current_context_item->getItemID(),'home','index',$params,$this->_translator->getMessage('LOGIN_ACCOUNT_FORGET_LINK'),'','','','','','','style="color:#800000;"','commsy');
               $retour .= '<span style="font-size:10pt;">-'.$action_url.'</span>'.BRLF;
               unset($params);
               $params = array();
               $params['cs_modus'] = 'password_forget';
               $action_url = ahref_curl($current_context_item->getItemID(),'home','index',$params,$this->_translator->getMessage('LOGIN_PASSWORD_FORGET_LINK'),'','','','','','','style="color:#800000;"','commsy');
               $retour .= '<span style="font-size:10pt;">-'.$action_url.'</span>'.BRLF;
               unset($params);
               $params = array();
               $params['back_tool'] = 'homepage';
               if ($current_context_item->isPortal()) {
                  $login_context_id = $current_context_item->getItemID();
               } elseif ($current_context_item->isServer()) {
                  $login_context_id = $current_context_item->getItemID();
               } else {
                  $portal_item = $current_context_item->getContextItem();
                  $login_context_id = $portal_item->getItemID();
               }
               $retour .= '<form method="post" action="'.curl($login_context_id,'context','login',$params,'','','homepage').'" name="login" style="margin-bottom: 4px; padding=0px;">'.LF;
               unset($params);
               $retour .= '<table style="border: 1px solid rgb(213, 213, 213); margin-top: 8px; margin-bottom: 0px; margin-left: 0px; margin-right: 0px;" summary="Layout">'.LF;
               $retour .= '<tr>'.LF;
               $retour .= '<td>'.LF;
               $retour .= '<label for="user_id"><span style="font-size:8pt; color:#666;">'.$this->_translator->getMessage('COMMON_ACCOUNT').':</span></label>'.LF;
               $retour .= '</td>'.LF;
               $retour .= '<td>'.LF;
               $retour .= '<input type="text" name="user_id" style="width:5.9em;" tabindex="1"/>'.LF;
               $retour .= '</td>'.LF;
               $retour .= '</tr>'.LF;
               $retour .= '<tr>'.LF;
               $retour .= '<td>'.LF;
               $retour .= '<label for="password"><span style="font-size:8pt; color:#666;">'.$this->_translator->getMessage('COMMON_PASSWORD').':</span></label>'.LF;
               $retour .= '</td>'.LF;
               $retour .= '<td>'.LF;
               $retour .= '<input type="password" name="password" style="width:5.9em;" tabindex="2"/>'.LF;
               $retour .= '</td>'.LF;
               $retour .= '</tr>'.LF;
               $retour .= '<tr>'.LF;
               $retour .= '<td>'.LF;
               $retour .= '</td>'.LF;
               $retour .= '<td>'.LF;
               $retour .= '<input type="submit" name="option" style="width: 6.2em;" value="'.$this->_translator->getMessage('COMMON_LOGIN').'" tabindex="3"/>'.LF;
               $retour .= '</td>'.LF;
               $retour .= '</tr>'.LF;
               $retour .= '</table>'.LF;
               $retour .= '</form>'.LF;
               $retour .= '</div>'.LF;
               $retour .= '</div>'.LF;
               $retour .= '</div>'.LF;
            }
            $retour .= LF.'</div>'.LF;
         }
         $retour .= '</td>'.LF;
      }

      $retour .= '</tr>'.LF;
      $retour .= '<tr>'.LF;
      $current_context = $this->_environment->getCurrentContextItem();
      if ($current_context->isServer()) {
         $retour .= '<td class="footer">'.LF;
         $retour .= '<hr class="footer"/>'.LF;
         $retour .= '</td>'.LF;
      } else {
         $retour .= '<td class="footer">'.LF;

         if ( !empty($footer) ) {
            $retour .= '<hr class="footer"/>'.LF;
            $retour .= '<div class="footer">'.LF;
            $retour .= $footer;
            $retour .= LF.'</div>'.LF;
         }

         $retour .= '</td>'.LF;
      }
      $retour .= '<td class="empty">'.LF;
      $retour .= '</td>'.LF;
      $retour .= '</tr>'.LF;
      $retour .= '</table>'.LF;

      $retour .= LF.'</div>'.LF;

      $retour .= LF.'</div>'.LF;
      $retour .= '</div>'.LF;
      $retour .= '</div>'.LF;
      $retour .= '</div>'.LF;
      $retour .= '</div>'.LF;

      $retour .= '<div class="site_footer">'.LF;
      $retour .= '   <a href="http://www.commsy.net" target="_top" title="'.$this->_translator->getMessage('COMMSY_LINK_TITLE').'">'.'<img src="images/commsy-logo-70x14.jpg" alt="commsy logo" style="vertical-align: bottom;" />'.'</a>&nbsp;&nbsp;'.getCommSyVersion();
      $retour .= '</div>'.LF;

      return $retour;
   }

   function _getErrorAsHTML () {
      $retour = '';
      if ( !empty($this->_error_views) ) {
         $retour = $this->_getHTMLFromViews($this->_error_views);
      }
      return $retour;
   }

   function _getHeaderAsHTML () {
      $retour = '';
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->isPortal()) {
         $context_item = $this->_environment->getServerItem();
      }
      $retour .= $context_item->getTitle().LF;
      return $retour;
   }

   function _getLogoAsHTML () {
      $retour = '';

      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->isPortal()) {
         $context_item = $this->_environment->getServerItem();
      }
      $logo_filename = $context_item->getLogoFilename();
      $disc_manager = $this->_environment->getDiscManager();
      if (!empty($logo_filename) and $disc_manager->existsFile($logo_filename)) {
         $params = array();
         $params['picture'] = $context_item->getLogoFilename();
         $curl = curl($this->_environment->getCurrentContextID(), 'picture', 'getfile', $params,'');
         unset($params);
         $retour .= '<img class="logo" src="'.$curl.'" alt="'.$this->_translator->getMessage('LOGO').'" border="0"/>';
      }

      return $retour;
   }

   function _getFooterAsHTML () {
      $retour = '';

      // imprint page
      $homepage_manager = $this->_environment->getManager(CS_HOMEPAGE_TYPE);
      $imprint_page = $homepage_manager->getImprintPageItem($this->_environment->getCurrentContextID());

      if ( isset($imprint_page) ) {

         $imprint_element = array();
         $imprint_element['title'] = $this->_translator->getMessage('HOMEPAGE_TITLE_IMPRINT_PAGE');
         $imprint_element['iid'] = $imprint_page->getItemID();
         if ($imprint_page->getItemID() == $this->_shown_homepage_id) {
            $imprint_element['shown'] = true;
            $found_page = true;
         }

         ############
         # get html #
         ############

         // imprint page
         $retour .= '<div class="footer_link">'.LF;
         $link_text = $imprint_element['title'];
         if ( isset($imprint_element['shown']) and $imprint_element['shown'] ) {
            $link_text = '<span class="bold">'.$link_text.'</span>';
         }
         $params = array();
         $params['iid'] = $imprint_element['iid'];
         $retour .= ahref_curl($this->_environment->getCurrentContextID(),'homepage','detail',$params,$link_text);
         unset($params);
         $retour .= '</div>'.LF;
      }
      return $retour;
   }

   function _getNavigationAsHTML () {
      $retour = '';
      $root_element = array();
      $data_array = array();
      $data_array2 = array();
      $sub_data_array = array();
      $sub_father_item_id = NULL;
      $found_page = false;
      $grand_father_id = '';

      ############
      # get data #
      ############

      // root page
      $homepage_manager = $this->_environment->getManager(CS_HOMEPAGE_TYPE);
      $root_page = $homepage_manager->getRootPageItem($this->_environment->getCurrentContextID());

      if ( isset($root_page) ) {
         $root_element = array();
         $root_element['title'] = $this->_translator->getMessage('HOMEPAGE_TITLE_ROOT_PAGE');
         $root_element['iid'] = $root_page->getItemID();
         if (!$found_page and $root_page->getItemID() == $this->_shown_homepage_id) {
            $root_element['shown'] = true;
            $found_page = true;
         }

         // child pages of root page
         $first_child_list = $homepage_manager->getChildList($root_page->getItemID());
         if ($first_child_list->isNotEmpty()) {
            $child_page = $first_child_list->getFirst();
            while ($child_page) {
               $temp_array = array();
               $temp_array['title'] = $child_page->getTitle();
               $temp_array['iid'] = $child_page->getItemID();
               if (!$found_page and $child_page->getItemID() == $this->_shown_homepage_id) {
                  $temp_array['shown'] = true;
                  $found_page = true;
                  $grand_father_item = $child_page;
                  $grand_father_item_id = $grand_father_item->getItemID();
               }
               $data_array[] = $temp_array;
               unset($temp_array);
               $child_page = $first_child_list->getNext();
            }
         }

         // child pages of first childs
         if (!$found_page) {
            $grand_father_item = $homepage_manager->getGrandFatherItem($this->_shown_homepage_id);
            $grand_father_item_id = '';
            if ( isset($grand_father_item) ) {
               $grand_father_item_id = $grand_father_item->getItemID();
            }
         }

         ############
         # get html #
         ############

         // root page
         $retour .= '<div class="navigation_link">'.LF;
         $link_text = $root_element['title'];
         if ( isset($root_element['shown']) and $root_element['shown'] ) {
            $link_text = '<span class="bold">'.$link_text.'</span>';
         }
         $params = array();
         $params['iid'] = $root_element['iid'];
         $retour .= ahref_curl($this->_environment->getCurrentContextID(),'homepage','detail',$params,$link_text);
         unset($params);
         $retour .= '</div>'.LF;

         // root children
         if ( !empty($data_array) ) {
            $retour .= LF;
            foreach ($data_array as $child_data) {
               $retour .= '<div class="navigation_link">'.LF;
               $link_text = $child_data['title'];
               if ( isset($child_data['shown']) and $child_data['shown'] ) {
                  $link_text = '<span class="bold">'.$link_text.'</span>';
               }
               $params = array();
               $params['iid'] = $child_data['iid'];
               $retour .= ahref_curl($this->_environment->getCurrentContextID(),'homepage','detail',$params,$link_text);
               unset($params);
               $retour .= '</div>'.LF;

               // second children ?
               if ( ( !empty($grand_father_item_id) and $grand_father_item_id == $child_data['iid'] )
                    or ( isset($child_data['shown']) and $child_data['shown'] )
                  ) {
                  $retour .= $this->_getSecondChildrenAsHTML($child_data['iid']);
               }
            }
         }
      }

      return $retour;
   }

   private function _getSecondChildrenAsHTML ($page_item_id) {
      $retour = '';
      $data_array = array();
      $found_page = false;
      $homepage_manager = $this->_environment->getManager(CS_HOMEPAGE_TYPE);

      $second_child_list = $homepage_manager->getChildList($page_item_id);
      if ($second_child_list->isNotEmpty()) {
         $child_page = $second_child_list->getFirst();
         while ($child_page) {
            $temp_array = array();
            $temp_array['title'] = $child_page->getTitle();
            $temp_array['iid'] = $child_page->getItemID();
            if (!$found_page and $child_page->getItemID() == $this->_shown_homepage_id) {
               $temp_array['shown'] = true;
               $found_page = true;
            }
            $data_array[] = $temp_array;
            unset($temp_array);
            $child_page = $second_child_list->getNext();
         }
      }

      if ( !empty($data_array) ) {
         $retour .= LF;
         foreach ($data_array as $child_data) {
            $retour .= '<div class="navigation_link_second">'.LF;
            $link_text = $child_data['title'];
            if ( isset($child_data['shown']) and $child_data['shown'] ) {
               $link_text = '<span class="bold">'.$link_text.'</span>';
            }
            $params = array();
            $params['iid'] = $child_data['iid'];
            $retour .= '&nbsp;- '.ahref_curl($this->_environment->getCurrentContextID(),'homepage','detail',$params,$link_text);
            unset($params);
            $retour .= '</div>'.LF;
         }
      }
      return $retour;
   }

   /** get all the actions for this detail view as HTML
    * this method returns the actions in HTML-Code. It checks the access rights!
    *
    * @return string actions as HMTL
    */
   private function _getActionsAsHTML() {
      $user = $this->_environment->getCurrentUser();
      $mod  = $this->_with_modifying_actions;
      $html = '';
      if ( $this->_environment->getCurrentFunction() == 'detail' ) {
         $homepage_manager = $this->_environment->getManager(CS_HOMEPAGE_TYPE);
         $item = $homepage_manager->getItem($this->_shown_homepage_id);
         $action_array = array();

         // Edit the homepage item, if the current user may so
         if ( isset($item)
              and $item->mayEdit($user)
              and $mod
            ) {
            $params = array();
            $params['iid'] = $item->getItemID();
            $actionCurl = ahref_curl( $this->_environment->getCurrentContextID(),
                                      'homepage',
                                      'edit',
                                      $params,
                                      $this->_translator->getMessage('COMMON_EDIT'));
            unset($params);
            $action_array[] = $actionCurl;
         } else {
            $action_array[] = '<span class="disabled">'.$this->_translator->getMessage('COMMON_EDIT').'</span>';
         }

         if ( $user->isUser() and $mod and !$item->isImprintPage() and !$item->isSpecialPage() ) {
            $params = array();
            $params['iid'] = $item->getItemID();
            $actionCurl = ahref_curl( $this->_environment->getCurrentContextID(),
                                    'homepage',
                                    'move',
                                    $params,
                                    $this->_translator->getMessage('HOMEPAGE_MOVE_PAGE_LINK'));
            unset($params);
            $action_array[] = $actionCurl;
         } else {
            $action_array[] = '<span class="disabled">'.$this->_translator->getMessage('HOMEPAGE_MOVE_PAGE_LINK').'</span>';
         }

         if ( $user->isUser() and $mod and !$item->isImprintPage() ) {
            $params = array();
            $params['iid'] = 'NEW';
            $params['rid'] = $item->getItemID();
            $actionCurl = ahref_curl( $this->_environment->getCurrentContextID(),
                                    'homepage',
                                    'edit',
                                    $params,
                                    $this->_translator->getMessage('HOMEPAGE_ENTER_NEW_LINK'));
            unset($params);
            $action_array[] = $actionCurl;
         } else {
            $action_array[] = '<span class="disabled">'.$this->_translator->getMessage('HOMEPAGE_ENTER_NEW_LINK').'</span>';
         }

      } else {
            $action_array[] = '<span class="disabled">'.$this->_translator->getMessage('COMMON_EDIT').'</span>';
            $action_array[] = '<span class="disabled">'.$this->_translator->getMessage('HOMEPAGE_MOVE_PAGE_LINK').'</span>';
            $action_array[] = '<span class="disabled">'.$this->_translator->getMessage('HOMEPAGE_ENTER_NEW_LINK').'</span>';
      }
      if ( !empty($action_array) ) {
        $html .= implode(LF.BR.LF,$action_array);
      }
      return $html;
   }

   function _getContentAsHTML () {
      $retour = '';
      $retour = $this->_getHTMLFromViews($this->_views);
      return $retour;
   }

   /** adds a view
   * this method adds an error view to the page
   *
   * @param object cs_view a commsy view
    */
   function addErrorView ($view) {
      $this->_error_views[] = $view;
   }

   function _getHTMLFromViews ($view_array) {
      $retour = '';
      if ( !empty($view_array) ) {
         foreach ($view_array as $view) {
            if ( !$this->_with_modifying_actions ) {
                   $view->withoutModifyingActions();
                }
                $retour .= $view->asHTML();
            }
        }
      return $retour;
   }
}
?>