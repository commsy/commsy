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
class cs_announcement_index_view extends cs_index_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   public function __construct ($params) {
      cs_index_view::__construct($params);
      $this->setTitle($this->_translator->getMessage('COMMON_ANNOUNCEMENTS'));
      $this->setActionTitle($this->_translator->getMessage('COMMON_ANNOUNCEMENT'));
      $this->_colspan = '4';
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

   /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function setList ($list) {
       // ------------------
       // --->UTF8 - OK<----
       // ------------------
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

   function _getTableheadAsHTML () {
      include_once('functions/misc_functions.php');
      $params = $this->_environment->getCurrentParameterArray();
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

      $html .= '      <td style="width:13%; font-size:8pt;" class="head" >';
      if ( $this->getSortKey() == 'modified' ) {
         $params['sort'] = 'modified_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'modified_rev' ) {
         $params['sort'] = 'modified';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'modified';
         $picture ='&nbsp;';
      }
      if ( empty($params['download'])
           or $params['download'] != 'zip'
         ) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('COMMON_MODIFIED_AT'), '', '', $this->getFragment(),'','','','class="head"');
      } else {
         $html .= $this->_translator->getMessage('COMMON_MODIFIED_AT');
      }
      $html .= $picture;
      $html .= '</td>'.LF;

      $html .= '      <td style="width:24%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'modificator' ) {
         $params['sort'] = 'modificator_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'modificator_rev' ) {
         $params['sort'] = 'modificator';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'modificator';
         $picture ='&nbsp;';
      }
      if ( empty($params['download'])
           or $params['download'] != 'zip'
         ) {
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('COMMON_MODIFIED_BY'), '', '', $this->getFragment(),'','','','class="head"');
      } else {
         $html .= $this->_translator->getMessage('COMMON_MODIFIED_BY');
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
   // @segment-end 47311


   // @segment-begin 85979 _getTablefootAsHTML():action-box-under-annoucement-index;-see#68626,#51410,#21229
   function _getTablefootAsHTML() {
      $html  = '   <tr id="index_table_foot" class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" colspan="2"><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="foot_left" colspan="2" style="vertical-align:middle;">'.LF;
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
   // @segment-end 85979

   // @segment-begin 89418 _getItemAsHTML($item,$pos=0)-odd/even-for-announcement-entry-in-index
   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML($item,$pos=0,$with_links=TRUE) {
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
            $room_manager = $this->_environment->getProjectManager();
            $sort_room = $room_manager->getItem($sort_criteria);
            $html .= '                     <tr class="list"><td '.$style.' width="100%" style="font-weight:bold;" colspan="5">'."\n";
            if ( empty($sort_room) ) {
               $community_manager = $this->_environment->getCommunityManager();
               $sort_community = $community_manager->getItem($sort_criteria);
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM').'&nbsp;'.$this->_translator->getMessage('COMMON_COMMUNITY_ROOM_TITLE').'&nbsp;"'.$sort_community->getTitle().'"'."\n";
            } elseif( $sort_room->isPrivateRoom() ){
               $user = $this->_environment->getCurrentUserItem();
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PRIVATEROOM').'&nbsp;"'.$user->getFullname().'"'.LF;
            }elseif( $sort_room->isGroupRoom() ){
              $html .= '                        '.$this->_translator->getMessage('COPY_FROM_GROUPROOM').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
            }else {
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PROJECTROOM').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
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
      $download = $this->_environment->getValueOfParameter('download');
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
         if ( $item->isNotActivated() ) {
            $title = $item->getTitle();
            $title = $this->_compareWithSearchText($title);
            $user = $this->_environment->getCurrentUser();
            if($item->getCreatorID() == $user->getItemID() or $user->isModerator()){
               $params = array();
               $params['iid'] = $item->getItemID();
               $title = ahref_curl( $this->_environment->getCurrentContextID(),
                                  CS_ANNOUNCEMENT_TYPE,
                                  'detail',
                                  $params,
                                  $title,
                                  '','', '', '', '', '', '', '',
                                  CS_ANNOUNCEMENT_TYPE.$item->getItemID());
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

      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemModificationDate($item).'</td>'.LF;
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

   public function _getAdditionalViewActionsAsHTML () {
      $retour = '';
      $retour .= '   <option value="download">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DOWNLOAD').'</option>'.LF;
      include_once('functions/misc_functions.php');
      $retour .= plugin_hook_output_all('getAdditionalViewActionsAsHTML',array('module' => CS_MATERIAL_TYPE),LF);
      return $retour;
   }
}
?>