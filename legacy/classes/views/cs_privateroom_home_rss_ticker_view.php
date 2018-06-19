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
class cs_privateroom_home_rss_ticker_view extends cs_view {

var  $_rss_ticker_array = array();

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->_view_title = $this->_translator->getMessage('COMMON_RSS_TICKER');
      $this->setViewName('clock');
   }

   function setRssTickerArray($array){
      $this->_rss_ticker_array = $array;
   }

   function getPortletJavascriptAsHTML(){
     //rssticker_ajax(RSS_id, cachetime, divId, divClass, delay, optionalswitch)
     //1) RSS_id: "Array key of RSS feed in PHP script bridge.php"
     //2) cachetime: Time to cache the feed in minutes (0 for no cache)
     //3) divId: "ID of DIV to display ticker in. DIV dynamically created"
     //4) divClass: "Class name of this ticker, for styling purposes"
     //5) delay: delay between message change, in milliseconds
     //6) optionalswitch: "optional arbitrary" string to create additional logic in call back function
     $current_context_item = $this->_environment->getCurrentContextItem();
     $current_user_item = $this->_environment->getCurrentUserItem();
     $hash_manager = $this->_environment->getHashManager();
     $html  = '<script type="text/javascript"> '.LF;
     $html .= '   var rss_ticker_cid = "'.$current_context_item->getItemID().'";'.LF;
     $html .= '   var rss_ticker_sid = "'.$this->_environment->getSessionID().'";'.LF;
     $html .= '</script>'.LF;
     $portlet_rss_array = $current_context_item->getPortletRSSArray();
     foreach($portlet_rss_array as $rss_item){
        if (isset($rss_item['title']) and !empty($rss_item['title']) and isset($rss_item['adress']) and !empty($rss_item['adress'])){
           $html .= '<script type="text/javascript"> '.LF;
           if (isset($rss_item['title']) and !empty($rss_item['title']) and $rss_item['display'] == '2'){
              $html .= ' new rssticker_ajax("'.$rss_item['title'].'", 0, "'.$rss_item['title'].'", "ticker", 10000, "date",rss_ticker_cid,rss_ticker_sid);'.LF;
           }else{
              $html .= ' new rssticker_ajax("'.$rss_item['title'].'", 0, "'.$rss_item['title'].'", "ticker", 10000, "date",rss_ticker_cid,rss_ticker_sid);'.LF;
           }
           $html .= '</script>'.LF;
        }
     }
     return $html;
   }

   function asHTML () {
     $html = '';
     $current_context_item = $this->_environment->getCurrentContextItem();
     $current_user_item = $this->_environment->getCurrentUserItem();
     $html .= '<div id="'.get_class($this).'" style="padding-right:12px;">'.LF;
     $portlet_rss_array = $current_context_item->getPortletRSSArray();
     foreach($portlet_rss_array as $rss_item){
        if (isset($rss_item['title']) and !empty($rss_item['title']) and isset($rss_item['adress']) and !empty($rss_item['adress'])){
           $html .= ' <h4 style="margin-bottom:0px; margin-top:0px;">'.$rss_item['title'].'</h4> '.LF;
           $html .= '<div id="'.$rss_item['title'].'" class="ticker">'.$rss_item['title'];
           $html .= '</div>';
        }
     }
     $html .= '</div>';

     return $html;
   }
   
   function getPreferencesAsHTML(){
      $html = $this->_translator->getMessage('PORTLET_CONFIGURATION_RSS').':<br/>';
      $current_context_item = $this->_environment->getCurrentContextItem();
      $portlet_rss_array = $current_context_item->getPortletRSSArray();
      $html .= '<div id="portlet_rss_list">';
      foreach($portlet_rss_array as $rss_item){
      	$checked = '';
      	if($rss_item['display']){
      		$checked = ' checked ';
      	}
      	$html .= '<div class="rss_list_div" name="'.$rss_item['title'].'"><input type="checkbox" name="portlet_rss[]" value="'.$rss_item['title'].'"'.$checked.'>'.$rss_item['title'].' ('.$rss_item['adress'].')</div>'.LF;
      }
      $html .= '</div><br/>';
      $html .= '<input type="text" id="portlet_rss_title" size="10">&nbsp;<input type="text" id="portlet_rss_adress" size="32">';
      $html .= '<input type="submit" id="portlet_rss_add_button" value="'.$this->_translator->getMessage('PORTLET_CONFIGURATION_RSS_ADD_BUTTON').'"><br/><br/>';
      $html .= '<input type="submit" id="portlet_rss_button" value="'.$this->_translator->getMessage('COMMON_SAVE_BUTTON').'">';
      return $html;
   }
}
?>