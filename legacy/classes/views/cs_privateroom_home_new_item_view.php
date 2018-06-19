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
class cs_privateroom_home_new_item_view extends cs_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->setViewName('new_item');
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $this->_view_title = $this->_translator->getMessage('COMMON_NEW_ITEM_PORTLET');
   }

   function asHTML(){
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image_new_material = '<img src="images/commsyicons_msie6/22x22/material.gif" style="vertical-align:bottom;"/>';
         $image_new_date = '<img src="images/commsyicons_msie6/22x22/date.gif" style="vertical-align:bottom;"/>';
         $image_new_discussion = '<img src="images/commsyicons_msie6/22x22/discussion.gif" style="vertical-align:bottom;"/>';
         $image_new_todo = '<img src="images/commsyicons_msie6/22x22/todo.gif" style="vertical-align:bottom;"/>';
      } else {
         $image_new_material = '<img src="images/commsyicons/22x22/material.png" style="vertical-align:bottom;"/>';
         $image_new_date = '<img src="images/commsyicons/22x22/date.png" style="vertical-align:bottom;"/>';
         $image_new_discussion = '<img src="images/commsyicons/22x22/discussion.png" style="vertical-align:bottom;"/>';
         $image_new_todo = '<img src="images/commsyicons/22x22/todo.png" style="vertical-align:bottom;"/>';
      }
      $html = '<div id="'.get_class($this).'">'.LF;
      $html .= '<center>';
      $html .= ahref_curl($this->_environment->getCurrentContextID(),'material','edit',array('iid' => 'NEW'),$image_new_material, $this->_translator->getMessage('COMMON_ENTER_NEW_MATERIAL'));
      $html .= '&nbsp;&nbsp;&nbsp;';
      $html .= ahref_curl($this->_environment->getCurrentContextID(),'date','edit',array('iid' => 'NEW'),$image_new_date, $this->_translator->getMessage('COMMON_ENTER_NEW_DATE'));
      $html .= '&nbsp;&nbsp;&nbsp;';
      $html .= ahref_curl($this->_environment->getCurrentContextID(),'discussion','edit',array('iid' => 'NEW'),$image_new_discussion, $this->_translator->getMessage('COMMON_ENTER_NEW_DISCUSSION'));
      $html .= '&nbsp;&nbsp;&nbsp;';
      $html .= ahref_curl($this->_environment->getCurrentContextID(),'todo','edit',array('iid' => 'NEW'),$image_new_todo, $this->_translator->getMessage('COMMON_ENTER_NEW_TODO'));
      $html .= '</center>';
      $html .= '</div>'.LF;
      return $html;
   }
}
?>