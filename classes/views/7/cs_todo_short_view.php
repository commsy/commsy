<?PHP
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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
   function cs_todo_short_view ($params) {
      $this->cs_home_view($params);
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
      $html .= '      <td '.$style.' style="font-size:10pt; width: 53%;">'.$this->_getItemTitle($item).$fileicons.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width: 12%;">'.$this->_getDateInLang($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width: 15%;">'.$this->_getStatus($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width: 20%;">'.$this->_getProcessors($item).'</td>'.LF;
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
      if ($context->withTodoManagment()){
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

         if($done_percentage <= 100){
            $style = ' height: 8px; background-color: #75ab05; ';
            $done_time .= '      <div style="border: 1px solid #444;  margin-left: 0px; height: 8px; width: 70px;">'.LF;
            if ( $done_percentage >= 30 ) {
               $done_time .= '         <div style="font-size: 2pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">&nbsp;</div>'.LF;
            } else {
               $done_time .= '         <div style="font-size: 2pt; '.$style.'width:'.$done_percentage.'%; color:#000000;">&nbsp;</div>'.LF;
            }
            $done_time .= '      </div>'.LF;
         }elseif($done_percentage <= 120){
         	$done_percentage = (100 / $done_percentage) *100;
            $style = ' height: 10px; border: 1px solid #444; background-color: #f2f030; ';
            $done_time .= '         <div style="width: 70px; font-size: 2pt; '.$style.' color:#000000;">'.LF;
            $done_time .= '      <div style="border-right: 1px solid #444; margin-left: 0px; height:10px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
            $done_time .= '      </div>'.LF;
            $done_time .= '</div>'.LF;
         }else{
         	$done_percentage = (100 / $done_percentage) *100;
            $style = ' height: 8px; border: 1px solid #444; background-color: #f23030; ';
            $done_time .= '         <div style="width: 70px; font-size: 2pt; '.$style.' color:#000000;">'.LF;
            $done_time .= '      <div style="border-right: 1px solid #444; margin-left: 0px; height:8px;  background-color:none; width:'.$done_percentage.'%;">'.LF;
            $done_time .= '      </div>'.LF;
            $done_time .= '</div>'.LF;
         }

         if ($done_percentage > 200){
            $done_percentage = 200;
         }

          if ($step_minutes >0 ){
            $status = $done_time;
         }else{
            $status = $item->getStatus();
         }
      }else{
         $status = $item->getStatus();
      }
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
	           if ( stristr(strtolower($file->getFilename()),'png')
	                 or stristr(strtolower($file->getFilename()),'jpg')
	                 or stristr(strtolower($file->getFilename()),'jpeg')
	                 or stristr(strtolower($file->getFilename()),'gif')
	               ) {
                      $this->_with_slimbox = true;
	                   $file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
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
}
?>