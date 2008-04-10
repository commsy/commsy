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

include_once('classes/cs_view.php');
include_once('functions/date_functions.php');

/**
 *  generic upper class for CommSy homepage-views
 */
class cs_homepage_usage_info_view extends cs_view {

var $_config_boxes = false;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_homepage_usage_info_view ($environment, $with_modifying_actions) {
      $this->cs_view( $environment,
                      $with_modifying_actions);
      $this->setViewName('usageinfos');
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $this->_view_title = $room->getUsageInfoHeaderForRubric($this->_environment->getCurrentModule());
      $rubric_info_array = $room->getUsageInfoArray();
      if (!is_array($rubric_info_array)) {
         $rubric_info_array = array();
      }
   }

   function asHTML () {
     $html  = '';
     $current_context = $this->_environment->getCurrentContextItem();
     $current_user = $this->_environment->getCurrentUserItem();
     $room = $this->_environment->getCurrentContextItem();
     $rubric_info_array = $room->getUsageInfoArray();
#     if (!(in_array($this->_environment->getCurrentModule().'_no', $rubric_info_array)) and $current_user->isUser() ){
         $html .= $this->_getRubricInfoAsHTML($this->_environment->getCurrentModule());
#     }
     return $html;
   }

  function _getRubricInfoAsHTML($act_rubric){
      $html='';
      $room = $this->_environment->getCurrentContextItem();
      $info_text = $room->getUsageInfoTextForRubric($act_rubric);
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title" style="padding-top:3px; font-weight:bold;">'.$room->getUsageInfoHeaderForRubric($act_rubric).'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" style="font-size:8pt;">'.LF;
      $html .= $this->_text_as_html_long($info_text).BRLF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

}
?>