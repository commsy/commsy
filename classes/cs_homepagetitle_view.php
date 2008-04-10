<?php
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

include_once('classes/cs_view.php');
include_once('functions/date_functions.php');

/**
 *  generic upper class for CommSy homepage-views
 */
class cs_homepagetitle_view extends cs_view {

   /**
    * int - begin of list
    */
   var $_from = NULL;

   /**
    * int - length of shown list
    */
   var $_interval = 10;

   /**
    * int - length of whole list
    */
   var $_count_all = NULL;
   var $_count_all_shown = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_homepagetitle_view ($environment, $with_modifying_actions) {
      $this->cs_view( $environment,
                      $with_modifying_actions);
   }

   // @segment-begin 49781  setCountAll($count_all)/getCountAll()-lenght-of-whole-list
   /** set count_all counter of the list view
    * this method sets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    *
    * @author CommSy Development Group
    */



   function asHTML () {
      $html ='<div style="width:100%;">'.LF;
      if ( $this->_environment->inProjectRoom() ) {
         $home_title  = $this->_translator->getMessage('HOME_ROOM_INDEX');
         $home_title .= ' ('.$this->_translator->getMessage('COMMON_PROJECT').')';
      } elseif ( $this->_environment->inGroupRoom() ) {
         $home_title  = $this->_translator->getMessage('HOME_ROOM_INDEX');
         $home_title .= ' ('.$this->_translator->getMessage('COMMON_GROUPROOM').')';
      } elseif ( $this->_environment->inPrivateRoom() ) {
         $home_title  = $this->_translator->getMessage('HOME_ROOM_INDEX');
         $home_title .= ' ('.$this->_translator->getMessage('COMMON_PRIVATEROOM_DESC').')';
      } else {
         $home_title  = $this->_translator->getMessage('HOME_CAMPUS_INDEX');
         $home_title .= ' ('.$this->_translator->getMessage('COMMON_COMMUNITY').')';
      }
      $html .= '<div>'.LF;
      $html .= '<h2 class="pagetitle">'.$home_title.'</h2>'.LF;
      $html .= '</div>';
      $html .= '</div>';
      return $html;
   }
}
?>