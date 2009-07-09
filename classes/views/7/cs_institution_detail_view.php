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

$this->includeClass(DETAIL_VIEW);
include_once('functions/curl_functions.php');

/**
 *  class for CommSy detail view: institution
 */
class cs_institution_detail_view extends cs_detail_view {

   /** constructor: cs_institution_detail_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_institution_detail_view ($params) {
      $this->cs_detail_view($params);
   }

   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item) {
      $user = $this->_environment->getCurrentUser();

      $html  = LF.'<!-- BEGIN OF INSITUTION ITEM DETAIL -->'.LF;

      $html  .='<table style="width:100%; border-collapse:collapse; border:0px solid black;" summary="Layout"><tr><td>';
      $picture = $item->getPicture();
      if ( !empty($picture) ){
         $disc_manager = $this->_environment->getDiscManager();
         if ($disc_manager->existsFile($picture)){
            $image_array = getimagesize($disc_manager->getFilePath('picture').$picture);
            $pict_width = $image_array[0];
            if ($pict_width > 150){
               $width = 150;
            }else{
               $width = $pict_width;
            }
         }else{
               $width = 150;
         }
         $params = array();
         $params['picture'] = $picture;
         $curl = curl($this->_environment->getCurrentContextID(),
                      'picture', 'getfile', $params, '');
         unset($params);
         $html .= '<img style=" width: '.$width.'px; margin-left:5px; margin-bottom:5px;" alt="Portrait" src="'.$curl.'" class="portrait2"/>'.LF;
      }
      $desc = $this->_item->getDescription();
      if ( !empty($desc) ) {
         $desc = $this->_text_as_html_long($this->_cleanDataFromTextArea($desc));
         $html .= $this->getScrollableContent($desc,$item,'',true).LF;
      }
      // Members
      $html .= '<h3>'.$this->_translator->getMessage('GROUP_MEMBERS').'</h3>'.LF;
      $html .= '<ul>'.LF;
      $members = $item->getMemberItemList();
      $context_item = $this->_environment->getCurrentContextItem();
      if ( $members->isEmpty() ) {
         $html .= '   <li><span class="disabled">'.$this->_translator->getMessage('COMMON_NONE').'</span></li>'.LF;
      } else {
         $member = $members->getFirst();
         while ($member) {
            if ( $member->isUser() ){
               $linktext = $member->getFullname();
               $member_title = $member->getTitle();
               if ( !empty($member_title) ) {
                  $linktext .= ', '.$member_title;
               }
               $html .= '   <li>';
               if ($member->maySee($user)) {
                  $params = array();
                  $params['iid'] = $member->getItemID();
                  $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                   'user',
                                   'detail',
                                   $params,
                                   $linktext);
                  unset($params);
               } else {
                  $current_user_item = $this->_environment->getCurrentUserItem();
                  if ( $current_user_item->isGuest()
                       and $member->isVisibleForLoggedIn()
                     ) {
                     $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_USER_NOT_VISIBLE').'</span>';
                  } else {
                     $html .= '<span class="disabled">'.$linktext.'</span>';
                  }
                  unset($current_user_item);
               }
               $html .= '</li>'.LF;
            }
            $member = $members->getNext();
         }
      }
      $html .= '</ul>'.LF;
      $html .= '</td></tr></table>'.LF;
      $html  .= '<!-- END OF INSITUTION ITEM DETAIL -->'.LF.LF;

      return $html;
   }

   function _is_always_visible ($rubric) {
      return true;
   }

   function _has_attach_link ($rubric) {
      return true;
   }



   function _getDetailActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      if ( $item->mayEdit($current_user) and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = $item->getItemID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit.gif" style="vertical-align:bottom;" alt="'.getMessage('COMMON_EDIT_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_EDIT_ITEM').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                          $this->_environment->getCurrentModule(),
                                          'edit',
                                          $params,
                                          $image,
                                          getMessage('COMMON_EDIT_ITEM')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit_grey.gif" style="vertical-align:bottom;" alt="'.getMessage('COMMON_EDIT_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit_grey.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_EDIT_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION').' "class="disabled">'.$image.'</a>'.LF;
      }
      $html .= $this->_getDetailItemActionsAsHTML($item).'&nbsp;&nbsp;&nbsp;';
      $params = $this->_environment->getCurrentParameterArray();
      $params['mode']='print';
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/print.gif" style="vertical-align:bottom;" alt="'.getMessage('COMMON_LIST_PRINTVIEW').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/print.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_LIST_PRINTVIEW').'"/>';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'detail',
                                    $params,
                                    $image,
                                    getMessage('COMMON_LIST_PRINTVIEW')).LF;
      unset($params['mode']);
      if ( !$this->_environment->inPrivateRoom() ) {
         if ( $current_user->isUser() and $this->_with_modifying_actions ) {
            $params = array();
            $params['iid'] = $item->getItemID();
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/mail.gif" style="vertical-align:bottom;" alt="'.getMessage('GROUPS_EMAIL_TO_GROUP').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/mail.png" style="vertical-align:bottom;" alt="'.getMessage('GROUPS_EMAIL_TO_GROUP').'"/>';
            }
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                    CS_INSTITUTION_TYPE,
                                    'mail',
                                    $params,
                                    $image,
                                    getMessage('GROUPS_EMAIL_TO_GROUP')).LF;
            unset($params);
         } else {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/mail_grey.gif" style="vertical-align:bottom;" alt="'.getMessage('GROUPS_EMAIL_TO_GROUP').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/mail_grey.png" style="vertical-align:bottom;" alt="'.getMessage('GROUPS_EMAIL_TO_GROUP').'"/>';
            }
            $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION').' "class="disabled">'.$image.'</a>'.LF;
         }
      }
      $params = $this->_environment->getCurrentParameterArray();
      $params['download']='zip';
      $params['mode']='print';
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/save.gif" style="vertical-align:bottom;" alt="'.getMessage('COMMON_DOWNLOAD').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/save.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_DOWNLOAD').'"/>';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'detail',
                                    $params,
                                    $image,
                                    getMessage('COMMON_DOWNLOAD')).LF;
      unset($params['download']);
      unset($params['mode']);
      if ( $current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = 'NEW';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new.gif" style="vertical-align:bottom;" alt="'.getMessage('COMMON_NEW_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_NEW_ITEM').'"/>';
         }
         $html .= '&nbsp;&nbsp;'.ahref_curl(  $this->_environment->getCurrentContextID(),
                                    $this->_environment->getCurrentModule(),
                                    'edit',
                                    $params,
                                    $image,
                                    getMessage('COMMON_NEW_ITEM')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/new_grey.gif" style="float:right; vertical-align:bottom;" alt="'.getMessage('COMMON_NEW_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/new_grey.png" style="float:right; vertical-align:bottom;" alt="'.getMessage('COMMON_NEW_ITEM').'"/>';
         }
         $html .= '&nbsp;&nbsp;'.'<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION').' "class="disabled">'.$image.'</a>'.LF;
      }
      return $html;
   }


   function _getDetailItemActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $context_item = $this->_environment->getCurrentContextItem();
      if ( $item->isMember($current_user) ) {
         if ( !$item->isSystemLabel() and $this->_with_modifying_actions ) {
            $params = array();
            $params['iid'] = $this->_item->getItemID();
            $params['institution_option'] = '2';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_leave.gif" style="vertical-align:bottom;" alt="'.getMessage('TOPIC_LEAVE').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_leave.png" style="vertical-align:bottom;" alt="'.getMessage('TOPIC_LEAVE').'"/>';
            }
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_INSTITUTION_TYPE,
                                       'detail',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('TOPIC_LEAVE')).LF;
            unset($params);
         } else {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_leave_grey.gif" style="vertical-align:bottom;" alt="'.getMessage('GROUP_LEAVE').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_leave_grey.png" style="vertical-align:bottom;" alt="'.getMessage('GROUP_LEAVE').'"/>';
            }
            $html .= '<a title="'.$this->_translator->getMessage('TOPIC_LEAVE').' "class="disabled">'.$image.'</a>'.LF;
         }
      } else {
         if ( !$item->isSystemLabel() and $this->_with_modifying_actions ) {
            $params = array();
            $params['iid'] = $this->_item->getItemID();
            $params['institution_option'] = '1';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_enter.gif" style="vertical-align:bottom;" alt="'.getMessage('TOPIC_ENTER').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_enter.png" style="vertical-align:bottom;" alt="'.getMessage('TOPIC_ENTER').'"/>';
            }
            $html .= ahref_curl(  $this->_environment->getCurrentContextID(),
                                       CS_INSTITUTION_TYPE,
                                       'detail',
                                       $params,
                                       $image,
                                       $this->_translator->getMessage('TOPIC_ENTER')).LF;
            unset($params);
         } else {
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/group_enter_grey.gif" style="vertical-align:bottom;" alt="'.getMessage('TOPIC_ENTER').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/group_enter_grey.png" style="vertical-align:bottom;" alt="'.getMessage('TOPIC_ENTER').'"/>';
            }
            $html .= '<a title="'.$this->_translator->getMessage('TOPIC_ENTER').' "class="disabled">'.$image.'</a>'.LF;
         }
      }
      if ( $item->mayEdit($current_user)  and $this->_with_modifying_actions and !$item->isSystemLabel()) {
         $params = $this->_environment->getCurrentParameterArray();
         $params['action'] = 'delete';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete.gif" style="vertical-align:bottom;" alt="'.getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                     $this->_environment->getCurrentModule(),
                                     'detail',
                                     $params,
                                     $image,
                                     getMessage('COMMON_DELETE_ITEM')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete_grey.gif" style="vertical-align:bottom;" alt="'.getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete_grey.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION').' "class="disabled">'.$image.'</a>'.LF;
      }
      return $html;
   }





}
?>