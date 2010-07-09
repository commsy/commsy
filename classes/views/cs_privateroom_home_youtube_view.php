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
class cs_privateroom_home_youtube_view extends cs_view {

var  $_config_boxes = false;

var $_channel_id = '';

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_privateroom_home_youtube_view ($params) {
      $this->cs_view($params);
      $this->_view_title = $this->_translator->getMessage('PORTLET_YOUTUBE_CHANNEL');
      $this->setViewName('youtube_cannel');
   }


/*
    * youtube script options:
    * userName: 'myUserName' // a YouTube username
    * hideNumberOfRatings: false // hide or show the number of ratings
    * removeBordersFromImage: false // should we remove the borders from the thumbnail
    * loadingText: "Loading..." // text for the AJAX loading
    * linksInNewWindow: false // should we open the links in a new window
    * hideVideoLength: true // should we hide the length of the video
    * hideFrom: false // should we hide the 'from' (yourself) link
    * hideViews: true // should we hide the number of views
    * hideRating: true // should we hide the ratings (stars)

 */

   function setChannelID($id){
      $this->_channel_id = $id;
   }

   function getPortletJavascriptAsHTML(){
     $html  = '<script type="text/javascript">'.LF;
     $html .= '$(document).ready(function() {' .LF;
     $html .= '   $("#youtubevideos_'.$this->_channel_id.'").youTubeChannel({'.LF;
     $html .= '      userName: "'.$this->_channel_id.'",'.LF;
     $html .= '      channel: "favorites",'.LF;
     $html .= '      hideAuthor: true,'.LF;
     $html .= '      numberToDisplay: 3,'.LF;
     $html .= '      linksInNewWindow: true,'.LF;
     $html .= '      loadingText: "'.$this->_translator->getMessage('PORTLET_YOUTUBE_IS_LOADING').'",'.LF;
     $html .= '   });'.LF;
     $html .= '});'.LF;
     $html .= '</script>';
     return $html;
   }

   function asHTML () {
     $html  = '<div id="'.get_class($this).'" style="margin-top:0px; margin-bottom:5px;">'.$this->_translator->getMessage('PORTLET_YOUTUBE_CHANNEL_ID',$this->_channel_id).'</div>'.LF;
     $html .= '<div id="youtubevideos_'.$this->_channel_id.'"></div>'.LF;
     return $html;
   }
   
   function getPreferencesAsHTML(){
   	$html = '<input type="text" name="portlet_youtube">';
   	$html .= '<input type="submit" id="portlet_youtube_button" value="Speichern">';
      return $html;
   }
}
?>