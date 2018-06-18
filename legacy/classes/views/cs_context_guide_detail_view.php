<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

/**
 *  class for CommSy detail view: context at portal
 */
class cs_context_guide_detail_view extends cs_view {

   var $_item = NULL;

   var $_environment = NULL;

   var $_account_mode = 'none';

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
   }

   function setItem ($value) {
      $this->_item = $value;
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
   function asHTML () {
      $current_user = $this->_environment->getCurrentUserItem();

      $html  = LF.'<!-- BEGIN OF CONTEXT ITEM DETAIL -->'.LF;


         if ( !($this->_item->isServer() and $this->_environment->inServer()) and
              !($this->_item->isPortal() and $this->_environment->inPortal())
            ) {
                // title actions
                $current_user = $this->_environment->getCurrentUser();
                if ($current_user->isModerator() or $this->_item->mayEnter($current_user)) {
                       $params = array();
                       $params['iid'] = $this->_item->getItemID();
                       $html .=  '<div class="action">'.ahref_curl($this->_environment->getCurrentContextID(),'configuration','common',$params,$this->_translator->getMessage('PORTAL_EDIT_ROOM')).'</div>'.LF;
                       unset($params);
                } else {
                   $html .=  '<div class="action"><span class="disabled">'.$this->_translator->getMessage('PORTAL_EDIT_ROOM').'</span></div>'.LF;
                }
             // title
            $html .='<h2>'.$this->_translator->getMessage('PORTAL_ROOM_DESCRIPTION').'</h2>'.LF;

            // room window
            $html .= '<div class="shadow5" style="width:27em; padding:right:0px;">';
            $html .= '<div class="shadow4">';
            $html .= '<div class="shadow3">';
            $html .= '<div class="shadow2">';
            $html .= '<div style=" position:relative; top:-3px; left: -3px; text-align: left; ">'.LF;
            $html .= $this->_getRoomWindowAsHTML($this->_item,$this->getAccountMode());
            $html .='</div>';
            $html .='</div>';
            $html .='</div>';
            $html .='</div>';
            $html .='</div>';

            // description
            $desc = $this->_item->getDescription();
            if ( !empty($desc) ) {
               $desc = $this->_text_as_html_long($this->_cleanDataFromTextArea($desc));
               $html .= $desc.BR.BRLF;
            }
         } else {

                $current_user = $this->_environment->getCurrentUser();
                if ($current_user->isModerator() or $current_user->isRoot()) {
                       $html .=  '<div class="action">'.ahref_curl($this->_environment->getCurrentContextID(),'configuration','index','',$this->_translator->getMessage('ADMIN_INDEX')).'</div>'.LF;
                }

            $html .= '<div style="text-align: left;">'.LF;

                // description
            $desc = $this->_item->getDescription();
            if ( !empty($desc) ) {
               $desc = $this->_text_as_html_long($this->_cleanDataFromTextArea($desc));
               $html .= $desc.BRLF;
            }

            $formal_data = array();

            $html .= '</div>'.LF;
            $html .= BRLF;
         }
         // portal: community room announcements
         if ($this->_item->isPortal()) {
            $class_params = array();
            $class_params['environment'] = $this->_environment;
            $announcement_view = $this->_class_factory->getClass(ANNOUNCEMENT_SHORT_COMMUNITY_GUIDE_VIEW);
            unset($class_params);
            $community_manager = $this->_environment->getCommunityManager();
            $community_manager->setOpenedLimit();
            $community_manager->setOrder('activity_rev');
            $community_manager->select();
            $community_list = $community_manager->get();
            if (!$community_list->isEmpty()) {
               $announcement_view->setList($community_list);
               $html .= $announcement_view->asHTML();
            }
         }

             // actions
 #            $html .= BR.$this->_getActionsAsHTML();
         $html .= '<!-- END OF CONTEXT ITEM DETAIL -->'.LF.LF;

      return $html;
   }

   function getInfoForBodyAsHTML () {
   }

   /**
    * Internal method used for formatting tabular (formal) data.
    */
   function _getFormalDataAsHTML($data, $spacecount=0, $clear=false) {
      $prefix = str_repeat(' ', $spacecount);
      $html  = $prefix.'<table class="detail"  summary="Layout" ';
      if ( $clear ) {
         $html .= 'style="clear:both;"';
      }
      $html .= '>'."\n";
      foreach ($data as $value) {
         $html .= $prefix.'   <tr>'."\n";
         $html .= $prefix.'      <td class="key">'."\n";
         if ( !empty($value[0]) ) {
            $html .= $prefix.'         '.$value[0].':&nbsp;'."\n";
         } else {
            $html .= $prefix.'         &nbsp;';
         }
         $html .= $prefix.'      </td><td class="value">'."\n";
         if ( !empty($value[1]) ) {
            if ( !empty($value[0])) {/* AND ($value[0] == $this->_translator->getMessage('MATERIAL_ABSTRACT') OR $value[0] == $this->_translator->getMessage('MATERIAL_BIBLIOGRAPHIC')) ) {
               $html .= $prefix.'         '.$this->_text_as_html_long($value[1])."\n";
            } else {*/
               $html .= $prefix.'         '.$value[1]."\n";
            }
         }
         $html .= $prefix.'      </td>'."\n";
         $html .= $prefix.'   </tr>'."\n";
      }
      $html .= $prefix.'</table>'."\n";
      return $html;
   }

   function _getActionsAsHTML () {
      $retour = '';
      $action_array = array();
      $user = $this->_environment->getCurrentUserItem();
      if ($user->isModerator() or $user->isRoot()) {
         if ($this->_item->getItemID() == $this->_environment->getCurrentContextID()) {
                         // do nothing
         } else {
            $params = $this->_environment->getCurrentParameterArray();
            if ($this->_item->isLocked()) {
               $params['automatic'] = 'unlock';
               $message = $this->_translator->getMessage('CONTEXT_ROOM_UNLOCK');
            } else {
               $params['automatic'] = 'lock';
               $message = $this->_translator->getMessage('CONTEXT_ROOM_LOCK');
            }
            $params['iid']  = $this->_item->getItemID();
            $action_array[] = '- '.ahref_curl($this->_environment->getCurrentContextID(),
                                              'configuration',
                                              'room',
                                              $params,
                                              $message
                                             ).LF;
            unset($params);

            $params = array();
            $params['iid'] = $this->_item->getItemID();
            if ($this->_item->isLockedForMove()) {
               $action_array[] = '- <span class="disabled">'.$this->_translator->getMessage('CONTEXT_MOVE_ROOM').'</span>'.LF;
            } else {
               $action_array[] = '- '.ahref_curl($this->_environment->getCurrentContextID(),
                                                 'configuration',
                                                 'move',
                                                 $params,
                                                 $this->_translator->getMessage('CONTEXT_MOVE_ROOM')
                                                ).LF;
            }
            unset($params);
         }
      }

      if ( !empty($action_array) ) {
         $retour  = '';
         $retour .= '<table style="width: 100%; border-collapse: collapse; border: 0px;" summary="Layout">'.LF;
         $retour .= '    <tr class="head">'.LF;
         $retour .= '            <td class="head">'.LF;
         $retour .= '                    <span style="font-weight: bold;">'.$this->_translator->getMessage('COMMON_ADMINISTRATION').'</span>'.LF;
         if ($this->_item->isPortal()) {
            $retour .= '                 <span class="desc">('.$this->_translator->getMessage('PORTAL_ADMINISTRATION_DESC').')</span>'.LF;
         } else {
            $retour .= '                 <span class="desc">('.$this->_translator->getMessage('ROOM_ADMINISTRATION_DESC').')</span>'.LF;
         }
         $retour .= '            </td>'.LF;
         $retour .= '    </tr>'.LF;
         $retour .= '</table>'.LF;
         $retour .= implode(BR,$action_array);
      }
      return $retour;
   }

   /** get room window as html
    *
    * param cs_project_item project room item
    */
   function _getRoomWindowAsHTML ($item,$mode='none') {
      $current_user = $this->_environment->getCurrentUserItem();
      $may_enter = $item->mayEnter($current_user);
      $title = $item->getTitle();
      $html  = '';
      $html .= '<table class="room_window" style="width: 100%;" summary="Layout">'.LF;
      $html .= '<tr><td class="detail_view_content_room_window">'.LF.LF;
      $logo = $item->getLogoFilename();
      $html .= '<table style="width: 100%;" summary="Layout">'.LF;
      $html .= '<tr>'.LF;
      $html .= '<td>';

      // Titelzeile
      if (!empty($logo) ) {
         $html .= '<div style="float: left; padding-right: 5px;">'.LF;
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
                 $html .= '<div style="font-size: large; padding-top: 8px; padding-bottom: 8px;">'.LF;
         $html .= $this->_text_as_html_short($title);
         if ($item->isDeleted()) {
            $html .= ' ['.$this->_translator->getMessage('ROOM_STATUS_DELETED').']';
         } elseif ($item->isLocked()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_LOCKED').']'.LF;
         } elseif ($item->isProjectroom() and $item->isTemplate()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_TEMPLATE').']'.LF;
         } elseif ($item->isClosed()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_CLOSED').']'.LF;
         }
         $html .= '</div>'.LF;
      } else {
         $html .= '<div style="vertical-align: middle; font-size: large; padding-top: 8px; padding-bottom: 8px;">'.LF;
         $html .= $this->_text_as_html_short($title)."\n";

         if ($item->isLocked()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_LOCKED').']';
         } elseif ($item->isClosed()) {
            $html .= ' ['.$this->_translator->getMessage('PROJECTROOM_CLOSED').']';
         }
         $html .= '</div>';
      }
      $html .= '</td>';
      $html .= '</tr>'.LF;

      $formal_data = array();

      //Projektraum User
      $html .= '<tr>'.LF;
      $html .= '<td colspan="2">'.LF;
      $user_manager = $this->_environment->getUserManager();
      $user_manager->setUserIDLimit($current_user->getUserID());
      $user_manager->setAuthSourceLimit($current_user->getAuthSource());
      $user_manager->setContextLimit($item->getItemID());
      $user_manager->select();
      $user_list = $user_manager->get();
      if (!empty($user_list)) {
         $room_user = $user_list->getFirst();
      } else {
         $room_user = '';
      }
      $current_user = $this->_environment->getCurrentUserItem();

      //Anzeige außerhalb des Anmeldeprozesses
      if ($mode !='member' and $mode !='info' and $mode !='email'){
         $current_user = $this->_environment->getCurrentUserItem();
         $may_enter = $item->mayEnter($current_user);
        $html .= '<div style="float: right; width:12em; padding:5px; vertical-align: middle; text-align: center; xbackground-color: '.$this->_item->getTableHeaderColor().';">'.LF;

         // Eintritt erlaubt
         if ( $may_enter
                      and (
                                ( !empty($room_user)
                                          and $room_user->isUser()
                                        )
                                        or $current_user->isRoot()
                                  )
                        ) {
            $actionCurl = curl( $item->getItemID(),
                             'home',
                             'index',
                             '');
            $html .= '<a class="room_window" href="'.$actionCurl.'"><img src="images/door_open_large.gif" alt="door open" style="vertical-align: middle;"/></a>'.BRLF;
            $actionCurl = curl( $item->getItemID(),
                             'home',
                             'index',
                             '');
            $html .= '<div style="padding-top:5px; text-align: center;">'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_ENTER').'</a></div>'.LF;
            $html .= '</div>';

         //als Gast Zutritt erlaubt, aber kein Mitglied
         } elseif ( $item->isLocked() ) {
            $html .= '<img src="images/door_closed_large.gif" alt="door closed" style="vertical-align: middle; "/>'.LF;
         } elseif ( $item->isOpenForGuests()
                            and empty($room_user)
                                  ) {
            $actionCurl = curl( $item->getItemID(),
                             'home',
                             'index',
                             '');
            $html .= '<a class="room_window" href="'.$actionCurl.'"><img src="images/door_open_large.gif" alt="door open" style="vertical-align: middle;"/></a>'.BRLF;
            $actionCurl = curl( $item->getItemID(),
                             'home',
                             'index',
                             '');
            $html .= '<div style="padding-top:5px; text-align: center;">'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_ENTER_AS_GUEST').'</a></div>'.LF;
                if ($item->isOpen()) {
                           $params['account'] = 'member';
               $params['room_id'] = $this->_item->getItemID();
               $actionCurl = curl( $this->_environment->getCurrentContextID(),
                                  'home',
                                  'index',
                                  $params,
                                  '');
               $html .= '<div style="padding-top:3px; text-align: center;">'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_JOIN').'</a></div>'.LF;
                   unset($params);
                } else {
               $html .= '<div style="padding-top:3px; text-align: center;"><span class="disabled">'.$this->_translator->getMessage('CONTEXT_JOIN').'</span></div>'.LF;
                        }
            $html .= '</div>';

         //Um Erlaubnis gefragt
         } elseif ( !empty($room_user) and $room_user->isRequested() ) {
                        if ( $item->isOpenForGuests() ) {
               $actionCurl = curl( $item->getItemID(),
                                   'home',
                                   'index',
                                   '');
               $html .= '<a class="room_window" href="'.$actionCurl.'"><img src="images/door_open_large.gif" alt="door open" style="vertical-align: middle;"/></a>'.BRLF;
               $actionCurl = curl( $item->getItemID(),
                                   'home',
                                   'index',
                                   '');
               $html .= '<div style="padding-top:5px; text-align: center;">'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_ENTER_AS_GUEST').'</a></div>'.LF;
                        } else {
               $html .= '<img src="images/door_closed_large.gif" alt="door closed" style="vertical-align: middle; "/>'.LF;
                        }
            $html .= '<div style="xborder: 2px solid '.$this->_item->getTableHeaderColor().'; margin-top: 5px; padding:3px; text-align:left;">';
            $html .= '<div style="padding-top:0px; text-align: center;"><p style=" margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">'.$this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED_YET').'</p></div>'.LF;
                $html.= '</div>';

         //Erlaubnis verweigert
         } elseif ( !empty($room_user) and $room_user->isRejected() ) {
                        if ( $item->isOpenForGuests() ) {
               $actionCurl = curl( $item->getItemID(),
                                   'home',
                                   'index',
                                   '');
               $html .= '<a class="room_window" href="'.$actionCurl.'"><img src="images/door_open_large.gif" alt="door open" style="vertical-align: middle;"/></a>'.BRLF;
               $actionCurl = curl( $item->getItemID(),
                                   'home',
                                   'index',
                                   '');
                $html .= '<div style="padding-top:5px; text-align: center;">'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_ENTER_AS_GUEST').'</a></div>'.LF;
                        } else {
               $html .= '<img src="images/door_closed_large.gif" alt="door closed" style="vertical-align: middle; "/>'.LF;
                        }
            $html .= '<div style="xborder: 2px solid '.$this->_item->getTableHeaderColor().'; margin-top: 5px; padding:3px; text-align:left;">';
            $html .= '<div style="padding-top:0px; text-align: center;"><p style=" margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">'.$this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED').'</p></div>'.LF;
           $html.= '</div>';

         // noch nicht angemeldet als Mitglied im Raum
         } else {
            $html .= '<img src="images/door_closed_large.gif" alt="door closed" style="vertical-align: middle; "/>'.BRLF;
            $html .= '<div style="xborder: 2px solid '.$this->_item->getTableHeaderColor().'; margin-top: 5px; padding:3px; text-align:left;">';
                if ($item->isOpen()) {
                           $params['account'] = 'member';
               $params['room_id'] = $this->_item->getItemID();
               $actionCurl = curl( $this->_environment->getCurrentContextID(),
                                  'home',
                                  'index',
                                  $params,
                                  '');
               $html .= '<div style="padding-top:5px; text-align: center;">'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_JOIN').'</a></div>'.LF;
                   unset($params);
                } else {
               $html .= '<div style="padding-top:3px; text-align: center;"><span class="disabled">'.$this->_translator->getMessage('CONTEXT_JOIN').'</span></div>'.LF;
                        }
                $html.= '</div>';
             }
         $html .= '</div>'.LF;
             $html .= '<div>'.LF;

       // prepare moderator
        $html_temp='';
         $moda = array();
         $moda_list = $this->_item->getContactModeratorList();
         $moda_item = $moda_list->getFirst();
         while ($moda_item) {
            $html_temp .= '<li>'.$this->_text_as_html_short($moda_item->getFullName()).'</li>';
            $moda_item = $moda_list->getNext();
         }
        $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('ROOM_CONTACT').':</span>'.LF;
        $html .= '<ul style="margin-left:0px;margin-top:0.5em; spacing-left:0px; padding-top:0px;padding-left:1.5em;">'.LF;
         if (!empty($html_temp) ) {
           $temp_array = array();
           $html .= $html_temp;
#            $html .= '</ul>'.LF;
#                  $html .= '<ul style="margin-left:0px;margin-top:0.5em; spacing-left:0px; padding-top:0px;padding-left:1.5em;">'.LF;
           $params['account'] = 'email';
            $params['room_id'] = $this->_item->getItemID();
            $actionCurl = curl( $this->_environment->getCurrentContextID(),
                             'home',
                             'index',
                             $params,
                             '');
            unset($params);
            if ($current_user->isUser()){
               $html .= '<li>'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('EMAIL_CONTACT_MODERATOR').'</a></li>';
            }else{
               $html .= '<li>'.'<span class="disabled">'.$this->_translator->getMessage('EMAIL_CONTACT_MODERATOR').'</span></li>';
            }
        }else{
            $html .= '<li>'.'<span class="disabled">'.$this->_translator->getMessage('COMMON_NO_CONTACTS').'</span></li>';
         }
         $html .= '</ul>'.LF;

                // prepare time (clock pulses)
                $current_context = $this->_environment->getCurrentContextItem();
                if ( $current_context->showTime()
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

                 // community list
             if ($this->_item->isProjectRoom()) {
                        $community_list = $this->_item->getCommunityList();
                $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('COMMUNITYS').':</span>'.LF;
                    $html .= '<ul style="margin-left:0px;margin-top:0.5em;spacing-left:0px;padding-top:0px;padding-left:1.5em;">'.LF;
                        if ($community_list->isNotEmpty()) {
                           $community_item = $community_list->getFirst();
                           while ($community_item) {
                          $html .= '<li>'.LF;
                                  $params = $this->_environment->getCurrentParameterArray();
                                  $params['room_id'] = $community_item->getItemID();
                                  $link = ahref_curl($this->_environment->getCurrentContextID(),'home','index',$params,$community_item->getTitle());
                          $html .= $link.LF;
                          $html .= '</li>'.LF;
                              $community_item = $community_list->getNext();
                           }
                        } else {
                           $html .= '<li>'.LF;
                       $html .= $this->_translator->getMessage('ROOM_NOT_LINKED').LF;
                       $html .= '</li>'.LF;
                        }
                    $html .= '</ul>'.LF;
             }

       $html .= '</div>'.LF;

     // Person ist User und will Mitglied werden
     } elseif ($mode=='member' and $current_user->isUser()) {
        $translator = $this->_environment->getTranslationObject();
        $html .= '<div>'.LF;
        $formal_data = array();
        $params['room_id'] = $this->_item->getItemID();
            $html.= $this->_translator->getMessage('ACCOUNT_GET_4_TEXT');
        $html .= '<form method="post" action="'.curl($this->_environment->getCurrentContextID(),'home','index',$params).'" name="member">'.LF;
                $get_params = $this->_environment->getCurrentParameterArray();
                if ( $this->_item->withAGB() and isset($get_params['failure']) and $get_params['failure'] == 'agb' ) {
           $temp_array[0] = $this->_translator->getMessage('RUBRIC_WARN_CHANGER').': ';
               $temp_array[1] = $this->_translator->getMessage('AGB_MUST_BE_ACCEPTED_FAILURE_TEXT').LF;
               $formal_data[] = $temp_array;
                } elseif ( $this->_item->withAGB() and isset($get_params['failure']) and $get_params['failure'] == 'agb2' ) {
           $temp_array[0] = $this->_translator->getMessage('RUBRIC_WARN_CHANGER').': ';
               $temp_array[1] = $this->_translator->getMessage('ACCOUNT_SHOW_AGB_ERROR').LF;
               $formal_data[] = $temp_array;
                }

               #if ($this->_item->checkNewMembersWithCode()) {
               #   $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_CODE').': ';
               #   $temp_array[1]= '<textfield name="code" tabindex="14" />'.LF;
               #   $formal_data[] = $temp_array;
               #} else {
                   $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_REASON').': ';
                   $value = '';
                   if (!empty($get_params['description_user'])) {
                      $value = $get_params['description_user'];
                      $value = str_replace('%20',' ',$value);
                   }
                   $temp_array[1] = '<textarea name="description_user" cols="31" rows="10" wrap="virtual" tabindex="14">'.$value.'</textarea>'.LF;
                   $formal_data[] = $temp_array;
                #}

                // AGB
                if ($this->_item->withAGB()) {
                   $temp_array = array();
               $temp_array[0] = $this->_translator->getMessage('AGB_CONFIRMATION_GUIDE').'<span style="color: red;">*</span>'.':';
                   $temp_array[1] = '<input type="checkbox" name="agb" value="1"';
                   if (isset($get_params['failure']) and $get_params['failure'] == 'agb2') {
                      $temp_array[1] .= ' checked=checked';
                   }
           $temp_array[1] .= ' style="margin-left: 0px;">'.LF;
           $desc_link = ahref_curl($this->_item->getItemID(),'agb','index','',$this->_translator->getMessage('COMMON_AGB_CONFIRMATION_LINK_INPUT'),'','_help','','',' onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=yes, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"');
           $temp_array[1] .= $this->_translator->getMessage('AGB_COMFIRMATION_TEXT',$desc_link);
               $formal_data[] = $temp_array;
                }

        $temp_array = array();
            $temp_array[0] = '&nbsp;';
            $temp_array[1]= '<input type="submit" name="option" tabindex="15" value="'.$this->_translator->getMessage('MAIL_SEND_BUTTON').'"/>'.
                                 '&nbsp;&nbsp;'.'<input type="submit" name="option" tabindex="16" value="'.$this->_translator->getMessage('COMMON_BACK_BUTTON').'"/>'.LF;
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
     elseif ($mode=='member' and $current_user->isGuest()) {
        $translator = $this->_environment->getTranslationObject();
        $html .= '<div>'.LF;
                $params = $this->_environment->getCurrentParameterArray();
                $params['cs_modus'] = 'portalmember';
                $link = ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),$this->_environment->getCurrentFunction(),$params,$this->_translator->getMessage('ACCOUNT_GET_GUEST_CHOICE_LINK'));
            $html .= $this->_translator->getMessage('ACCOUNT_GET_GUEST_CHOICE',$link);
        $html .= '</div>'.LF;
     }
     elseif ( $mode=='email') {
         $translator = $this->_environment->getTranslationObject();
         $html .= '<div>'.LF;
         $formal_data = array();
         $params['room_id'] = $this->_item->getItemID();
             $html.= $this->_translator->getMessage('EMAIL_CONTACT_MODERATOR_TEXT');
         $html .= '<form method="post" action="'.curl($this->_environment->getCurrentContextID(),'home','index',$params).'" name="member">'.LF;
         $temp_array[0] = $this->_translator->getMessage('EMAIL_CONTACT_MODERATOR_TEXT_DESC').': ';
        $temp_array[1]= '<textarea name="description_user" cols="31" rows="10" wrap="virtual" tabindex="14" ></textarea>'.LF;

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
     // Person ist User und hat sich angemeldet; wurde aber nicht automatisch freigschaltet
     elseif ($mode =='info') {
        $translator = $this->_environment->getTranslationObject();
        $html .= '<div>'.LF;
        $formal_data = array();
        $params['room_id'] = $this->_item->getItemID();
        $html .= '<form method="post" action="'.curl($this->_environment->getCurrentContextID(),'home','index',$params).'" name="member">'.LF;
        $temp_array = array();
            $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_CONFIRMATION').': ';
            $temp_array[1]= $this->_translator->getMessage('ACCOUNT_GET_6_TEXT_2',$this->_item->getTitle());
            $formal_data[] = $temp_array;
        $temp_array = array();
            $temp_array[0] = '&nbsp;';
            $temp_array[1]= '<input type="submit" name="option"  value="'.$this->_translator->getMessage('COMMON_NEXT').'"/>'.LF;
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
     $html .= '</table>'.LF.LF;
     return $html;
   }

   function getInfoForHeaderAsHTML () {
      $retour = parent::getInfoForHeaderAsHTML();
      $retour .= $this->getStylesForHeaderAsHTML ();
      return $retour;
   }

   function getStylesForHeaderAsHTML () {
      return '';
   }


   function _getFormalDataAsHTML2($data, $spacecount=0, $clear=false) {
      $prefix = str_repeat(' ', $spacecount);
      $html  = $prefix.'<table class="detail" style="width: 100%;" summary="Layout">';
      if ( $clear ) {
         $html .= 'style="clear:both"';
      }
      $html .= '>'."\n";
      foreach ($data as $value) {
         if ( !empty($value[0]) ) {
            $html .= $prefix.'   <tr>'.LF;
            $html .= $prefix.'      <td style="padding: 10px 2px 10px 0px; color: #666; vertical-align: top; width: 1%;">'.LF;
            $html .= $prefix.'         '.$value[0].'&nbsp;'.LF;
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
}
?>
