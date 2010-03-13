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

$this->includeClass(INDEX_VIEW);
//include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: announcement
 */
class cs_entry_index_view extends cs_index_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   public function __construct ($params) {
      $this->cs_index_view($params);
      $this->setTitle($this->_translator->getMessage('COMMON_ENTRIES'));
      $this->setActionTitle($this->_translator->getMessage('COMMON_ENTRY'));
      $this->_colspan = '4';
   }


    function setList ($list) {
       $this->_list = $list;
    }


   function _getItemChangeStatus($item,$context_id) {
      $current_user = $this->_environment->getCurrentUserItem();
      $related_user = $current_user->getRelatedUserItemInContext($context_id);
      if ($related_user->isUser()) {
         $noticed_manager = $this->_environment->getNoticedManager();
         $noticed = $noticed_manager->getLatestnoticedByUser($item->getItemID(),$related_user->getItemID());
         if ( empty($noticed) ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_NEW').']</span>';
         } elseif ( $noticed['read_date'] < $item->getModificationDate() ) {
            $info_text = ' <span class="changed">['.$this->_translator->getMessage('COMMON_CHANGED').']</span>';
         } else {
            $info_text = '';
         }
         // Add change info for annotations (TBD)
      } else {
         $info_text = '';
      }
      return $info_text;
   }

    /** get the value of the search box
    * this method gets the search value of the list
    *
    * @param string  $this->_search_text
    */
    function getSearchText (){
       if (empty($this->_search_text)){
        $this->_search_text = $this->_translator->getMessage('COMMON_SEARCH_IN_ENTRIES');
       }
       return $this->_search_text;
    }


   function _getSearchBoxAsHTML(){
      $html = '<div class="portlet">'.LF;
      $html .= '<div class="portlet-header">'.LF;
      $html .= $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_SEARCH_BOX').LF;
      $html .= '</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;
      $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), 'campus_search', 'index','').'" method="post" name="form">'.LF;
      $html .= '   <input type="hidden" name="cid" value="'.$this->_text_as_form($this->_environment->getCurrentContextID()).'"/>'.LF;
      $html .= '   <input type="hidden" name="mod" value="campus_search"/>'.LF;
      $html .= '   <input type="hidden" name="fct" value="index"/>'.LF;
      $html .= '<input id="searchtext" onclick="javascript:resetSearchText(\'searchtext\');" style="width:80%; font-size:10pt; margin-bottom:0px;" name="search" type="text" size="20" value="'.$this->_text_as_form($this->getSearchText()).'"/>';
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $html .= '<input type="image" src="images/commsyicons_msie6/22x22/search.gif" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
      } else {
         $html .= '<input type="image" src="images/commsyicons/22x22/search.png" style="vertical-align:top;" alt="'.$this->_translator->getMessage('COMMON_SEARCH_BUTTON').'"/>';
      }
      $html .='</form>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }



   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;

      $html .= $this->_getIndexPageHeaderAsHTML().LF;
      $html .= '<div class="column" style="width:50%;">'.LF;
      $html .= $this->_getContentBoxAsHTML().LF;
      $html .= '</div>'.LF;
      $html .= '<div class="column" style="width:50%;">'.LF;
      $html .= $this->_getSearchBoxAsHTML().LF;
      $html .= $this->_getMatrixBoxAsHTML().LF;
      $html .= $this->_getBuzzwordBoxAsHTML().LF;
      $html .= '</div>'.LF;

      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      return $html;
   }



   function _getContentBoxAsHTML () {
      $list = $this->_list;
      $html = '<div class="portlet">'.LF;
      $html .= '<div class="portlet-header">'.LF;
      $html .= $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_LIST_BOX').LF;
      $html .= '</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;
      $html .= '<table style="width:100%;">';
      if ( !isset($list) || $list->isEmpty() ) {
         $html .= '<tr  class="list"><td class="odd" style="border-bottom: 0px;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</td></tr>';
      } else {
         $current_item = $list->getFirst();
         $i = 0;
         while ( $current_item ) {
            $html .= $this->_getItemAsHTML($current_item, $i++);
            $current_item = $list->getNext();
         }
      }
      $html .= '</table>';
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;

   }


   function _getMatrixBoxAsHTML () {
      $html = '<div class="portlet">'.LF;
      $html .= '<div class="portlet-header">'.LF;
      $html .= $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_MATRIX_BOX').LF;
      $html .= '</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;
/*Prototyp*/
      $html .= '<table style="width:100%; border:1px solid #EEEEEE;">';
      $html .= '<tr>'.LF;
      $html .= '<td style="background-color:#EEEEEE;">'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="background-color:#EEEEEE;">Subjektbezug'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="background-color:#EEEEEE;">Schulbezug'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="background-color:#EEEEEE;">Wissenschaftsbezug'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;

      $html .= '<tr>'.LF;
      $html .= '<td style="background-color:#EEEEEE;">Unterrichten'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="text-align:center;"><a>5</a>'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="text-align:center;"><a>2</a>'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="text-align:center;"><a>0</a>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;

      $html .= '<tr>'.LF;
      $html .= '<td style="background-color:#EEEEEE;">Erziehen und Beraten'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="text-align:center;"><a>10</a>'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="text-align:center;"><a>2</a>'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="text-align:center;"><a>6</a>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;

      $html .= '<tr>'.LF;
      $html .= '<td style="background-color:#EEEEEE;">Diagnostizieren und fördern'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="text-align:center;"><a>3</a>'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="text-align:center;"><a>0</a>'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="text-align:center;"><a>0</a>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;

      $html .= '<tr>'.LF;
      $html .= '<td style="background-color:#EEEEEE;">Schule entwickeln'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="text-align:center;"><a>3</a>'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="text-align:center;"><a>20</a>'.LF;
      $html .= '</td>'.LF;
      $html .= '<td style="text-align:center;"><a>3</a>'.LF;
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;

      $html .= '</table>';
/*EndePrototyp*/
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;

   }


   function _getBuzzwordBoxasHTML(){
      $current_user = $this->_environment->getCurrentUserItem();
      $buzzword_manager = $this->_environment->getLabelManager();
      $buzzword_manager->resetLimits();
      $buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
      $buzzword_manager->setTypeLimit('buzzword');
      $buzzword_manager->setGetCountLinks();
      $buzzword_manager->select();
      $buzzword_list = $buzzword_manager->get();
      $html  = '';
      $html .= '<div class="portlet">'.LF;
      $html .= '<div class="portlet-header">'.LF;
      $html .= $this->_translator->getMessage('PRIVATEROOM_MY_ENTRIES_BUZZWORD_BOX').LF;
      $html .= '</div>'.LF;
      $html .= '<div class="portlet-content">'.LF;
      $buzzword = $buzzword_list->getFirst();
      $params = $this->_environment->getCurrentParameterArray();
      if (!$buzzword){
         $html .= '<span class="disabled" style="font-size:10pt;">'.$this->_translator->getMessage('COMMON_NO_ENTRIES').'</span>';
      }
      while ($buzzword){
         $count = $buzzword->getCountLinks();
         if ($count > 0){
            $font_size = $this->getBuzzwordSizeLogarithmic($count);
            $font_color = 100 - $this->getBuzzwordColorLogarithmic($count);
            $params['selbuzzword'] = $buzzword->getItemID();
            $temp_text = '';
            $style_text  = 'style="margin-left:2px; margin-right:2px;';
            $style_text .= ' color: rgb('.$font_color.'%,'.$font_color.'%,'.$font_color.'%);';
            $style_text .= 'font-size:'.$font_size.'px;"';
            $title  = '<span  '.$style_text.'>'.LF;
            $title .= $this->_text_as_html_short($buzzword->getName()).LF;
            $title .= '</span> ';

            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                'campus_search',
                                'index',
                                $params,
                                $title,$title).LF;
         }
         $buzzword = $buzzword_list->getNext();
      }
      $html .= '<div style="width:100%; text-align:right; padding-right:2px; padding-top:5px;">';
      if ($current_user->isUser() and $this->_with_modifying_actions ) {
         $params = array();
         $params['module'] = $this->_environment->getCurrentModule();
         $html .= ahref_curl($this->_environment->getCurrentContextID(),'buzzwords','edit',$params,$this->_translator->getMessage('COMMON_EDIT')).LF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.$this->_translator->getMessage('COMMON_EDIT').'</span>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      unset($current_user);
      return $html;
   }


   function getBuzzwordSizeLogarithmic( $count, $mincount=0, $maxcount=30, $minsize=10, $maxsize=20, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }

   function getBuzzwordColorLogarithmic( $count, $mincount=0, $maxcount=30, $minsize=30, $maxsize=70, $tresholds=0 ) {
      if( empty($tresholds) ) {
         $tresholds = $maxsize-$minsize;
         $treshold = 1;
      } else {
         $treshold = ($maxsize-$minsize)/($tresholds-1);
      }
      $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
      return round($minsize+round($a)*$treshold);
   }




   function _getIndexPageHeaderAsHTML(){
      $html .= '<h2 class="pagetitle">'.$this->_translator->getMessage('ENTRY_INDEX');
      $html .= '</h2>'.LF;
      return $html;
   }



   // @segment-begin 89418 _getItemAsHTML($item,$pos=0)-odd/even-for-announcement-entry-in-index
   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML($item, $pos=0, $with_links=TRUE) {
      $html = '';
      if ($pos%2 == 0){
         $style='class="even"';
      }else{
         $style='class="odd"';
      }
      $type = $item->getItemType();
      $item_manager = $this->_environment->getManager($type);
      $full_item = $item_manager->getItem($item->getItemID());
      if (is_object($full_item)){
         $html .= '   <tr class="list">'.LF;
         $html .= '   <td '.$style.'>'.LF;
         $type = $full_item->getType();
         if ($type =='label'){
            $type = $full_item->getLabelType();
         }
         $fragment = '';    // there is no anchor defined by default
         $link_created = $this->_translator->getDateInLang($full_item->getModificationDate());
         $text = '';
         $creator = $full_item->getCreatorItem();
         if ( isset($creator) and !$creator->isDeleted()) {
            $fullname = $this->_text_as_html_short($creator->getFullname());
         } else {
            $fullname = $this->_translator->getMessage('COMMON_DELETED_USER');
         }
         $room = $full_item->getContextItem();
         $room_title = $room->getTitle();
         switch ( $type ) {
            case CS_DISCARTICLE_TYPE:
               $linked_iid = $full_item->getDiscussionID();
               $fragment = 'anchor'.$full_item->getItemID();
               $discussion_manager = $this->_environment->getDiscussionManager();
               $new_full_item = $discussion_manager->getItem($linked_iid);
               break;
            case CS_STEP_TYPE:
               $linked_iid = $full_item->getToDoID();
               $fragment = 'anchor'.$full_item->getItemID();
               $todo_manager = $this->_environment->getToDoManager();
               $new_full_item = $todo_manager->getItem($linked_iid);
               break;
            case CS_SECTION_TYPE:
               $linked_iid = $full_item->getLinkedItemID();
               $fragment = 'anchor'.$full_item->getItemID();
               $material_manager = $this->_environment->getMaterialManager();
               $new_full_item = $material_manager->getItem($linked_iid);
               break;
            default:
               $linked_iid = $full_item->getItemID();
               $new_full_item = $full_item;
         }
         $type = $new_full_item->getType();
         if ($type =='label'){
            $type = $full_item->getLabelType();
         }
         switch ( mb_strtoupper($type, 'UTF-8') ) {
           case 'ANNOUNCEMENT':
              $text .= $this->_translator->getMessage('COMMON_ONE_ANNOUNCEMENT');
              $img = 'images/commsyicons/32x32/announcement.png';
              break;
           case 'DATE':
              $text .= $this->_translator->getMessage('COMMON_ONE_DATE');
              $img = 'images/commsyicons/32x32/date.png';
              break;
           case 'DISCUSSION':
              $text .= $this->_translator->getMessage('COMMON_ONE_DISCUSSION');
              $img = 'images/commsyicons/32x32/discussion.png';
              break;
           case 'GROUP':
              $text .= $this->_translator->getMessage('COMMON_ONE_GROUP');
              $img = 'images/commsyicons/32x32/group.png';
              break;
           case 'INSTITUTION':
              $text .= $this->_translator->getMessage('COMMON_ONE_INSTITUTION');
              $img = '';
              break;
           case 'MATERIAL':
              $text .= $this->_translator->getMessage('COMMON_ONE_MATERIAL');
              $img = 'images/commsyicons/32x32/material.png';
              break;
           case 'PROJECT':
              $text .= $this->_translator->getMessage('COMMON_ONE_PROJECT');
              $img = '';
              break;
           case 'TODO':
              $text .= $this->_translator->getMessage('COMMON_ONE_TODO');
              $img = 'images/commsyicons/32x32/todo.png';
              break;
           case 'TOPIC':
              $text .= $this->_translator->getMessage('COMMON_ONE_TOPIC');
              $img = 'images/commsyicons/32x32/topic.png';
              break;
           case 'USER':
              $text .= $this->_translator->getMessage('COMMON_USER');
              $img = 'images/commsyicons/32x32/user.png';
              break;
           default:
              $text .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_detail_view('.__LINE__.') ';
              $img = '';
              break;
        }
        $link_creator_text = $text.' - '.$this->_translator->getMessage('COMMON_EDIT_BY').' '.
                                    $fullname.', '.
                                    $link_created;
         $module = Type2Module($type);
         if ($module == CS_USER_TYPE){
            $link_title = $this->_text_as_html_short($full_item->getFullName());
         }else{
            $link_title = $this->_text_as_html_short($full_item->getTitle());
         }
         $params = array();
         $params['iid'] = $linked_iid;
         $html .= '<div style="float:left;">'.ahref_curl( $full_item->getContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       '<img src="' . $img . '" style="padding-right:3px;" title="' . $link_creator_text . '"/>',
                                       $link_creator_text,
                                       '_self',
                                       $fragment,
                                       '',
                                       '',
                                       '',
                                       '',
                                       '',
                                       '',
                                       '').'</div>';
         $html .= ahref_curl( $full_item->getContextID(),
                                       $module,
                                       'detail',
                                       $params,
                                       $link_title,
                                       $link_creator_text,
                                       '_self',
                                       $fragment,
                                       '',
                                       '',
                                       '',
                                       '',
                                       '',
                                       '',
                                       '').$this->_getItemChangeStatus($full_item,$full_item->getContextID());
         $html .= '<br/><span style="font-size:8pt;">('.$this->_translator->getMessage('COMMON_ROOM').': ';
         $html .= ahref_curl( $full_item->getContextID(),
                                       'home',
                                       'detail',
                                       $params,
                                       $room_title,
                                       $room_title,
                                       '_self',
                                       $fragment,
                                       '',
                                       '',
                                       '',
                                       '',
                                       '',
                                       '',
                                       '');
         $html .= ')</span>'.LF;
         $html .= '   </td>'.LF;
         $html .= '   </tr>'.LF;
      }

      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getItemTitle($item){
      $title = $item->getTitle();
      $title = $this->_compareWithSearchText($title);
      $params = array();
      $params['iid'] = $item->getItemID();
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_ANNOUNCEMENT_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($title),
                           '', '', '', '', '', '', '', '',
                           CS_ANNOUNCEMENT_TYPE.$item->getItemID());
      unset($params);
      if ( !$this->_environment->inPrivateRoom() and !$item->isNotActivated()) {
         $title .= $this->_getItemChangeStatus($item);
         $title .= $this->_getItemAnnotationChangeStatus($item);
      }
      return $title;
   }

}
?>