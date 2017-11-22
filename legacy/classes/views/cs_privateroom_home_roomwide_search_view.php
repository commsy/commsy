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

/** upper class of the form view
 */
$this->includeClass(VIEW);

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_privateroom_home_roomwide_search_view extends cs_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->setViewName('search');
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $this->_view_title = $this->_translator->getMessage('PRIVATROOM_ROOMWIDE_SEARCH_BOX');
   }


    /** get the value of the search box
    * this method gets the search value of the list
    *
    * @param string  $this->_search_text
    */
    function getSearchText (){
       if (empty($this->_search_text)){
          $this->_search_text = $this->_translator->getMessage('PRIVATROOM_ROOMWIDE_SEARCH_BOX_TEXT');
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


   function asHTML(){
      $html = '<div id="'.get_class($this).'">'.LF;
      #$html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), 'campus_search', 'index','').'" method="get" name="form">'.LF;
      $html .= '<form style="padding:0px; margin:0px;" id="privateroom_home_roomwide_search_form">'.LF;
      $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
      $html .= '   <input type="hidden" name="mod" value="entry"/>'.LF;
      $html .= '   <input type="hidden" name="fct" value="index"/>'.LF;
      $html .= '<input id="privateroom_home_roomwide_search_text" onclick="javascript:resetSearchText(\'privateroom_home_roomwide_search_text\');" style="width:80%; font-size:10pt; margin-bottom:0px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>';
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $html .= '<input type="image" src="images/commsyicons_msie6/22x22/search.gif" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
      } else {
         $html .= '<input type="image" src="images/commsyicons/22x22/search.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
      }
#      $html .= '</div>'.LF;
      $html .= '<div style="padding-top:5px;"><img id="privateroom_home_roomwide_search_toggle" src="images/more.gif"/>&nbsp;Erweiterte Suche';
      $html .= '<div id="privateroom_home_roomwide_search_extended" style="display: block;">';
      $html .= '<div style="padding: 2px;">';
      $html .= '<div style="width: 97%;" id="form_formatting_box">';

      $html .= '<div style="padding-bottom: 5px;">'.$this->_translator->getMessage('COMMON_PAGE_ENTRIES').':</div>';
      $html .= '<input type="radio" name="roomwide_search_interval" value="10" />10';
      $html .= '<input type="radio" name="roomwide_search_interval" value="20" checked />20';
      $html .= '<input type="radio" name="roomwide_search_interval" value="50" />50<br/><br/>';

      $html .= '<div style="padding-bottom: 5px;">'.$this->_translator->getMessage('PRIVATE_ROOM_ROOMWIDE_SEARCH_EXT_TYPES').':</div>';
      $html .= '<div style="height:60px; overflow-y:auto; border:1px dashed #bbbbbb; background-color:#ffffff;">';
      $html .= '<input type="checkbox" name="roomwide_search_type" value="announcement"/>'.$this->_translator->getMessage('ANNOUNCEMENTS').'<br/>';
      $html .= '<input type="checkbox" name="roomwide_search_type" value="date"/>'.$this->_translator->getMessage('DATES').'<br/>';
      $html .= '<input type="checkbox" name="roomwide_search_type" value="material"/>'.$this->_translator->getMessage('MATERIALS').'<br/>';
      $html .= '<input type="checkbox" name="roomwide_search_type" value="discussion"/>'.$this->_translator->getMessage('DISCUSSIONS').'<br/>';
      $html .= '<input type="checkbox" name="roomwide_search_type" value="todo"/>'.$this->_translator->getMessage('TODOS').'<br/>';
      $html .= '<input type="checkbox" name="roomwide_search_type" value="topic"/>'.$this->_translator->getMessage('TOPICS').'<br/>';
      $html .= '</div><br/>';

      $context_array = array();
      $room_name_array = array();

      $user_item = $this->_environment->getCurrentUserItem();
      $private_room_item = $this->_environment->getCurrentContextItem();
      $context_array[] = $private_room_item->getItemID();
      $room_name_array[$private_room_item->getItemID()] = $private_room_item->getTitle();

      // Projekt- und Gruppenraeume
      $project_list = $user_item->getRelatedProjectList();
      $project_item = $project_list->getFirst();
      while($project_item){
         $context_array[] = $project_item->getItemID();
         $room_name_array[$project_item->getItemID()] = $project_item->getTitle();
         $project_item = $project_list->getNext();
      }

      // Gemeinschaftsraeume
      $community_list = $user_item->getUserRelatedCommunityList();
      $community_item = $community_list->getFirst();
      while($community_item){
         $context_array[] = $community_item->getItemID();
         $room_name_array[$community_item->getItemID()] = $community_item->getTitle();
         $community_item = $community_list->getNext();
      }

      $html .= '<div style="padding-bottom: 5px;">'.$this->_translator->getMessage('PRIVATE_ROOM_ROOMWIDE_SEARCH_EXT_ROOMS').':</div>';
      $html .= '<div style="height:60px; overflow-y:auto; border:1px dashed #bbbbbb; background-color:#ffffff;">';
      foreach($context_array as $context_temp){
         $html .= '<input type="checkbox" name="roomwide_search_room" value="'.$context_temp.'"/>'.$this->_text_as_form($room_name_array[$context_temp]).'<br/>';
      }
      $html .= '</div>';

      $html .= '</div>';
      $html .= '</div>';
      $html .= '</div>';
      $html .= '</div>';
      $html .='</form>'.LF;
      $html .= '</div>';

      $html .= '<div id="privateroom_home_roomwide_search_div">';
      $html .= '<table id="privateroom_home_roomwide_search_table" style="width:100%;">';
      $html .= '</table>';
      $html .= '</div>';

      $html .= '<script type="text/javascript">'.LF;
      $html .= '<!--'.LF;
      $html .= 'var reset_search_text_message = "'.$this->_text_as_form($this->getSearchText()).'";'.LF;
      $html .= 'var roomwide_search_empty_result = "'.$this->_translator->getMessage('PRIVATE_ROOM_ROOMWIDE_SEARCH_EMPTY_RESULT').'";'.LF;
      $html .= 'var roomwide_search_from = "'.$this->_translator->getMessage('COMMON_FROM2').'";'.LF;
      $html .= 'var roomwide_search_to = "'.$this->_translator->getMessage('COMMON_TO').'";'.LF;
      $html .= '-->'.LF;
      $html .= '</script>'.LF;
      return $html;
   }
}
?>