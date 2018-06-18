<?PHP
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

$this->includeClass(HOME_VIEW);
include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: news
 */
class cs_context_short_view extends cs_home_view {

#   var $_max_activity = NULL;

   var $_room_type = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_home_view::__construct($params);
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_PROJECT_TYPE,
                           'index',
                           '',
                           $this->_translator->getMessage('COMMON_PROJECT_INDEX'),'','','','','','','class="head"');
      $this->setViewTitle($title);
   }


   function _getDescriptionAsHTML () {
      if ($this->_room_type == CS_PROJECT_TYPE) {
         $value  = $this->_translator->getMessage('PROJECT_SHORT_DESCRIPTION',5);
      } elseif ($this->_room_type == CS_COMMUNITY_TYPE) {
         $value  = $this->_translator->getMessage('COMMUNITY_SHORT_DESCRIPTION');
      }
      $retour = '';
      if ( !empty($value) ) {
         $retour = ' ('.$value.')';
      }
      return $retour;

   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML($item,$pos) {
      $shown_entry_number = $pos;
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      $html  = '   <tr class="list">'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).'</td>'.LF;
      $html .= '      <td  '.$style.' style="font-size:8pt;">'.$this->_getItemActivity($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getItemTitle ($item) {
      $title = $item->getTitle();
      $params = array();
      $params['iid'] = $item->getItemID();

      $current_user = $this->_environment->getCurrentUserItem();
      $user_manager = $this->_environment->getUserManager();
      $user_manager->setUserIDLimit($current_user->getUserID());
      $user_manager->setAuthSourceLimit($current_user->getAuthSource());
      $user_manager->setContextLimit($item->getItemID());
      $user_manager->select();
      $user_list = $user_manager->get();
      if (!empty($user_list)){
         $room_user = $user_list->getFirst();
      } else {
         $room_user ='';
      }
     if ($current_user->isRoot()) {
         $may_enter = true;
     } elseif (!empty($room_user)){
         $may_enter = $item->mayEnter($room_user);
      } else {
         $may_enter = false;
      }
      if ($may_enter) {
         $html = ahref_curl($item->getItemID(), 'home',
                                        'index',
                                        '',
                                        '<img src="images/door_open_small.gif" style="vertical-align: middle" alt="door open"/>').LF;
      } else {
       $html = '<img src="images/door_closed_small.gif" style="vertical-align: middle" alt="door closed"/>'.LF;
     }
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           $this->_room_type,
                           'detail',
                           $params,
                           $this->_text_as_html_short($title));
      unset($params);
      return $html.' '.$title;
   }

   /** get the activity of the item
    * this method returns the item activity in the right formatted style
    *
    * @return string title
    */
   function _getItemActivity ($item) {
      if ( $this->_max_activity != 0 ) {
         $percentage = $item->getActivityPoints();
         if ( empty($percentage) ) {
            $percentage = 0;
         } else {
           $teiler = $this->_max_activity/20;
            $percentage = log(($percentage/$teiler)+1);
          if ($percentage < 0) {
            $percentage = 0;
          }
          $max_activity = log(($this->_max_activity/$teiler)+1);
            $percentage = round(($percentage / $max_activity) * 100,2);
         }
      } else {
         $percentage = 0;
     }
      $display_percentage = $percentage;
      $html = '         <div class="gauge" style="height:5px;">'.LF;
      $html .= '            <div class="gauge-bar" style="height:5px; width:'.$display_percentage.'%;">&nbsp;</div>'.LF;
      $html .= '         </div>'.LF;

      return $html;
   }
}
?>