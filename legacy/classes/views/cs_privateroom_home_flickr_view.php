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
class cs_privateroom_home_flickr_view extends cs_view {

var  $_flickr_id = '';
var  $_rotation_time = '5000';

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->_view_title = $this->_translator->getMessage('COMMON_FLICKR');
      $this->setViewName('flickr');
   }

   function getPortletJavascriptAsHTML(){
     $count= '1';
     $html  = '<script type="text/javascript">'.LF;
     $html .= 'var bridge = new ctRotatorBridgeFlickr("http://api.flickr.com/services/feeds/photos_faves.gne?format=json&id='.$this->_flickr_id.'&jsoncallback=?", function(dataSource){'.LF;
     $html .= '$("#flickr").ctRotator(dataSource, {'.LF;
     $html .= 'showCount:'.$count.','.LF;
     $html .= 'speed: '.$this->_rotation_time.','.LF;
     $html .= 'itemRenderer:function(item){'.LF;
     $html .= 'return "<a href=\"" + item.url+'.LF;
     $html .= '      "\"><img style=\"height:200px;\" src=\"" + item.image +'.LF;
     $html .= '       "\" alt=\"" + item.title + "\"/></a>";'.LF;
     $html .= '}'.LF;
     $html .= '});'.LF;
     $html .= '});'.LF;
     $html .= 'bridge.getDataSource();'.LF;
     $html .= 'var flickr_message = \''.$this->_translator->getMessage('PORTLET_FLICKR_ID','TEMP_ID').'\';'.LF;
     $html .= '</script>'.LF;
     return $html;
   }

   function setFlickrID($id){
      $this->_flickr_id = $id;
   }
   function setRotationTime($time){
      $this->_rotation_time = $time;
   }

   function asHTML () {
       if (empty($this->_flickr_id)){
        $html  = '<div id="'.get_class($this).'" style="margin-bottom:5px;">'.$this->_translator->getMessage('PORTLET_FLICKR_ID_UNKNOWN').'</div>'.LF;
       }else{
           $html  = '<div id="'.get_class($this).'" style="margin-bottom:5px;" name="flickr_message">'.$this->_translator->getMessage('PORTLET_FLICKR_ID',$this->_flickr_id).'</div>'.LF;
       }
     $html  .= '<div style="text-align:center;height:205px;overflow:hidden;" id="flickr"></div>'.LF;
     return $html;
   }

   function getPreferencesAsHTML(){
      $html = $this->_translator->getMessage('PORTLET_CONFIGURATION_FLICKR_ID').': ';
      $html .= '<input type="text" id="portlet_flickr_id" value="'.$this->_flickr_id.'" />';
      $html .= '<input type="submit" id="portlet_flickr_button" value="'.$this->_translator->getMessage('COMMON_SAVE_BUTTON').'" />';
      return $html;
   }
}
?>