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
class cs_buzzword_index_view extends cs_index_view {


var $_item = NULL;

   // @segment-begin 80628 cs_announcement_index_view($environment, $with_modifying_actions)-uses-#77035,#48753,#60854
   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   public function __construct ($params) {
      cs_index_view::__construct($params);
      $this->setTitle($this->_translator->getMessage('COMMON_BUZZWORDS'));
      $this->setActionTitle($this->_translator->getMessage('COMMON_BUZZWORDS'));
      $this->_colspan = '5';
   }

   function setItem($item){
      $this->_item = $item;
   }



   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;

      $html .='<div id="profile_content">'.LF;
      $html .= '<form style="padding:0px; margin:0px;" action="';
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['rem_item_text']);
      $html .= curl($this->_environment->getCurrentContextID(),
                    $this->_environment->getCurrentModule(),
                    $this->_environment->getCurrentFunction(),
                    $params
                   ).'" method="post">'.LF;
      $params = $this->_environment->getCurrentParameterArray();
      unset($params['attach_view']);
      unset($params['attach_type']);
      unset($params['rem_item_text']);
      $params['return_attach_buzzword_list']= 'true';
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           $this->_environment->getCurrentModule(),
                           $this->_environment->getCurrentFunction(),
                           $params,
                           'X',
                           '','', '', '', '', '', 'class="titlelink"');
      $html .='<div>'.LF;
      $html .= '<div class="profile_title" style="float:right">'.$title.'</div>';
      $html .= '<h2 id="profile_title">'.$this->_translator->getMessage('COMMON_BUZZWORD_NEW_ATTACH').'</h2>';
      $html .='</div>'.LF;
      $current_browser = mb_strtolower($this->_environment->getCurrentBrowser(), 'UTF-8');
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width= ' width:100%; padding-right:10px;';
      }else{
         $width= '';
      }

      $html .='<div style="width:100%; padding-top:0px; vertical-align:bottom;">'.LF;
      $html .= '<table class="list" style="width: 100%; border-collapse: collapse;" summary="Layout">'.LF;
      $html .= $this->_getContentAsHTML();
      $html .= '</table>'.LF;
      $html .= '<table class="list" style="width: 100%; border-collapse: collapse;" summary="Layout">'.LF;
      $html .= $this->_getTablefootAsHTML();
      $html .= '</table>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .= '</form>'.LF;
      $html .='</div>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      return $html;
   }

   function _getContentAsHTML() {
      if( $this->_environment->getCurrentFunction() == 'edit'){
         $session = $this->_environment->getSessionItem();
         $checked_buzzword_array = $session->getValue('cid'.$this->_environment->getCurrentContextID().'_'.$this->_environment->getCurrentModule().'_buzzword_ids');
      }else{
         $buzzword_list = $this->_item->getBuzzwordList();
         $checked_buzzword_array = array();
         if ($buzzword_list->getCount() > 0) {
            $buzzword_item = $buzzword_list->getFirst();
            while ($buzzword_item) {
               $checked_buzzword_array[] = $buzzword_item->getItemID();
               $buzzword_item = $buzzword_list->getNext();
            }
         }
      }
      if(isset($_POST['buzzwordlist'])){
         $checked_buzzword_array = array_keys($_POST['buzzwordlist']);
      }
      if(empty($checked_buzzword_array)){
         $checked_buzzword_array= array();
      }
      $html = '';
      $list = $this->getList();
      if ( !isset($list) || $list->isEmpty() ) {
         $html .= '<tr><td>'.$this->_translator->getMessage('COMMON_NO_CONTENT').'</td></tr>';

      } else {

         // Put items into an array representing the
         // future table layout
         $count = $list->getCount();
         $num_cols = 3;
         $num_rows = ceil($count/$num_cols);
         $layout_array = array();
         $item = $list->getFirst();
         for ( $col=1; $col<=$num_cols; $col++ ) {
            for ( $row=1; $row<=$num_rows; $row++ ) {
               if ( $item ) {
                  $checked_ids = $this->getCheckedIDs();
                  $dontedit_ids = $this->getDontEditIDs();
                  $key = $item->getItemID();
                  $text = '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" type="checkbox" name="buzzwordlist['.$key.']" value="1"';
                  if ( isset($checked_buzzword_array) and !empty($checked_buzzword_array) and in_array($key, $checked_buzzword_array)) {
                     $text .= ' checked="checked"'.LF;
                     if ( in_array($key, $dontedit_ids) ) {
                         $text .= ' disabled="disabled"'.LF;
                     }
                  }
                  $text .= '/>'.LF;
                  $text .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
                  $layout_array[$row][$col] = $text.'&nbsp;'.$this->_text_as_html_short($item->getTitle());
                  $item = $list->getNext();
               } else {
                  $layout_array[$row][$col] = '';
               }
            }
         }

         // Print out the table
         $r = 0;
         foreach ( $layout_array as $row ) {
            $r++;
            if ($r%2 == 0){
               $style='class="even"';
            }else{
               $style='class="odd"';
            }
            $html .= '<tr class="list">'.LF;
            foreach ( $row as $entry ) {
               if ( $r < $num_rows ) {
                  $html .= '   <td  '.$style.' width="33%">';
               } else {
                  $html .= '   <td  '.$style.' width="33%">';
               }
               $html .= $entry.'</td>'.LF;
            }
            $html .= '</tr>'.LF;
         }
         $r++;
         if ($r%2 == 0){
            $style='class="even"';
         }else{
            $style='class="odd"';
         }
      }
      if (!isset($style)){
         $style='class="odd"';
      }
      $html .= '<tr>'.LF;
      $html .= '<td colspan ="3" '.$style.' >'.LF;
      $html .= '<span class="infocolor">'.$this->_translator->getMessage('BUZZWORDS_NEW_BUTTON').':&nbsp;'.'</span>';
      $html .= '         <input style="font-size:10pt; width:150px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" name="attach_new_buzzword" value=""/>';
      $html .= '		 <input style="font-size:10pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_BUZZWORD_ADD').'"/>';
      
      $session_item = $this->_environment->getSessionItem();
      if($session_item->issetValue('buzzword_add_duplicated')) {
         $html .= '&nbsp;<span style="font-weight:bold;color:red;">'.$this->_translator->getMessage('COMMON_BUZZWORD_ADD_DUPLICATED').'</span>'.LF;
      }
      
      if($session_item->issetValue('buzzword_add')) {
         $html .= '<ul>';
         // iterate list
         $new_buzzword_list = $session_item->getValue('buzzword_add');
         $new_buzzword_item = $new_buzzword_list->getFirst();
         while($new_buzzword_item) {
            
            $params = $this->_environment->getCurrentParameterArray();
            $text = $this->_environment->getTextConverter()->text_as_html_short($new_buzzword_item);
            $params['rem_item_text'] = $text;
            $img = ahref_curl(   $this->_environment->getCurrentContextID(),
                                 $this->_environment->getCurrentModule(),
                                 $this->_environment->getCurrentFunction(),
                                 $params,
                 			     ' <img src="images/delete_restriction.gif" alt="x"/>');
            
            
            $html .= '<li>' . $text . $img . '</li>';
            $new_buzzword_item = $new_buzzword_list->getNext();
         }
         $html .= '</ul>';
      }
      unset($session_item);
      
      $html .= '</td>'.LF;
      $html .= '</tr>'.LF;
      return $html;
   }

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

   function _getTablefootAsHTML() {
      $html  = '   <tr id="index_table_foot" class="list">'.LF;
      $html .= '<td class="foot_left" colspan="2" style="vertical-align:middle;">'.LF;
      $html .= $this->_getViewActionsAsHTML();
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;

   }

   function _getViewActionsAsHTML () {
      $html = '   <input type="hidden" name="return_attach_buzzword_list" value="true"/>'.LF;
      $html .= '<input type="submit" style="font-size:10pt;" name="option"';
      $html .= ' value="'.$this->_translator->getMessage('COMMON_BUZZWORD_ATTACH').'"';
      $html .= '/>'.LF;

      return $html;
   }

}
?>