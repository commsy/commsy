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

/**
 *  class for CommSy detail view: contexts
 */
class cs_context_detail_view extends cs_detail_view {

var $_account_mode = 'none';
var $_room_title_color = 'black';
var $_room_type = 'context';

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_detail_view::__construct($params);
      $this->_show_content_without_window = true;
   }

   function setAccountMode($mode){
      $this->_account_mode = $mode;
   }

     function getAccountMode(){
      return $this->_account_mode;
   }

   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $item as HMTL
    *
    * @param object item     the single list entry
    */
   function _getItemAsHTML ($item) {
      $html  = LF.'<!-- BEGIN OF CONTEXT ITEM DETAIL -->'.LF;
      if ( $this->_show_content_without_window ) {
         $html .= $this->_getRoomWindowAsHTML($item,$this->getAccountMode());
      } else {
         $current_user = $this->_environment->getCurrentUserItem();
         // description
         $desc = $this->_item->getDescription();
         if ( !empty($desc) ) {
            $desc = $this->_text_as_html_long($this->_compareWithSearchText($this->_cleanDataFromTextArea($desc)));
            $html .= $desc.LF;
         }
         $html .= '<div style="width:100%; padding-left:15%; padding-bottom:20px; vertical-align:center;">'.$this->_getRoomWindowAsHTML($item,$this->getAccountMode()).'</div>';
      }
      $html .= '<!-- END OF CONTEXT ITEM DETAIL -->'.LF.LF;
      return $html;
   }

   function _getPrintableItemAsHTML($item) {
      return $this->_getItemAsHTML($item);
   }

   function _getDetailActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= $this->_getDetailItemActionsAsHTML($item);
      $html .= $this->_getAdditionalActionsAsHTML($item);
      $html .= $this->_getPrintAction($item,$current_user);

      if ( !$this->_environment->inPrivateRoom() ) {
         $html .= $this->_getMailAction($item,$current_user);
      }
      if ( $this->_environment->inPrivateRoom() ) {
         /*
         $portal_user = $current_user->getRelatedCommSyUserItem();
         $current_portal_item = $this->_environment->getCurrentPortalItem();
         if ( isset($current_portal_item)
              and $current_portal_item->openProjectRoomOnlyInCommunityRoom()
              and $current_portal_item->openCommunityRoomOnlyByModeration()
              and isset($portal_user)
              and !$portal_user->isModerator()
            ) {
            $html .= $this->_getNewActionDisabled();
         } else {
            $html .= $this->_getNewAction($item,$current_user);
         }
         */
      } else {
         $html .= $this->_getNewAction($item,$current_user);
      }
      return $html;
   }

   function _getFormalDataAsHTML2($data, $spacecount=0, $clear=false) {
      $prefix = str_repeat(' ', $spacecount);
      $html  = $prefix.'<table class="detail" style="width: 100%;" summary="Layout"';
      if ( $clear ) {
         $html .= 'style="clear:both"';
      }
      $html .= '>'."\n";
      foreach ($data as $value) {
         if ( !empty($value[0]) ) {
            $html .= $prefix.'   <tr>'.LF;
            $html .= $prefix.'      <td style="padding: 10px 2px 10px 0px; color: #666; vertical-align: top; width: 1%;">'.LF;
            $html .= $prefix.'         '.$value[0].LF;
         } else {
            $html .= $prefix.'         &nbsp;';
         }
         $html .= $prefix.'      </td><td style="margin: 0px; padding: 10px 2px 10px 0px;">'.LF;
         if ( !empty($value[1]) ) {
            $html .= $prefix.'         '.$value[1].LF;
         }
         $html .= $prefix.'      </td>'.LF;
         $html .= $prefix.'   </tr>'.LF;
      }
      $html .= $prefix.'</table>'.LF;
      return $html;
   }

   /** get room window as html
    *
    * param cs_project_item project room item
    */
   function _getRoomWindowAsHTML ($item, $mode) {
      $current_user = $this->_environment->getCurrentUserItem();
      $may_enter = $item->mayEnter($current_user);
      $color_array = $item->getColorArray();
      $title = $item->getTitle();
      $html  = '';
      if ( $this->_show_content_without_window ) {
         $html = '<table class="room_window" style="margin-left:0px; width: 100%; border-collapse:collapse; border: 1px solid '.$color_array['tabs_background'].'; margin-top:1px;" summary="Layout">'.LF;
      } else {
         $html .= '<table class="room_window" style="width: 70%; border-collapse:collapse; border: 2px solid '.$color_array['tabs_background'].';';
         $html .= '" summary="Layout">'.LF;
      }
      $html .= '<tr><td style="padding:0px;">'.LF.LF;
      $logo = $item->getLogoFilename();
      $html .= '<table style="width: 100%; padding:0px; border-collapse:collapse;" summary="Layout">'.LF;
      $html .= '<tr>'.LF;
      $html .= '<td style="background-color:'.$color_array['tabs_background'].'; padding:0px; border-right: 1px solid '.$color_array['tabs_background'].';">';

      // Titelzeile
      if (!empty($logo) ) {
         $html .= '<div style="background-color:'.$color_array['tabs_background'].'; float: left; padding: 2px;">'.LF;
         $params = array();
         $params['picture'] = $item->getLogoFilename();
         $curl = curl($item->getItemID(), 'picture', 'getfile', $params,'');
         unset($params);
         if (!$may_enter) {
            $html .= '      <img class="logo_small" src="'.$curl.'" alt="'.$this->_translator->getMessage('LOGO').'" border="0"/>'.LF;
         } else {
            $html .= ahref_curl($item->getItemID(),'home','index','','<img class="logo_small" src="'.$curl.'" alt="'.$this->_translator->getMessage('LOGO').'" border="0"/>');
         }
         $html .= '</div>'.LF;
         if ( $this->_show_content_without_window ) {
            $html .= '<div style="background-color:'.$color_array['tabs_background'].'; color:'.$color_array['tabs_title'].'; font-size: 14pt; padding: 5px 0px 5px 0px;">'.LF;
         } else {
            $html .= '<div style="background-color:'.$color_array['tabs_background'].'; color:'.$color_array['tabs_title'].'; font-size: 14pt; padding-top: 8px; padding-bottom: 8px;">'.LF;
         }
         $html .= $this->_text_as_html_short($this->_compareWithSearchText($title,false));
         if ($item->isLocked()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_LOCKED').']'.LF;
         } elseif ($item->isProjectroom() and $item->isTemplate()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_TEMPLATE').']'.LF;
         } elseif ($item->isClosed()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_CLOSED').']'.LF;
         }
         $html .= '</div>'.LF;
      } else {
         if ( $this->_show_content_without_window ) {
            $html .= '<h2 class="contenttitle" style="background-color:'.$color_array['tabs_background'].'; color: '.$color_array['tabs_title'].'; padding: 4px 4px 4px 2px;">'.LF;
         } else {
            $html .= '<div style="background-color:'.$color_array['tabs_background'].';  color:'.$color_array['tabs_title'].'; vertical-align: large; font-size: large; padding-top: 8px; padding-bottom: 8px;">'.LF;
         }
         $html .= $this->_text_as_html_short($title).LF;

         if ($item->isLocked()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_LOCKED').']';
         } elseif ($item->isClosed()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_CLOSED').']';
         }
         if ( $this->_show_content_without_window ) {
            $html .= '</h2>'.LF;
         } else {
            $html .= '</div>'.LF;
         }
      }
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;

      $formal_data = array();

      //Projektraum User
      $html .= '<tr>'.LF;
      $html .= '<td colspan="2" class="room_content_fader" style="border-right: 1px solid '.$color_array['tabs_background'].'; padding: 5px 10px 5px 10px;">'.LF;
      $user_manager = $this->_environment->getUserManager();
      $user_manager->setUserIDLimit($current_user->getUserID());
      $user_manager->setAuthSourceLimit($current_user->getAuthSource());
      $user_manager->setContextLimit($item->getItemID());
      $user_manager->select();
      $user_list = $user_manager->get();
      if (!empty($user_list)){
         $room_user = $user_list->getFirst();
      } else {
         $room_user = '';
      }
      $current_user = $this->_environment->getCurrentUserItem();

      //Anzeige außerhalb des Anmeldeprozesses
      if (($mode !='member' and $mode !='info' and $mode !='email') or !$item->isOpen()){
         $current_user = $this->_environment->getCurrentUserItem();
         if ($current_user->isRoot()) {
            $may_enter = true;
         } elseif ( !empty($room_user) ) {
            $may_enter = $item->mayEnter($room_user);
         } else {
            $may_enter = false;
         }
         $html .= '<div style="float:right; width:15em; padding:5px; vertical-align: middle; text-align: center;">'.LF;

         // Eintritt erlaubt
         if ($may_enter) {
            $actionCurl = curl( $item->getItemID(),
                             'home',
                             'index',
                             '');
            if (!$this->isPrintableView()) {
               $html .= '<a class="room_window" href="'.$actionCurl.'"><img alt="door" src="images/door_open_large.gif"/></a>'.BRLF;
            } else {
               $html .= '<img alt="door" src="images/door_open_large.gif" style="vertical-align: large;"/>'.BRLF;
            }
            if ($item->isOpen()) {
               $actionCurl = curl( $item->getItemID(),
                                'home',
                                'index',
                                '');
               $html .= '<div style="margin-top: 5px; padding:3px; text-align:left;">';
               if (!$this->isPrintableView()) {
                 $html .= '<div style="padding-top:5px; text-align: center;">'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_ENTER').'</a></div>'.LF;
               } else {
                  $html .= '<div style="padding-top:5px; text-align: center;">'.$this->_translator->getMessage('CONTEXT_ENTER').'</div>'.LF;
               }
            } else {
               $html .= '<div style="padding-top:3px; text-align: center;"><span class="disabled">'.$this->_translator->getMessage('CONTEXT_JOIN').'</span></div>'.LF;
            }
            $html .= '</div>';

         } elseif ( $item->isLocked() ) {
            $html .= '<img alt="door" src="images/door_closed_large.gif" style="vertical-align: middle; "/>'.LF;
         //Um Erlaubnis gefragt
         } elseif(!empty($room_user) and $room_user->isRequested()) {
            $html .= '<img alt="door" src="images/door_closed_large.gif" style="vertical-align: large; "/>'.LF;
            $html .= '<div style="xborder: 2px solid '.$color_array['tabs_background'].'; margin-top: 5px; padding:3px; text-align:left;">';
            $html .= '<div style="padding-top:0px; text-align: center;"><p style=" margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">'.$this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED_YET').'</p></div>'.LF;
           $html.= '</div>';

         //Erlaubnis verweigert
         } elseif(!empty($room_user) and $room_user->isRejected()) {
            $html .= '<img alt="door" src="images/door_closed_large.gif" style="vertical-align: large; "/>'.LF;
            $html .= '<div style="xborder: 2px solid '.$color_array['tabs_background'].'; margin-top: 5px; padding:3px; text-align:left;">';
            $html .= '<div style="padding-top:0px; text-align: center;"><p style=" margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">'.$this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED').'</p></div>'.LF;
           $html.= '</div>';

         // noch nicht angemeldet als Mitglied im Raum
         } else {
            $html .= '<img alt="door" src="images/door_closed_large.gif" style="vertical-align: middle text-align:left;"/>'.BRLF;
            $html .= '<div style="xborder: 2px solid '.$color_array['tabs_background'].'; margin-top: 5px; padding:3px; text-align:center;">';
            $current_user_item_read = $this->_environment->getCurrentUserItem();
            if ( $item->isOpen()
                 and !$current_user_item_read->isOnlyReadUser()
               ) {
               if ( $this->_environment->inPortal() ) {
                  $params['account'] = 'member';
                  $params['room_id'] = $this->_item->getItemID();
                  $actionCurl = curl( $this->_environment->getCurrentPortalID(),
                                      'home',
                                      'index',
                                      $params,
                                      '');
               } else {
                  $params['account'] = 'member';
                  $params['iid'] = $this->_item->getItemID();
                  $actionCurl = curl( $this->_environment->getCurrentContextID(),
                                      $this->_environment->getCurrentModule(),
                                      $this->_environment->getCurrentFunction(),
                                      $params,
                                      '');
               }
               if (!$this->isPrintableView()) {
                  $html .= '<div style="padding-top:5px; text-align: center;">'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_JOIN').'</a></div>'.LF;
               } else {
                  $html .= '<div style="padding-top:5px; text-align: center;">'.$this->_translator->getMessage('CONTEXT_JOIN').'</div>'.LF;
               }
               unset($params);
            } else {
               $html .= '<div style="padding-top:3px; text-align: center;"><span class="disabled">'.$this->_translator->getMessage('CONTEXT_JOIN').'</span></div>'.LF;
            }
            $html.= '</div>';
         }
         $html .= '</div>'.LF;

         // description
         if ( $this->_show_content_without_window ) {
            $html .= '<div style="padding-bottom: 10px;">'.LF;
            $desc = $this->_item->getDescription();
            if ( !empty($desc) ) {
               $desc = $this->_text_as_html_long($this->_compareWithSearchText($this->_cleanDataFromTextArea($desc)));
               $html .= $desc.LF;
            }
            $html .= '</div>'.LF;
         }

         // prepare moderator
         $html .= '<div>'.LF;
         $html_temp='';
         $moda = array();
         $moda_list = $this->_item->getContactModeratorList();
         $moda_item = $moda_list->getFirst();
         while ($moda_item) {
            $moda_item_here = $moda_item->getRelatedUserItemInContext($this->_environment->getCurrentContextID());
            $current_user_item = $this->_environment->getCurrentUserItem();
            if ( $current_user_item->isGuest()
                 and isset($moda_item_here)
                 and $moda_item_here->isVisibleForLoggedIn()
               ) {
               $html_temp .= '<li>'.$this->_translator->getMessage('COMMON_USER_NOT_VISIBLE').'</li>';
            } else {
               $html_temp .= '<li>'.$this->_text_as_html_short($this->_compareWithSearchText($moda_item->getFullName())).'</li>';
            }
            unset($current_user_item);
            $moda_item = $moda_list->getNext();
         }

         $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('ROOM_CONTACT').':</span>'.LF;
         $html .= '<ul style="margin-left:0px;margin-top:0.5em; spacing-left:0px; padding-top:0px;padding-left:1.5em;">'.LF;
         if (!empty($html_temp) ) {
            $temp_array = array();
            $html .= $html_temp;
            $params['account'] = 'email';
            $params['iid'] = $this->_item->getItemID();
            $actionCurl = curl( $this->_environment->getCurrentContextID(),
                             $this->_environment->getCurrentModule(),
                             'detail',
                             $params,
                             '');
            unset($params);
            if ( !$current_user->isReallyGuest() ) {
               if (!$this->isPrintableView()) {
                  $html .= '<li>'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('EMAIL_CONTACT_MODERATOR').'</a></li>';
               }
               else {
                  $html .= '<li>'.$this->_translator->getMessage('EMAIL_CONTACT_MODERATOR').'</li>';
               }
            } else {
               $html .= '<li>'.'<span class="disabled">'.$this->_translator->getMessage('EMAIL_CONTACT_MODERATOR').'</span></li>';
            }
         } else {
            $html .= '<li>'.'<span class="disabled">'.$this->_translator->getMessage('COMMON_NO_CONTACTS').'</span></li>';
         }
         $html .= '</ul>'.LF;
         $html .= '</div>'.LF;

      // prepare time (clock pulses)
      $current_context = $this->_environment->getCurrentContextItem();
      $current_portal = $this->_environment->getCurrentPortalItem();
      if ( $current_portal->showTime()
           and ( $this->_item->isProjectRoom()
                or $this->_item->isCommunityRoom() )
         ) {
         $time_list = $this->_item->getTimeList();
         if ($time_list->isNotEmpty()) {
            $this->translatorChangeToPortal();
            $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('COMMON_TIME_NAME').':</span>'.LF;
            $this->translatorChangeToCurrentContext();
            if ($this->_item->isContinuous()) {
               $time_item = $time_list->getFirst();
               if ($this->_item->isClosed()) {
                  $time_item_last = $time_list->getLast();
                  if ($time_item_last->getItemID() == $time_item->getItemID()) {
                     $html .= '<ul style="margin-left:0px;margin-top:0.5em; spacing-left:0px; padding-top:0px;padding-left:1.5em;">'.LF;
                     $html .= '   <li>'.LF;
                     $html .= $this->_translator->getTimeMessage($time_item->getTitle()).LF;
                     $html .= '   </li>'.LF;
                     $html .= '</ul>'.LF;
                  } else {
                     $html .= '<ul style="margin-left:0px;margin-top:0.5em; spacing-left:0px; padding-top:0px;padding-left:1.5em;">'.LF;
                     $html .= '   <li>'.LF;
                     $html .= $this->_translator->getMessage('COMMON_FROM2').' '.$this->_translator->getTimeMessage($time_item->getTitle()).LF;
                     $html .= '   </li>'.LF;
                     $html .= '   <li>'.LF;
                     $html .= $this->_translator->getMessage('COMMON_TO').' '.$this->_translator->getTimeMessage($time_item_last->getTitle()).LF;
                     $html .= '   </li>'.LF;
                     $html .= '</ul>'.LF;
                  }
               } else {
                  $html .= '<ul style="margin-left:0px;margin-top:0.5em; spacing-left:0px; padding-top:0px;padding-left:1.5em;">'.LF;
                  $html .= '   <li>'.LF;
                  $html .= $this->_translator->getMessage('ROOM_CONTINUOUS_SINCE').' '.BRLF.$this->_translator->getTimeMessage($time_item->getTitle()).LF;
                  $html .= '   </li>'.LF;
                  $html .= '</ul>'.LF;
               }
            } else {
               $html .= '<ul style="margin-left:0px;margin-top:0.5em; spacing-left:0px; padding-top:0px;padding-left:1.5em;">'.LF;
               $time_item = $time_list->getFirst();
               while ($time_item) {
                  $html .= '<li>'.$this->_translator->getTimeMessage($time_item->getTitle()).'</li>'.LF;
                  $time_item = $time_list->getNext();
               }
               $html .= '</ul>'.LF;
            }
         } else {
            $this->translatorChangeToPortal();
            $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('COMMON_TIME_NAME').':</span>'.LF;
            $this->translatorChangeToCurrentContext();
            $html .= '<ul style="margin-left:0px;margin-top:0.5em; spacing-left:0px; padding-top:0px;padding-left:1.5em;">'.LF;
            $html .= '   <li>'.LF;
            $html .= $this->_translator->getMessage('ROOM_NOT_LINKED').LF;
            $html .= '   </li>'.LF;
            $html .= '</ul>'.LF;
         }
      }

      // add-ons
      if ( $item->showHomepageDescLink() or
            ( $item->showWikiLink()
              and $item->existWiki()
              and $item->issetWikiPortalLink()
            )
         ) {
         $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('COMMON_PORTAL_LINKS').':</span>'.LF;
         $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1.5em;">'.LF;

         if (
               ( $item->showWikiLink()
                 and $item->existWiki()
                 and $item->issetWikiPortalLink()
               )
            ) {
            $html .= '<li style="font-weight:normal;">'.LF;
            global $c_pmwiki_path_url;
            $url_session_id = '';
            if ( $item->withWikiUseCommSyLogin() ) {
               $session_item = $this->_environment->getSessionItem();
               $url_session_id = '?commsy_session_id='.$session_item->getSessionID();
               unset($session_item);
            }
            $html .= '<span style="white-space:nowrap;"> <a href="'.$c_pmwiki_path_url.'/wikis/'.$item->getContextID().'/'.$item->getItemID().'/'.$url_session_id.'" target="_blank">'.$item->getWikiTitle().'</a> ('.$this->_translator->getMessage('COMMON_WIKI_LINK').')</span>';
            $html .= '</li>'.LF;
         }

         if ( $item->showHomepageDescLink() ) {
            $html .= '<li style="font-weight:normal;">'.LF;
            $link = ahref_curl( $item->getitemID(),
                                'context',
                                'forward',
                                array('tool' => 'homepage'),
                                $this->_translator->getMessage('HOMEPAGE_HOMEPAGE'),'','_blank');
            $html .= '<span style="white-space:nowrap;"> '.$link.'</span>';
            $html .= '</li>'.LF;
         }

         $html .= '</ul>'.LF;
      }


      //Person ist User und will Mitglied werden
      } elseif ($mode == 'member'
                 and ( $current_user->isUser()
                       or ( $current_user->getUserID() != 'guest'
                            and ( $current_user->isGuest()
                                  or $current_user->isRequested()
                                )
                          )
                     )
               ) {
         $translator = $this->_environment->getTranslationObject();
         $html .= '<div>'.LF;
         $formal_data = array();
         $params['iid'] = $this->_item->getItemID();
         $html .= '<form method="post" action="'.curl($this->_environment->getCurrentContextID(),CS_PROJECT_TYPE,'detail',$params).'" name="member">'.LF;
         $get_params = $this->_environment->getCurrentParameterArray();
         if ($this->_item->checkNewMembersWithCode()) {
            $html .= $this->_translator->getMessage('ACCOUNT_GET_CODE_TEXT');
            if ( isset($get_params['error']) and !empty($get_params['error']) ) {
               $temp_array[0] = $this->_translator->getMessage('COMMON_ATTENTION').': ';
               $temp_array[1] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_CODE_ERROR');
               $formal_data[] = $temp_array;
            }
            $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_CODE').': ';
            $temp_array[1] = '<input type="text" name="code" tabindex="14" size="30"/>'.LF;
            $formal_data[] = $temp_array;
         } else {
            $html .= $this->_translator->getMessage('ACCOUNT_GET_4_TEXT');
            $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_REASON').': ';
            $temp_array[1]= '<textarea name="description_user" cols="40" rows="10" wrap="virtual" tabindex="14" ></textarea>'.LF;
            $formal_data[] = $temp_array;
         }

         $temp_array = array();
         $temp_array[0] = '&nbsp;';
         $temp_array[1]= '<input type="submit" name="option" tabindex="15" value="'.$this->_translator->getMessage('ACCOUNT_GET_MEMBERSHIP_BUTTON').'"/>'.
                       '&nbsp;&nbsp;'.'<input type="submit" name="option" tabindex="16" value="'.$this->_translator->getMessage('COMMON_BACK_BUTTON').'"/>'.LF;
         $formal_data[] = $temp_array;
         if ( !empty($formal_data) ) {
            $html .= $this->_getFormalDataAsHTML2($formal_data);
            $html .= BRLF;
         }
         unset($params);
         $html .= '</form>'.LF;
         $html .= '</div>'.LF;

      } elseif ( $mode=='email') {
         $translator = $this->_environment->getTranslationObject();
         $html .= '<div>'.LF;
         $formal_data = array();
         $params['iid'] = $this->_item->getItemID();
         $html.= $this->_translator->getMessage('EMAIL_CONTACT_MODERATOR_TEXT');
         $html .= '<form method="post" action="'.curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params).'" name="member">'.LF;
         $temp_array[0] = $this->_translator->getMessage('EMAIL_CONTACT_MODERATOR_TEXT_DESC').': ';
         $temp_array[1]= '<textarea name="description_user" cols="50" rows="10" wrap="virtual" tabindex="14" ></textarea>'.LF;
         $formal_data[] = $temp_array;
         $temp_array = array();
         $temp_array[0] = '&nbsp;';
         $temp_array[1]= '<input type="submit" name="option"  value="'.$this->_translator->getMessage('CONTACT_MAIL_SEND_BUTTON').'"/>'.
                       '&nbsp;&nbsp;'.'<input type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_BACK_BUTTON').'"/>'.LF;
         $formal_data[] = $temp_array;
         if ( !empty($formal_data) ) {
            $html .= $this->_getFormalDataAsHTML2($formal_data);
            $html .= BRLF;
         }
         unset($params);
         $html .= '</form>'.LF;
         $html .= '</div>'.LF;
      }

    // person is guest und will Mitglied werden
     elseif ($mode == 'member' and $current_user->isGuest() and $current_user->getUserID() == 'guest') {
        $translator = $this->_environment->getTranslationObject();
        $html .= '<div>'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      $params['cs_modus'] = 'portalmember';
      $link = ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,$this->_translator->getMessage('ACCOUNT_GET_GUEST_CHOICE_LINK'));
       $html .= $this->_translator->getMessage('ACCOUNT_GET_GUEST_CHOICE',$link);
        $html .= '</div>'.LF;
     }

      //Person ist User und hat sich angemeldet; wurde aber nicht automatisch freigschaltet
      elseif ($mode =='info') {
         $translator = $this->_environment->getTranslationObject();
         $html .= '<div>'.LF;
         $formal_data = array();
         $params['iid'] = $this->_item->getItemID();
         $html .= '<form method="post" action="'.curl($this->_environment->getCurrentContextID(),CS_PROJECT_TYPE,'detail',$params).'" name="member">'.LF;
         $temp_array = array();
        $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_CONFIRMATION').': ';
        $temp_array[1]= $this->_translator->getMessage('ACCOUNT_GET_6_TEXT_2',$this->_item->getTitle());
        $formal_data[] = $temp_array;
         $temp_array = array();
        $temp_array[0] = '&nbsp;';
        $temp_array[1]= '<input type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_NEXT').'"/>'.LF;
        $formal_data[] = $temp_array;
        if ( !empty($formal_data) ) {
           $html .= $this->_getFormalDataAsHTML2($formal_data);
           $html .= BRLF;
        }
         unset($params);
         $html .= '</form>'.LF;
         $html .= '</div>'.LF;
      }

      $html .= '</td></tr>'.LF;
      $html .= '</table>'.LF.LF;

      $html .= '</td></tr>'.LF;

      // creator, modificator infos
      if ( $this->_show_content_without_window ) {
         $html .= '<tr><td colspan="2" style="padding: 5px 10px 5px 10px;">'.LF;
         if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
            $html .='<div class="infoborder" style="margin-top:5px; padding-top:10px; vertical-align:top;">';
            $mode = 'short';
            if (in_array($this->_item->getItemID(),$this->_openCreatorInfo)) {
               $mode = 'long';
            }
            $html .= $this->_getCreatorInformationAsHTML($this->_item, 3,$mode).LF;
            $html .= '</div>'.LF;
         }
         $html .= '</td></tr>'.LF;
      }

      $html .= '</table>'.LF.LF;

      return $html;
   }

   function getInfoForHeaderAsHTML () {
      return parent::getInfoForHeaderAsHTML();
   }
}
?>