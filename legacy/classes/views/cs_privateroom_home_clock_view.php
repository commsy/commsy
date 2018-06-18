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

$this->includeClass(VIEW);
include_once('functions/date_functions.php');
include_once('classes/cs_link.php');

/**
 *  generic upper class for CommSy homepage-views
 */
class cs_privateroom_home_clock_view extends cs_view {

var  $_config_boxes = false;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->_view_title = $this->_translator->getMessage('COMMON_CLOCK');
      $this->setViewName('clock');
   }

   function asHTML () {
     $session = $this->_environment->getSessionItem();
     if($session->issetValue('cookie')){
        if($session->getValue('cookie') == '1'){
           $html = ' <div id="'.get_class($this).'" style="margin:0px auto; padding: 5px; text-align:center;"><div style="width:180px; margin:0px auto;"><ul id="clock"> <li id="sec"></li><li id="hour"></li><li id="min"></li></ul></div></div>';
        } else {
        	  $html = $this->_translator->getMessage('COMMON_COOKIES_NEEDED');
        }
     }
     return $html;
   }
}
?>