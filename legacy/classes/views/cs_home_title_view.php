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

/**
 *  generic upper class for CommSy homepage-views
 */
class cs_home_title_view extends cs_view {

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
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
   }

   var $_search_text = NULL;

   // @segment-begin 49781  setCountAll($count_all)/getCountAll()-lenght-of-whole-list
   /** set count_all counter of the list view
    * this method sets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    *
    * @author CommSy Development Group
    */

    /** get the value of the search box
    * this method gets the search value of the list
    *
    * @param string  $this->_search_text
    */
    function getSearchText (){
       if (empty($this->_search_text)){
       	$this->_search_text = $this->_translator->getMessage('COMMON_SEARCH_IN_ROOM');
       }
       return $this->_search_text;
    }

    // @segment-begin 8397  setSearchText($search_tex)-sets:_search_text/_search_array
    /** set the value of the search box
    * this method sets the search value of the list
    *
    * @param string  $this->_search_text
    */
    function setSearchText ($search_text){
       $this->_search_text = $search_text;
       $literal_array = array();
       $search_array = array();

       //find all occurances of quoted text and store them in an array
       preg_match_all('~("(.+?)")~u',$search_text,$literal_array);
       //delete this occurances from the original string
       $search_text = preg_replace('~("(.+?)")~u','',$search_text);

       $search_text = preg_replace('~-(\w+)~u','',$search_text);

       //clean up the resulting array from quots
       $literal_array = str_replace('"','',$literal_array[2]);
       //clean up rest of $limit and get an array with entrys
       $search_text = str_replace('  ',' ',$search_text);
       $search_text = trim($search_text);
       $split_array = explode(' ',$search_text);

       //check which array contains search limits and act accordingly
       if ($split_array[0] != '' AND count($literal_array) > 0) {
          $search_array = array_merge($split_array,$literal_array);
       } else {
          if ($split_array[0] != '') {
             $search_array = $split_array;
          } else {
             $search_array = $literal_array;
          }
       }
       $this->_search_array = $search_array;
    }


   function asHTML () {
     $current_context_id = $this->_environment->getCurrentContextID();
     $current_portal_id = $this->_environment->getCurrentPortalID();

      $html ='<div style="width:100%; height:30px;">'.LF;
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
      if (!$this->_environment->inPrivateRoom()){
         $html .= '<div style="float:right; text-align:left; padding-top: 5px; width:28%; white-space:nowrap;">'.LF;
         $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), 'campus_search', 'index','').'" method="post" name="form">'.LF;
         $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
         $html .= '   <input type="hidden" name="mod" value="campus_search"/>'.LF;
         $html .= '   <input type="hidden" name="fct" value="index"/>'.LF;
         $html .= '<input id="searchtext" onclick="javascript:resetSearchText(\'searchtext\');" style="width:220px; font-size:10pt; margin-bottom:0px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $html .= '<input type="image" src="images/commsyicons_msie6/22x22/search.gif" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
         } else {
            $html .= '<input type="image" src="images/commsyicons/22x22/search.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
         }
         $html .='</form>'.LF;
         $html .= '</div>'.LF;
      }
      $html .= '<div>'.LF;
      if (!$this->_environment->inPrivateRoom()){
         $html .= '<h2 class="pagetitle">'.$home_title.'</h2>'.LF;
      } else {
      	 $html .= '<div style="float:left;"><h2 class="pagetitle">'.$home_title.'</h2></div>'.LF;
         $html .= '<div style="float:right;">'.LF;
         $html .= '<div class="portlet-configuration">'.LF;
         $html .= '<div class="portlet-header-configuration ui-widget-header" style="width:200px; font-weight:normal;">'.LF;
         $html .= '<span style="font-weight:bold;">'.$this->_translator->getMessage('HOME_PORTLET_CONFIGURATION').'</span>'.LF;
         $html .= '<div style="float:right;">'.LF;
         $html .= '<a href="#"><img id="new_icon" src="images/commsyicons/48x48/config/privateroom_home_options.png"  style="height:0px;" /></a>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
      }
      $html .= '</div>';
      $html .= '</div>';
      return $html;

   }
}
?>