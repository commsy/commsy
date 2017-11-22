<?php
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
include_once('functions/date_functions.php');

/**
 *  generic upper class for CommSy homepage-views
 */
class cs_home_usageinfo_view extends cs_view {

var $_config_boxes = false;

   function __construct($params) {
      cs_view::__construct($params);
      $this->setViewName('usageinfos');
      $room = $this->_environment->getCurrentContextItem();
      $this->_view_title = $room->getUsageInfoHeaderForRubric($this->_environment->getCurrentModule());
      unset($room);
   }


   function asHTML () {
     $html  = '';
     $current_context = $this->_environment->getCurrentContextItem();
     $current_user = $this->_environment->getCurrentUserItem();
     $room = $this->_environment->getCurrentContextItem();
     $rubric_info_array = $room->getUsageInfoArray();
     $user = $this->_environment->getCurrentUserItem();
     $room = $this->_environment->getCurrentContextItem();
     $act_rubric = $this->_environment->getCurrentModule();
     $info_text = $room->getUsageInfoTextForRubric($act_rubric);
     if (!strstr($info_text, $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'))
         and !strstr($info_text, $this->_translator->getMessage('USAGE_INFO_COMING_SOON'))
         and !empty($info_text)
      ){
         $html .= $this->_getRubricInfoAsHTML($this->_environment->getCurrentModule());
     }
     return $html;
   }

  function _getRubricInfoAsHTML($act_rubric){
      $html='';
      $room = $this->_environment->getCurrentContextItem();
      $info_text = $room->getUsageInfoTextForRubric($act_rubric);
      $html .= '<div style="margin-top:0px;">'.LF;
      $html .= '<div style="position:relative; top:12px;">'.LF;
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $html .= '<img src="images/commsyicons_msie6/usage_info_3.gif"/>';
      } else {
         $html .= '<img src="images/commsyicons/usage_info_3.png"/>';
      }
      $html .= '</div>'.LF;
      $html .= '<div class="right_box_title" style="font-weight:bold;">'.$this->_text_as_html_short($this->_view_title).'</div>';
      $html .= '<div class="usage_info">'.LF;
      $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($info_text)).BRLF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

}
?>