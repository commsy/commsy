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
class cs_privateroom_home_twitter_view extends cs_view {

   var $_twitter_id = '';


   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->_view_title = $this->_translator->getMessage('COMMON_TWITTER_FRIENDS');
      $this->setViewName('twitter');
   }

   function setTwitterID($id){
      $this->_twitter_id = $id;
   }

   function asHTML () {
     $html  = '<div id="'.get_class($this).'" name="twitter_message" style="margin-top:0px; margin-bottom:5px;">'.$this->_translator->getMessage('PORTLET_TWITTER_CHANNEL_ID',$this->_twitter_id).'</div>'.LF;
     $html .='<script type="text/javascript">'.LF;
     $html .='$(document).ready(function(){$("#twitter_friends").twitterFriends({debug:1,username:"'.$this->_twitter_id.'"});});'.LF;
     $html .= 'var twitter_message = \''.$this->_translator->getMessage('PORTLET_TWITTER_CHANNEL_ID','TEMP_TWITTER_CHANNEL_ID').'\';'.LF;
     $html .= '</script>'.LF;
     $html .= '<div id="twitter_friends" style="height:200px;"></div>';
     return $html;
   }
   
   function getPreferencesAsHTML(){
      $html = $this->_translator->getMessage('PORTLET_CONFIGURATION_TWITTER_ACCOUNT').': ';
      $html .= '<input type="text" id="portlet_twitter_channel_id" value="'.$this->_twitter_id.'">';
      $html .= '<input type="submit" id="portlet_twitter_button" value="'.$this->_translator->getMessage('COMMON_SAVE_BUTTON').'">';
      return $html;
   }
}
?>