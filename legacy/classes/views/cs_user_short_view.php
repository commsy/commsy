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

$this->includeClass(HOME_VIEW);
include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: user
 */
class cs_user_short_view extends cs_home_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_home_view::__construct($params);
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_USER_TYPE,
                           'index',
                           '',
                           $this->_translator->getMessage('COMMON_USER_INDEX'),'','','','','','','class="head"');
      $this->setViewTitle($title);
   }

   /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    */
   function _getDescriptionAsHTML() {
      if ($this->_environment->inProjectRoom()){
      global $who_is_online;
      if (isset($who_is_online) and $who_is_online) {
       $retour = '';
         $list = $this->getList();
         $shown = $list->getCount();

       if ($shown > 0) {
          $context_item = $this->_environment->getCurrentContextItem();
          if ($context_item->isProjectRoom()) {
            $days = $context_item->getTimeSpread();
          } else {
            $days = 90;
          }
          $item = $list->getFirst();
          $count_active_now = 0;
          $this->_user_active_now_array = array();
          while ($item) {
             $lastlogin = $item->getLastLogin();
             if ($lastlogin > getCurrentDateTimeMinusMinutesInMySQL($days)) {
                 $count_active_now++;
               $this->_user_active_now_array[] = $item->getItemID();
             }
             $item = $list->getNext();
          }
       }

       $retour = ' ('.$this->_translator->getMessage('HOME_USER_SHORT_VIEW_DESCRIPTION2', $shown, $count_active_now, $this->_count_all,$days).')';
       return $retour;
     } else {
         $all = $this->getCountAll();
         $list = $this->getList();
         $shown = $list->getCount();
         return ' ('.$this->_translator->getMessage('HOME_USER_SHORT_VIEW_DESCRIPTION', $shown, $all).')';
     }
     }else{
      $all = $this->getCountAll();
      $list = $this->getList();
      $shown = $list->getCount();
      $context = $this->_environment->getCurrentContextItem();
      return ' ('.$this->_translator->getMessage('COMMON_SHORT_CONTACT_VIEW_DESCRIPTION',$shown,$all).')';
     }
   }


   /** get the name of the item
    * this method returns the item name in the right formatted style
    *
    * @return string name
    */
   function _getItemFullName($item){
      $name = $item->getFullname();

      $name_text = $name;
      $params = array();
      $params['iid'] = $item->getItemID();
      $name = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_USER_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($name_text),
                           '','', '', '', '', '', '', '',
                           CS_USER_TYPE.$item->getItemID());

      ##################################################
      # messenger - BEGIN
      ##################################################

      global $c_commsy_domain;
         $host = $c_commsy_domain;
      global $c_commsy_url_path;
      $url_to_img = $host.$c_commsy_url_path.'/images/messenger';
      #$url_to_service = '???';

      $icq_number = $item->getICQ();
      if ( !empty($icq_number) ){
         $name .= '   <img style="vertical-align:middle;" src="http://status.icq.com/online.gif?icq='.rawurlencode($icq_number).'&amp;img=5" alt="ICQ Online Status" />'.LF;
      }
      $msn_number = $item->getMSN();
      if ( !empty($msn_number) ){
         $name .= '<a href="http://www.IMStatusCheck.com/?msn">'.LF;
         $name .= '   <img style="vertical-align:middle;" src="http://www.IMStatusCheck.com/status/msn/'.rawurlencode($msn_number).'?icons" alt="MSN Online Status" />'.LF;
         $name .= '</a>'.LF;
      }
      $skype_number = $item->getSkype();
      if ( !empty($skype_number) ){
         $name .= '<script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>'.LF;
         $name .= '<a href="skype:'.rawurlencode($skype_number).'?chat">'.LF;
         $name .= '   <img src="http://mystatus.skype.com/smallicon/'.rawurlencode($skype_number).'" style="vertical-align:middle; border: none;" width="16" height="16" alt="Skype Online Status" />'.LF;
         $name .= '</a>'.LF;
      }
      $yahoo_number = $item->getYahoo();
      if ( !empty($yahoo_number) ){
         $name .= '<a href="http://messenger.yahoo.com/edit/send/?.target='.rawurlencode($yahoo_number).'">'.LF;
         $name .= '   <img style="vertical-align:middle;" src="http://opi.yahoo.com/yahooonline/u='.rawurlencode($yahoo_number).'/m=g/t=0/l='.$this->_environment->getSelectedLanguage().'/opi.jpg" alt="Yahoo Online Status Indicator" />'.LF;
         $name .= '</a>'.LF;
      }

      ##################################################
      # messenger - END
      ##################################################

      unset($params);
      if ( !$this->_environment->inPrivateRoom() ) {
         $name .= $this->_getItemChangeStatus($item);
      }
      return $name;
   }


   function _getItemEmail ($item) {
     if ($item->isEmailVisible()) {
         $email = $item->getEmail();
         $email_text = $email;
         $email = curl_mailto( $item->getEmail(), $this->_text_as_html_short(chunkText($email_text,27)));
     } else {
         $email = '<span class="disabled">'.$this->_translator->getMessage('USER_EMAIL_HIDDEN').'</span>';
     }
      return $email;
   }


   /** get the content of the list view as HTML
    * this method returns the content in HTML-Code
    *
    * @return string $this->_list as HMTL
    *
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item,$pos,$with_links=true) {
      $shown_entry_number = $pos;
      if ($shown_entry_number%2 == 0) {
         $style='class="odd"';
      } else {
         $style='class="even"';
      }
      $phone = $item->getTelephone();
      $handy = $item->getCellularphone();
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      $html  = '   <tr class="list">'.LF;
      $html .= '      <td '.$style.' style="width: 50%; font-size:10pt;">'.$this->_getItemFullname($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="width: 20%; font-size:8pt;">';
      if ( !empty($phone) ){
         $html .= $this->_text_as_html_short($phone).LF;
      }
      if (!empty($phone) and !empty($handy)) {
         $html .= BRLF;
      }
      if ( !empty($handy) ){
         $html .= $this->_text_as_html_short($handy).LF;
      }
      $html .= '</td>'.LF;
      $html .= '      <td '.$style.' style="width: 30%; font-size:8pt;">'.$this->_getItemEmail($item).'</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;
   }


   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemTitle($item){
      global $who_is_online;
      $title = $item->getFullname();
     if (isset($who_is_online) and $who_is_online) {
        if (in_array($item->getItemID(),$this->_user_active_now_array)) {
           $title = '<span style="font-weight: bold;">'.$title.'</span>';
        }
     }
      $params = array();
      $params['iid'] = $item->getItemID();;
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           'user',
                           'detail',
                           $params,
                           $title);
      unset($params);
      if ($this->_environment->inProjectRoom()) {
         $title .= $this->_getItemChangeStatus($item);
      }
      return $title;
   }
}
?>