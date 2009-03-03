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
include_once('functions/text_functions.php');

/**
 *  class for CommSy activity panel
 */
class cs_activity_view extends cs_view {

   var $_percent_active_members = 0;
   var $_active_members = 0;
   var $_count_new_entries = 0;
   var $_count_page_impressions = 0;
   var $_config_boxes = false;

   /** constructor
    *
    * @param array params array with parameters
    */
   public function __CONSTRUCT ( $params ) {
      $this->cs_view($params);
      $this->setViewName('activity');
      // Determine time spread
      $context = $this->_environment->getCurrentContextItem();
      if ($this->_environment->inCommunityRoom()){
         $time_spread = 90;
      }else{
         $time_spread = $context->getTimeSpread();
      }
      $this->_view_title = $this->_translator->getMessage('HOME_ACTIVITY_SHORT_HEADER').' ('.$this->_translator->getMessage('HOME_ACTIVITY_SHORT_DESCRIPTION', $time_spread).'):';
   }

   function setPercentActiveMembers ( $value ) {
      $this->_percent_active_members = (int)$value;
   }

   function getPercentActiveMembers () {
      return $this->_percent_active_members;
   }

   function setActiveMembers ( $value ) {
      $this->_active_members = (int)$value;
   }

   function getActiveMembers () {
      return $this->_active_members;
   }

   function setCountNewEntries ( $value ) {
      $this->_count_new_entries = (int)$value;
   }

   function getCountNewEntries () {
      return $this->_count_new_entries;
   }

   function setCountPageImpressions ( $value ) {
      $this->_count_page_impressions = (int)$value;
   }

   function getCountPageImpressions () {
      return $this->_count_page_impressions;
   }

   function asHTML () {

      // Determine time spread
      $environment = $this->getEnvironment();
      $context = $environment->getCurrentContextItem();
      if ($environment->inCommunityRoom()){
         $time_spread = 90;
      }else{
         $time_spread = $context->getTimeSpread();
      }
      $html = LF.'<!-- BEGIN OF ACTIVITY VIEW -->'.LF;
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title" style="font-weight:bold;">'.$this->_translator->getMessage('COMMON_ACTIVITY').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main">'.LF;
      $html .= '      <span class="normal">'.$this->_translator->getMessage('HOME_ACTIVITY_ACTIVE_MEMBERS').':</span>'.LF;
      $active = $context->getActiveMembers($time_spread);
      $all_users = $context->getAllUsers();
      $percentage = round($active / $all_users * 100);
      $html .= '         <div class="gauge" style="height:15px;">'.LF;
      if ( $percentage >= 5 ) {
         $html .= '            <div class="gauge-bar" style="height:15px; width:'.$percentage.'%; font-size:10pt;">'.$active.'</div>'.LF;
      } else {
         $html .= '            <div class="gauge-bar" style="height:15px; width:'.$percentage.'%; font-size:10pt;">&nbsp;</div>'.LF;
      }
      $html .= '         </div>'.LF;
      $html .= '         <div class="div_line">'.LF;
      $html .= '         </div>'.LF;

      $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
      $html .= '         <tr>'.LF;
      $html .= '         <td style="width:80%; font-size:10pt;" class="right_box_main">'.LF;
      $count_total = $context->getPageImpressions($time_spread);
      $html .= $this->_translator->getMessage('HOME_ACTIVITY_PAGE_IMPRESSIONS').':';
      $html .= '         </td>'.LF;
      $html .= '         <td class="right_box_main" style="width:20%; text-align:right; font-size:10pt;">'.$count_total.LF;
      $html .= '         </td>'.LF;
      $html .= '         </tr>'.LF;
      $html .= '         <tr>'.LF;
      $count_total = $context->getNewEntries($time_spread);
      $html .= '         <td style="width:80%; font-size:10pt;" class="right_box_main">'.LF;
      $html .= $this->_translator->getMessage('HOME_ACTIVITY_NEW_ENTRIES').':';
      $html .= '         </td>'.LF;
      $html .= '         <td class="right_box_main" style=" width:20%; text-align: right; font-size:10pt;">'.$count_total.LF;
      $html .= '         </td>'.LF;
      $html .= '         </tr>'.LF;
      $html .= '         </table>'.LF;
      $html .= '         </div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .= '         </div>'.LF;
      $html .= '<!-- END OF ACTIVITY VIEW -->';
      return $html;
   }


}
?>