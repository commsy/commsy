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

/** upper class of the form view
 */
$this->includeClass(VIEW);

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_privateroom_home_note_view extends cs_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_privateroom_home_note_view ($params) {
      $this->cs_view($params);
      $this->setViewName('note');
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $this->_view_title = $this->_translator->getMessage('COMMON_NOTE_PORTLET');
   }

   function asHTML(){
   	$room = $this->_environment->getCurrentContextItem();
      $html = '';
      $html .= '<p id="portlet_note_content_p">'.$room->getPortletNoteContent().'</p>';
      $html .= '<input type="hidden" id="portlet_note_content_p_hidden" style="display:none;" value="'.$room->getPortletNoteContent().'">';
      return $html;
   }
   
   function getPreferencesAsHTML(){
      $html = '';
      $html .= '<textarea rows="10" style="width:99%;" id="portlet_note_content"></textarea><br/><br/>';
      $html .= '<input type="submit" id="portlet_note_save_button" value="'.$this->_translator->getMessage('PORTLET_CONFIGURATION_NOTE').'">';
      return $html;
   }
}
?>