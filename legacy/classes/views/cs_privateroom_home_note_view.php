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
   function __construct($params) {
      cs_view::__construct($params);
      $this->setViewName('note');
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $this->_view_title = $this->_translator->getMessage('COMMON_NOTE_PORTLET');
   }

   function asHTML(){
   	$room = $this->_environment->getCurrentContextItem();
      $text_converter = $this->_environment->getTextConverter();
      
      $content = str_ireplace('COMMSY_BR', "\n\r", $room->getPortletNoteContent());
      $content = str_ireplace('COMMSY_DOUBLE_QUOTE', '"', $content);
      $content = str_ireplace('COMMSY_SINGLE_QUOTE', "'", $content);
      $content = $text_converter->text_as_html_long($text_converter->cleanDataFromTextArea($content));
      
      $html = '';
      $html .= '<div id="'.get_class($this).'">'.LF;
      $html .= '<p id="portlet_note_content_p">'.$content.'</p>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }
   
   function getPreferencesAsHTML(){
   	$room = $this->_environment->getCurrentContextItem();
   	
   	$content = str_ireplace('COMMSY_DOUBLE_QUOTE', '"', $room->getPortletNoteContent());
      $content = str_ireplace('COMMSY_SINGLE_QUOTE', "'", $content);
      
      $html = '';
      $html .= '<textarea rows="10" style="width:99%;" id="portlet_note_content">'.$content.'</textarea><br/><br/>'.LF;
      $html .= '<input type="submit" id="portlet_note_save_button" value="'.$this->_translator->getMessage('COMMON_SAVE_BUTTON').'">'.LF;
      return $html;
   }
}
?>