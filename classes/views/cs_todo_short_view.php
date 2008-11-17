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

include_once('classes/cs_home_view.php');
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
      $environment = $params['environment'];
      $with_modifying_actions = true;
      if ( isset($params['with_modifying_actions']) ) {
         $with_modifying_actions = $params['with_modifying_actions'];
      }
      $this->cs_home_view($environment, $with_modifying_actions);
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
      $status = $item->getStatus();
      return $status;
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