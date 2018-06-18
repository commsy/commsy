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

$this->includeClass(ROOM_INDEX_VIEW);

/**
 *  class for CommSy list view: discussion
 */
class cs_discussion_index_view extends cs_room_index_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_room_index_view::__construct($params);
      $this->setTitle($this->_translator->getMessage('DISCUSSION_HEADER'));
      $this->setActionTitle($this->_translator->getMessage('COMMON_DISCUSSION'));
      $this->setColspan(5);
   }

   /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function setList ($list) {
       $this->_list = $list;
       if (!empty($this->_list)){
          $id_array = array();
          $item = $list->getFirst();
          while($item){
             $id = $item->getModificatorID();
             if (!in_array($id, $id_array)){
                $id_array[] = $id;
             }
             $item = $list->getNext();
          }
          $user_manager = $this->_environment->getUserManager();
          $user_manager->getRoomUserByIDsForCache($this->_environment->getCurrentContextID(),$id_array);
       }
    }

   function getAdditionalRestrictionTextAsHTML(){
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withActivatingContent()){
         $activation_limit= $this->getActivationLimit();
         if ( $activation_limit == 2 ){
            $this->_additional_selects = true;
            $html_text ='<tr>'.LF;
            $html_text .='<td>'.LF;
            $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_ACTIVATION_RESTRICTION').': </span>';
            $html_text .='</td>'.LF;
            $html_text .='<td style="text-align:right;">'.LF;
            $html_text .= '<span>'.$this->_translator->getMessage('COMMON_SHOW_ONLY_ACTIVATED_ENTRIES').'</span>';
            $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
            $new_params = $params;
            $new_params['selactivatingstatus'] = 1;
            $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
            $html_text .='</td>'.LF;
            $html_text .='</tr>'.LF;
            $html .= $html_text;
         }
      }
      return $html;
   }

   function _getTableheadAsHTML () {
      include_once('functions/misc_functions.php');
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;
      $html = '   <tr class="head">'.LF;
      $current_context = $this->_environment->getCurrentContextItem();
	  $with_assessment = false;
	  if($current_context->isAssessmentActive()) {
	  	$with_assessment = true;
	  }

	  if($with_assessment) {
	  	 $html .= '      <td class="head" style="width:45%;" colspan="2">';
	  } else {
	  	 $html .= '      <td class="head" style="width:53%;" colspan="2">';
	  }
      if ( $this->getSortKey() == 'title' ) {
         $params['sort'] = 'title_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'title_rev' ) {
         $params['sort'] = 'title';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'title';
         $picture ='&nbsp;';
      }
      if ( empty($params['download'])
           or $params['download'] != 'zip'
         ) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                $params, $this->_translator->getMessage('COMMON_TITLE'), '', '', $this->getFragment(),'','','','class="head"');
      } else {
         $html .= $this->_translator->getMessage('COMMON_TITLE');
      }
      $html .= $picture;
      $html .= '</td>'.LF;

      $html .= '      <td style="width:15%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'numposts' ) {
         $params['sort'] = 'numposts_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'numposts_rev' ) {
         $params['sort'] = 'numposts';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'numposts';
         $picture ='&nbsp;';
      }
      if ( empty($params['download'])
           or $params['download'] != 'zip'
         ) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                $params, $this->_translator->getMessage('DISCUSSION_ARTICLES'), '', '', $this->getFragment(),'','','','class="head"');
      } else {
         $html .= $this->_translator->getMessage('DISCUSSION_ARTICLES');
      }
      $html .= $picture;
      $html .= '</td>'.LF;

      $html .= '      <td style="width:13%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'latest' ) {
         $params['sort'] = 'latest_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'latest_rev' ) {
         $params['sort'] = 'latest';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'latest';
         $picture ='&nbsp;';
      }
      if ( empty($params['download'])
           or $params['download'] != 'zip'
         ) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                $params, $this->_translator->getMessage('COMMON_EDIT_AT'), '', '', $this->getFragment(),'','','','class="head"');
      } else {
         $html .= $this->_translator->getMessage('COMMON_EDIT_AT');
      }
      $html .= $picture;
      $html .= '</td>'.LF;
      $html .= '      <td style="width:15%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'creator' ) {
         $params['sort'] = 'creator_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'creator_rev' ) {
         $params['sort'] = 'creator';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'creator';
         $picture ='&nbsp;';
      }
      if ( empty($params['download'])
           or $params['download'] != 'zip'
         ) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                 $params, $this->_translator->getMessage('COMMON_EDIT_BY'), '', '', $this->getFragment(),'','','','class="head"');
      } else {
         $html .= $this->_translator->getMessage('COMMON_EDIT_BY');
      }
      $html .= $picture;
      $html .= '</td>'.LF;

	  // assessment
	  if($with_assessment) {
	  	  $html .= '<td style="15%; font-size:8pt;" class="head">';
		  if($this->getSortKey() == 'assessment') {
		  	$params['sort'] = 'assessment_rev';
			$picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
		  } elseif($this->getSortKey() == 'assessment_rev') {
		  	$params['sort'] = 'assessment';
			$picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
		  } else {
		  	$params['sort'] = 'assessment';
			$picture = '&nbsp;';
		  }
	      if ( empty($params['download'])
	           or $params['download'] != 'zip'
	         ) {
	         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
	                             $params, $this->_translator->getMessage('COMMON_ASSESSMENT_INDEX'), '', '', $this->getFragment(),'','','','class="head"');
	      } else {
	         $html .= $this->_translator->getMessage('COMMON_ASSESSMENT_INDEX');
	      }
	      $html .= $picture;
	      $html .= '</td>'.LF;
	  }

      $html .= '   </tr>'.LF;

      return $html;
   }


   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" colspan="3"><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="foot_left" colspan="3" style="vertical-align:middle;">'.LF;
         $html .= '<span class="select_link">[</span>';
         $params = $this->_environment->getCurrentParameterArray();
         $params['select'] = 'all';
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_ALL_ENTRIES'), '', '', $this->getFragment(),'','','','class="select_link"');
         $html .= '<span class="select_link">]</span>'.LF;
// if room is archived deactivate dropdown
		 $context = $this->_environment->getCurrentContextItem();
         if(!($context->isProjectRoom() and $context->isClosed())){
         	$html .= $this->_getViewActionsAsHTML();
         }
         unset($context);
      }
      $html .= '</td>'.LF;
	  $current_context = $this->_environment->getCurrentContextItem();
	  if($current_context->isAssessmentActive()) {
	  	$html .= '<td class="foot_right" colspan="3" style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
	  } else {
	  	$html .= '<td class="foot_right" colspan="2" style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
	  }
      if ( $this->hasCheckboxes() ) {
         if (count($this->getCheckedIDs())=='1'){
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED_ONE',count($this->getCheckedIDs()));
         }else{
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED',count($this->getCheckedIDs()));
         }
      }
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;

   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item,$pos=0,$with_links=true) {
      $html = '';
      $shown_entry_number = $pos;
      $shown_entry_number = $pos + $this->_count_headlines;
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      if ($this->_clipboard_mode){
         $sort_criteria = $item->getContextID();
         if ( $sort_criteria != $this->_last_sort_criteria ) {
            $this->_last_sort_criteria = $sort_criteria;
            $this->_count_headlines ++;
            $room_manager = $this->_environment->getRoomManager();
            $sort_room = $room_manager->getItem($sort_criteria);
            if ( empty($sort_room) ) {
               $room_manager = $this->_environment->getPrivateRoomManager();
               $sort_room = $room_manager->getItem($sort_criteria);
            }
            $html .= '                     <tr class="list"><td '.$style.' width="100%" style="font-weight:bold;" colspan="5">'.LF;
            if ( !empty($sort_room) ) {
               if ( $sort_room->isCommunityRoom() ) {
                  $html .= '                        '.$this->_translator->getMessage('COPY_FROM').'&nbsp;'.$this->_translator->getMessage('COMMON_COMMUNITY_ROOM_TITLE').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
               } elseif( $sort_room->isPrivateRoom() ){
                  $user = $this->_environment->getCurrentUserItem();
                  $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PRIVATEROOM').'&nbsp;"'.$user->getFullname().'"'."\n";
               }elseif( $sort_room->isGroupRoom() ){
                 $html .= '                        '.$this->_translator->getMessage('COPY_FROM_GROUPROOM').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
               }else {
                  $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PROJECTROOM').'&nbsp;"'.$sort_room->getTitle().'"'."\n";
               }
            }
            $html .= '                     </td></tr>'."\n";
            if ( $style=='class="odd"' ){
               $style='class="even"';
            }else{
               $style='class="odd"';
            }
         }
      }
      $html  .= '   <tr class="list">'.LF;
      $checked_ids = $this->getCheckedIDs();
      $dontedit_ids = $this->getDontEditIDs();
      $key = $item->getItemID();
      $fileicons = $this->_getItemFiles($item, $with_links);
      if ( !empty($fileicons) ) {
         $fileicons = ' '.$fileicons;
      }
      if ( !(isset($_GET['mode']) and $_GET['mode']=='print')
           or ( !empty($download)
                and $download == 'zip'
              )
         ) {
         $html .= '      <td '.$style.' style="vertical-align:middle;" width="2%">'.LF;
         if ( empty($download)
              or $download != 'zip'
            ) {
            $html .= '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" type="checkbox" onClick="quark(this)" name="attach['.$key.']" value="1"';
            $user = $this->_environment->getCurrentUser();
            if($item->isNotActivated() and !($item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
               $html .= ' disabled="disabled"'.LF;
            }elseif ( isset($checked_ids)
                 and !empty($checked_ids)
                 and in_array($key, $checked_ids)
               ) {
               $html .= ' checked="checked"'.LF;
               if ( in_array($key, $dontedit_ids) ) {
                  $html .= ' disabled="disabled"'.LF;
               }
            }
            $html .= '/>'.LF;
            $html .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
         }
         $html .= '      </td>'.LF;
         if ($item->isNotActivated()){
            $title = $item->getTitle();
            $title = $this->_compareWithSearchText($title);
            $user = $this->_environment->getCurrentUser();
            if($item->getCreatorID() == $user->getItemID() or $user->isModerator()){
               $params = array();
               $params['iid'] = $item->getItemID();
               $title = ahref_curl( $this->_environment->getCurrentContextID(),
                                  CS_DISCUSSION_TYPE,
                                  'detail',
                                  $params,
                                  $title,
                                  '','', '', '', '', '', '', '',
                                  CS_DISCUSSION_TYPE.$item->getItemID());
               unset($params);
            }
            $activating_date = $item->getActivatingDate();
            if (strstr($activating_date,'9999-00-00')){
               $title .= BR.$this->_translator->getMessage('COMMON_NOT_ACTIVATED');
            }else{
               $title .= BR.$this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
            }
            $title = '<span class="disabled">'.$title.'</span>';
            $html .= '      <td '.$style.'>'.$title.LF;
         }else{
             if($with_links) {
                $html .= '      <td '.$style.'>'.$this->_getItemTitle($item).$fileicons.LF;
             } else {
                $title = $this->_text_as_html_short($item->getTitle());
                $html .= '      <td '.$style.'>'.$title.LF;
             }
         }
      } else {
         $html .= '      <td colspan="2" '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).$fileicons.'</td>'.LF;
      }
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getFastItemArticleCount($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemLastArticleDate($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemModificator($item).'</td>'.LF;

	  // assessment
		 $current_context = $this->_environment->getCurrentContextItem();
	  	 if($current_context->isAssessmentActive()) {
			 // display stars
			 $assessment_manager = $this->_environment->getAssessmentManager();
			 $assessment = $assessment_manager->getAssessmentForItemAverage($item);
			 if(isset($assessment[0])) {
			 	$assessment = sprintf('%1.1f', (float) $assessment[0]);
			 } else {
			 	$assessment = 0;
			 }
		  	 $php_version = explode('.', phpversion());
			 if($php_version[0] >= 5 && $php_version[1] >= 3) {
			 	// if php version is equal to or above 5.3
				$stars_full = round($assessment, 0, PHP_ROUND_HALF_UP);
			 } else {
				// if php version is below 5.3
				$stars_full = round($assessment);
			 }
			 $stars = '';
			 for($i = 0; $i < $stars_full; $i++) {
			  	$stars .= '<span><img src="images/commsyicons/32x32/star_filled.png" data-tooltip="sticky_' . $item->getItemID() . '" style="width:14px; height:14px"/></span>'.LF;
			 }
			 for($i = $stars_full; $i < 5; $i++) {
			 	$stars .= '<span><img src="images/commsyicons/32x32/star_unfilled.png" data-tooltip="sticky_' . $item->getItemID() . '" style="width:14px; height:14px"/></span>'.LF;
			 }
			 $html .= '<td ' . $style . '>' . $stars . '</td>'.LF;
		 }

      $html .= '   </tr>'.LF;

      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemTitle ($item) {
      $title = $item->getTitle();
      $title_text = $this->_compareWithSearchText($title);
      $params = array();
      $params['iid'] = $item->getItemID();
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DISCUSSION_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($title_text),
                           '','', '', '', '', '', '', '',
                           CS_DISCUSSION_TYPE.$item->getItemID());

      unset($params);
      if ($item->isClosed()) {
         $title .= ' <span class="closed">('.$this->_translator->getMessage('DISCUSSION_IS_CLOSED').')</span>';
      }
      if ( !$this->_environment->inPrivateRoom() and !$item->isNotActivated()) {
         $title .= $this->_getItemChangeStatus($item);
     }
      return $title;
   }



   /** get article count of a discussion
    * Returns the total and unread number of articles
    * for a discussion-item in a formatted string.
    *
    * @return string article_count
    *
    * @author CommSy Development Group
    */
   function _getItemArticleCount ($item) {
     $all_articles = $item->getAllArticlesCount();
     $unread_articles = $item->getUnreadArticles();
     return $all_articles.' ('.$unread_articles.' <span class="desc">'.$this->_translator->getMessage('COMMON_UNREAD').'</span>)';
   }

   function _getFastItemArticleCount ($item) {
     $array = $item->getAllAndUnreadArticles();
     return $array['count'].' ('.$array['unread'].' <span class="desc">'.$this->_translator->getMessage('COMMON_UNREAD').'</span>)';
   }
   /** get the date of last added article
    * this method returns the number in the right formatted style
    *
    * @return date last_article_date
    *
    * @author CommSy Development Group
    */
   function _getItemLastArticleDate ($item) {
     $last_article_date = $item->getLatestArticleModificationDate();
     $last_article_date = getDateInLang($last_article_date);
     //$last_article_date = //'<span class="list_view_description">'.
                          //$this->_translator->getMessage('LAST_ARTICLE_DATE').
                          //'</span> '.
                          //$last_article_date;
     return $this->_text_as_html_short($last_article_date);
   }

   /** get the file list of the item
    * this method returns the item file list in the right formatted style
    *
    * @return string file list
    */
   function _getItemFiles($item, $with_links=true){
      $retour = '';
      $file_list='';
      $files = $item->getFileListWithFilesFromArticles();
      $files->sortby('filename');
      $file = $files->getFirst();
      $user = $this->_environment->getCurrentUser();
      while ($file) {
         $url = $file->getUrl();
         $displayname = $file->getDisplayName();
         $filesize = $file->getFileSize();
         $fileicon = $file->getFileIcon();
         if ($with_links and $this->_environment->inProjectRoom() || (!$this->_environment->inProjectRoom() and ($item->isPublished() || $user->isUser())) ) {
            if ( isset($_GET['mode'])
                 and $_GET['mode']=='print'
                 and ( empty($_GET['download'])
                       or $_GET['download'] != 'zip'
                     )
               ) {
               $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
            } else {
              if(in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
                      $this->_with_slimbox = true;
                      // jQuery
                      //$file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                      $file_list.='<a href="'.$url.'" rel="lightbox-gallery'.$item->getItemID().'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                      // jQuery
                  }else{
                     $file_list.='<a href="'.$url.'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
                  }
              }
         } else {
            $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
         }
         $file = $files->getNext();
      }
      return $retour.$file_list;
   }

   public function _getAdditionalViewActionsAsHTML () {
      $retour = '';
      $retour .= '   <option value="download">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DOWNLOAD').'</option>'.LF;
      include_once('functions/misc_functions.php');
      $retour .= plugin_hook_output_all('getAdditionalViewActionsAsHTML',array('module' => CS_MATERIAL_TYPE),LF);
      return $retour;
   }
}
?>