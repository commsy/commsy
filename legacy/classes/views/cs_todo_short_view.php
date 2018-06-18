<?PHP
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

$this->includeClass(HOME_VIEW);
include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: todo
 */
class cs_todo_short_view extends cs_home_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_home_view::__construct($params);
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_TODO_TYPE,
                           'index',
                           '',
                           $this->_translator->getMessage('COMMON_TODO_INDEX'),'','','','','','','class="head"');
      $this->setViewTitle($title);
   }

   /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    *
    * @author CommSy Development Group
    */
   function _getDescriptionAsHTML() {
      $all = $this->getCountAll();
      $list = $this->getList();
      $shown = $list->getCount();
      $context = $this->_environment->getCurrentContextItem();
      $period = $context->getTimeSpread();
      return ' ('.$this->_translator->getMessage('TODO_SHORT_VIEW_DESCRIPTION',$shown,$all).')';
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
   function _getItemAsHTML($item,$pos,$with_links=true) {
      $context = $this->_environment->getCurrentContextItem();
      $shown_entry_number = $pos;
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      $fileicons = $this->_getItemFiles($item, $with_links);
      if ( !empty($fileicons) ) {
         $fileicons = ' '.$fileicons;
      }
      $html  = '   <tr class="list">'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt; width: 35%;">'.$this->_getItemTitle($item).$fileicons.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width: 15%;">'.$this->_getStatus($item).'</td>'.LF;
      if ($context->withTodoManagement()){
         $html .= '      <td '.$style.' style="font-size:8pt; width: 10%;">'.$this->_getDateInLang($item).'</td>'.LF;
         $html .= '      <td '.$style.' style="font-size:8pt; width: 10%;">'.$this->_getItemProcess($item).'</td>'.LF;
      }else{
         $html .= '      <td '.$style.' style="font-size:8pt; width: 20%;">'.$this->_getDateInLang($item).'</td>'.LF;
      }
      $html .= '      <td '.$style.' style="font-size:8pt; width: 30%;">'.$this->_getProcessors($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }


   function _getItemProcess($item){
      $step_html = '';
      $step_minutes = 0;
      $step_item_list = $item->getStepItemList();
      if ( $step_item_list->isEmpty() ) {
         $status = $item->getStatus();
      } else {
         $step = $step_item_list->getFirst();
         $count = $step_item_list->getCount();
         $counter = 0;
         while ($step) {
            $counter++;
            $step_minutes = $step_minutes + $step->getMinutes();
            $step = $step_item_list->getNext();
         }
      }
      $done_time = '';
      $done_percentage = 100;
      if ($item->getPlannedTime() > 0){
         $done_percentage = $step_minutes / $item->getPlannedTime() * 100;
      }

      $tmp_message = $this->_translator->getMessage('COMMON_MINUTES');
      $step_minutes_text = $step_minutes;
      if (($step_minutes/60)>1 and ($step_minutes/60)<=8){
         $step_minutes_text = '';
         $exact_minutes = $step_minutes/60;
         $step_minutes = round($exact_minutes,1);
         if ($step_minutes != $exact_minutes){
            $step_minutes_text .= 'ca. ';
         }
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $step_minutes = str_replace('.',',',$step_minutes);
         }
         $step_minutes_text .= $step_minutes;
         $tmp_message = $this->_translator->getMessage('COMMON_HOURS');
         if ($step_minutes == 1){
            $tmp_message = $this->_translator->getMessage('COMMON_HOUR');
         }
       }elseif(($step_minutes/60)>8){
         $exact_minutes = ($step_minutes/60)/8;
         $step_minutes = round($exact_minutes,1);
         $step_minutes_text = '';
         if ($step_minutes != $exact_minutes){
            $step_minutes_text .= 'ca. ';
         }
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $step_minutes = str_replace('.',',',$step_minutes);
         }
         $step_minutes_text .= $step_minutes;
         $tmp_message = $this->_translator->getMessage('COMMON_DAYS');
         if ($step_minutes == 1){
            $tmp_message = $this->_translator->getMessage('COMMON_DAY');
         }
      }else{
         $step_minutes = round($step_minutes,1);
         if ($this->_translator->getSelectedLanguage() == 'de'){
            $step_minutes = str_replace('.',',',$step_minutes);
         }
      }
      $shown_time = $step_minutes_text.' '.$tmp_message;
      $display_time_text = $shown_time.' - '.round($done_percentage).'% '.$this->_translator->getMessage('TODO_DONE');

      if($done_percentage <= 100){
         $style = ' height: 8px; background-color: #75ab05; ';
         $done_time .= '      <div title="'.$display_time_text.'" style="border: 1px solid #444;  margin-left: 0px; height: 8px; width: 60px;">'.LF;
         if ( $done_percentage >= 30 ) {
            $done_time .= '         <div style="font-size: 2pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">&nbsp;</div>'.LF;
         } else {
            $done_time .= '         <div style="font-size: 2pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">&nbsp;</div>'.LF;
         }
         $done_time .= '      </div>'.LF;
      }elseif($done_percentage <= 120){
         $done_percentage = (100 / $done_percentage) *100;
         $style = ' height: 10px; border: 1px solid #444; background-color: #f2f030; ';
         $done_time .= '         <div title="'.$display_time_text.'" style="width: 60px; font-size: 2pt; '.$style.' color:#000000;">'.LF;
         $done_time .= '      <div style="border-right: 1px solid #444; margin-left: 0px; height:10px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
         $done_time .= '      </div>'.LF;
         $done_time .= '</div>'.LF;
      }else{
         $done_percentage = (100 / $done_percentage) *100;
         $style = ' height: 8px; border: 1px solid #444; background-color: #f23030; ';
         $done_time .= '         <div title="'.$display_time_text.'" style="width: 60px; font-size: 2pt; '.$style.' color:#000000;">'.LF;
         $done_time .= '      <div style="border-right: 1px solid #444; margin-left: 0px; height:8px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
         $done_time .= '      </div>'.LF;
         $done_time .= '</div>'.LF;
      }
      if ($item->getPlannedTime() > 0){
         $process = $done_time;
      }else{
      	$process = $shown_time;
      }
      return $process;
   }


   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemTitle($item){
      $title = $item->getTitle();
      $params = array();
      $params['iid'] = $item->getItemID();
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           'todo',
                           'detail',
                           $params,
                           $this->_text_as_html_short($title));
      unset($params);
      $title .= $this->_getItemChangeStatus($item);
      $title .= $this->_getItemAnnotationChangeStatus($item);
      $title .= $this->_getItemStepChangeStatus($item);
      return $title;
   }

   /** get the date of the item
    * this method returns the item date in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getDateInLang($item){
      $original_date = $item->getDate();
      $date = getDateInLang($original_date);
      $status = $item->getStatus();
      $actual_date = date("Y-m-d H:i:s");
      if ($status !=$this->_translator->getMessage('TODO_DONE') and $original_date < $actual_date){
         $date = '<span class="required">'.$date.'</span>';
      }
      if ($original_date == '9999-00-00 00:00:00'){
      	 $date = $this->_translator->getMessage('TODO_NO_END_DATE');
      }
      return $date;
   }

   /** get the status of the item
    * this method returns the item date in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getStatus($item){
      $user = $this->_environment->getCurrentUser();
      $context = $this->_environment->getCurrentContextItem();
      $status = $item->getStatus();
      return $status;
   }

   function _getItemFiles($item, $with_links=true){
      $retour = '';
      $file_list='';
      $files = $item->getFileListWithFilesFromSteps();
      $files->sortby('filename');
      $file = $files->getFirst();
      $user = $this->_environment->getCurrentUser();
      while ($file) {
         $url = $file->getUrl();
         $displayname = $file->getDisplayName();
         $filesize = $file->getFileSize();
         $fileicon = $file->getFileIcon();
         if ($with_links and $this->_environment->inProjectRoom() || (!$this->_environment->inProjectRoom() and ($item->isPublished() || $user->isUser())) ) {
            if ( isset($_GET['mode']) and $_GET['mode']=='print' ) {
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

   function _getProcessors($item){
     $user = $this->_environment->getCurrentUser();
     $html ='';
     $members = $item->getProcessorItemList();
      if ( $members->isEmpty() ) {
         $html .= '   <span class="disabled">'.$this->_translator->getMessage('TODO_NO_PROCESSOR').'</span>'.LF;
      } else {
         $member = $members->getFirst();
         if ( $member->isUser() ){
            $linktext = $member->getFullname();
            $params = array();
            $params['iid'] = $member->getItemID();
            if ( $this->_environment->inProjectRoom() and $member->maySee($user) ) {
               $html .= ahref_curl($this->_environment->getCurrentContextID(),
                             'user',
                             'detail',
                             $params,
                             $linktext);
            } else {
               $html .= '<span class="disabled">'.$linktext.'</span>';
            }
            unset($params);
         }
         $member = $members->getNext();
         while ($member) {
            if ( $member->isUser() ){
               $linktext = ', '.$member->getFullname();
               $member_title = $member->getTitle();
               $params = array();
               $params['iid'] = $member->getItemID();
               if ( $this->_environment->inProjectRoom() and $member->maySee($user) ) {
                  $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                'user',
                                'detail',
                                $params,
                                $linktext);
               } else {
                  $html .= '<span class="disabled">'.$linktext.'</span>';
               }
               unset($params);
            }
            $member = $members->getNext();
         }
      }
      return $html;

   }

	/**
    * returns the html link when list is shortened on home view
    *
    * @return string $html		- the html link code
    */
   function _getListShortenedLink() {
      $html = '';
      $style = '';
      if($this->getList()->getCount() % 2 == 0) {
         $style = 'class="odd"';
      } else {
         $style = 'class="even"';
      }

      $link = ahref_curl(   $this->_environment->getCurrentContextID(),
                            CS_TODO_TYPE,
                            'index',
                            array(),
                            $this->_translator->getMessage("HOME_RUBRIC_LIST_SHORTENED"));

      $html .= '<tr class="list"><td ' . $style . ' colspan="5">' . $link . '</td></tr>';

      return $html;
   }
}
?>