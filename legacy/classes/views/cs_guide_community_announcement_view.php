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

/**
 *  class for CommSy view: community announcements on the portal
 */
class cs_guide_community_announcement_view extends cs_view {

   /**
    * list - containing the content of the list view
    */
   var $_list = NULL;
   var $_with_announcement =true;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
   }

   /** get the single entry of the list view as HTML
    * this method returns the single entry in HTML-Code
    *
    * @returns string $_list as HMTL
    *
    * @param object item     the single list entry
    */
   function asHTML () {
      $i =1;
      $retour  = LF.'<!-- BEGIN OF GUIDE COMMUNITY ANNOUNCEMENT VIEW -->'.LF;
      $retour .= '<table style=" width:95%;border-collapse: collapse; border: 0px; padding:0px; margin-left:5px; font-weight:normal;" summary="Layout">'.LF;
      $retour .= '	<tr>'.LF;
      $retour .= '<td colspan="3" style="text-align:left; padding-bottom:3px; padding-top:10px;">'.LF;
      $retour .= '<h1 class="portal_announcement_title">'.$this->_translator->getMessage('ANNOUNCEMENT_ACTUAL_TITLE').'</h1>'.LF;
      $retour .= '<span class="portal_description">('.$this->_translator->getMessage('PORTAL_ANNOUNCEMENT_FROM_COMMUNITIES_DESC').')</span>'.LF;
      $retour .= '		</td>'.LF;
      $retour .= '	</tr>'.LF;

      if ( isset($this->_list) and !$this->_list->isEmpty() ) {
         $community = $this->_list->getFirst();
         while ($community) {
         if($community->isOpenForGuests() OR $community->isUser($this->_environment->getCurrentUser()) OR $this->_environment->getCurrentUser()->isRoot()) {
               $text = '';
               $text .= '<tr>'.LF;
               $logo = $this->_getLogo($community);
               if ( !empty($logo) ) {
                  $text .= '		<td class="anouncement_background" style="vertical-align: middle; width: 10%; padding-top: 0px; padding-bottom: 0px;">'.LF;
                  $text .= $logo.LF;
                  $text .= '		</td>'.LF;
                  $text .= '		<td class="anouncement_background" style="vertical-align: middle; width: 70%; padding-top: 0px; padding-bottom: 0px; text-align:left;">'.LF;
               } else {
                  $text .= '		<td colspan="2" class="anouncement_background" style="vertical-align: middle; width: 80%; padding-top: 3px; padding-bottom:3px; padding-left:3px; text-align:left;">'.LF;
               }
               $current_user = $this->_environment->getCurrentUserItem();
               $params['room_id'] = $community->getItemID();
               $length = mb_strlen($community->getTitle());
               if ( $length > 20 and !mb_stristr($community->getTitle(),' ') ) {
                  $title = mb_substr($community->getTitle(),0,20).'...';
               } else {
                  $title = $community->getTitle();
               }
               $text .= ahref_curl($this->_environment->getCurrentContextID(),'home','index',$params,$title).LF;
               $text .= '</td><td class="anouncement_background" style="width:20%; text-align:right;">';
               if ($community->mayEnter($current_user)) {
                  $text .= '			'.ahref_curl($community->getItemID(),
                              'home',
                              'index',
                              '',
                              '<img src="images/door_open_small.gif" style="vertical-align: middle;" alt="door open"/>');
               } else {
                  $text .= '			<img src="images/door_closed_small.gif" style="vertical-align: middle;" alt="door closed"/>';
               }
               $params = array();
               $text .= '		</td>'.LF;
               $text .= '	</tr>'.LF;
               $text .= '	<tr>'.LF;
               $text .= '		<td colspan="3" style="font-size:8pt; padding-bottom:3px;">'.LF;
               $text .= '				'.$this->_getAnnouncement($community).LF;
               $text .= '		</td>'.LF;
               $text .= '	</tr>'.LF;
               if ($this->_with_announcement and $i < 4){
                  $retour .= $text;
                  $i++;
               }
         }
           $community = $this->_list->getNext();

         }
      }
      $retour .= '</table>';
      $retour .= '<!-- END OF GUIDE COMMUNITY ANNOUNCEMENT VIEW -->'.LF.LF;

      return $retour;
   }

   /** get the content of the view
    * this method gets the whole entries of the view
    *
    * @param list  $this->_list          content of the view
    *
    * @author CommSy Development Group
    */
   function getList (){
      return $this->_list;
   }

   /** set the content of the view
    * this method sets the whole entries of the view
    *
    * @param list  $this->_list          content of the view
    *
    * @author CommSy Development Group
    */
   function setList ($list){
      $this->_list = $list;
   }

   /** get the logo of the item
    * this method returns the item logo in the right formatted style
    *
    * @return string title
    */
   function _getLogo ($item) {
      $logo_description = '';
      $logo = $item->getLogoFileName();
      $disc_manager = $this->_environment->getDiscManager();
      $disc_manager->setPortalID($item->getContextID());
      $disc_manager->setContextID($item->getItemID());
      if ( !empty($logo) and $disc_manager->existsFile($logo) ) {
         $params = array();
         $params['picture'] = $item->getLogoFilename();
         $curl = curl($item->getItemID(), 'picture', 'getfile', $params);
         unset($params);
         $logo_description = '<img style="margin:0px; padding-left:3px; padding-top:3px; padding-right:3px; padding-bottom:0px; height:20px;" src="'.$curl.'" alt="'.$item->getTitle().' '.$this->_translator->getMessage('LOGO').'"/>'."\n";
         $logo_description = ahref_curl( $item->getItemID(),
                                         'home',
                                         'index',
                                         '',
                                         $logo_description);
      } else {
         $logo_description = '';
      }
      $disc_manager->setPortalID($this->_environment->getCurrentContextID());
      $disc_manager->setContextID($this->_environment->getCurrentContextID());
      return $logo_description;
   }

   /** get the most current announcement from the item
    * this method returns the most current announcement
    * from the item in the right formatted style
    *
    * @return string title
    */
   function _getAnnouncement ($item) {
      $this->_with_announcement = true;
      $announcement_manager = $this->_environment->getAnnouncementManager();
      $announcement_manager->setContextLimit($item->getItemID());
      $announcement_manager->setDateLimit(getCurrentDateTimeInMySQL());
      $announcement_manager->setOrder('modified');
      $announcement_manager->setIntervalLimit(0,1);
      $announcement_manager->select();
      $announcement_list = $announcement_manager->get();

      if (!$announcement_list->isEmpty()) {
         $announcement = $announcement_list->getFirst();
         $params['iid'] = $announcement->getItemID();

         $announcement_text = $this->_text_as_html_short($announcement->getTitle());
         $current_user = $this->_environment->getCurrentUserItem();
         if ($item->mayEnter($current_user)) {
            $announcement_text .= ahref_curl( $item->getItemID(),
                                              'announcement',
                                              'detail',
                                              $params,
                                              ' ['.$this->_translator->getMessage('COMMON_MORE').'...]');
         } else {
            $announcement_text .= ' <span class="disabled">['.$this->_translator->getMessage('COMMON_MORE').'...]</span>';
         }
      } else {
         $announcement_text = $this->_translator->getMessage('PORTAL_NO_ANNOUNCEMENTS');
         $this->_with_announcement = false;
      }
      return $announcement_text;
   }
}