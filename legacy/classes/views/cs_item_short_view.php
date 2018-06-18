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

$this->includeClass(HOME_VIEW);
include_once('classes/cs_reader_manager.php');
include_once('functions/text_functions.php');

/**
 *  class for CommSy short list view: items
 */
class cs_item_short_view extends cs_home_view {

   /** constructor: cs_item_list_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_home_view::__construct($params);
   }

   function _getTableheadAsHTML () {
      $html  = '   <tr class="head">'.LF;
      $html .= '      <td class="head" colspan="2">';
      $html .= '<span style="font-weight: bold;">';
      $html .= $this->_translator->getMessage('ITEM_TITLE_SHORT');
      $html .= '</span>';
      $html .= $this->_getDescriptionAsHTML();
      $html .= '</td>'.LF;
      $html .= '      <td class="head">';
      $html .= $this->_translator->getMessage('COMMON_MODIFIED_AT');
      $html .= $this->_getDescriptionAsHTML();
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      $this->_setColspan(3);

      return $html;
   }

   /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    *
    * @author CommSy Development Group
    */
   function _getDescriptionAsHTML () {
      $all = $this->getCountAll();
      $list = $this->getList();
      $shown = $list->getCount();
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
   function _getItemAsHTML ($item) {
      $html  = '   <tr>'.LF;
      $item_type = $item->getItemType();
      if ($item_type == 'label') {
         $item_type = $item->getItemType();
      }
      switch ($item_type) {
         case 'announcement':
            $html = $this->_getAnnouncementItemAsShortHtml($item);
            break;
         case 'user':
            $html = $this->_getUserItemAsShortHtml($item);
            break;
         case 'topic':
            $html = $this->_getTopicItemAsShortHtml($item);
            break;
         case 'institution':
            $html = $this->_getInstitutionItemAsShortHtml($item);
            break;
         case 'material':
            $html = $this->_getMaterialItemAsShortHtml($item);
            break;
      }
      $html .= '   </tr>'.LF;
      return $html;
   }

   function _getAnnouncementItemAsShortHTML ($item) {
      $html = '';
      $html .= '      <td>'.$this->_translator->getMessage('COMMON_ANNOUNCEMENT').': '.'</td>'.LF;
      $html .= '      <td>'.$this->_getTitle($item,$item->getTitle(),CS_ANNOUNCEMENT_TYPE).'</td>'.LF;
      $html .= $this->_getDateColumn($item);
      return $html;
   }

   function _getTopicItemAsShortHTML ($item) {
      $html = '';
      $html .= '      <td>'.$this->_translator->getMessage('COMMON_TOPIC').': '.'</td>'.LF;
      $html .= '      <td width="80%">'.$this->_getTitle($item,$item->getTitle(),CS_TOPIC_TYPE).'</td>'.LF;
      $html .= $this->_getDateColumn($item);
      return $html;
   }

   function _getInstitutionItemAsShortHTML ($item) {
      $html = '';
      $html .= '      <td>'.$this->_translator->getMessage('INSTITUTION').': '.'</td>'.LF;
      $html .= '      <td>'.$this->_getTitle($item,$item->getTitle(),'institution').'</td>'.LF;
      $html .= $this->_getDateColumn($item);
      return $html;
   }

   function _getUserItemAsShortHTML ($item) {
      $html = '';
      $html .= '      <td>'.$this->_translator->getMessage('COMMON_USER').': '.'</td>'.LF;
      $html .= '      <td>'.$this->_getTitle($item,$item->getFullName(),'contact').'</td>'.LF;
      $html .= $this->_getDateColumn($item);
      return $html;
   }

   function _getMaterialItemAsShortHTML ($item) {
      $current_user = $this->_environment->getCurrentUser();
      $html = '';
      $html .= '      <td>'.$this->_translator->getMessage('COMMON_MATERIAL').': '.'</td>'.LF;
      if ($current_user->isUser() or $item->isPublished()) {
         $html .= '      <td>'.$this->_getTitle($item,$item->getTitle(),'material').' '.$this->_getFiles($item).'</td>'.LF;
      } else {
         $html .= '      <td><span class="disabled">'.$this->_getTitle($item,$item->getTitle(),'material').'</span></td>'.LF;
      }
      $html .= $this->_getDateColumn($item);
      return $html;
   }

   function _getDateColumn ($item) {
      $html = '      <td width="1%">'.$this->_text_as_html_short($this->_translator->getDateInLang($item->getModificationDate())).'</td>'.LF;
      return $html;
   }

   /** get the formatted text of the item entry
    * this method returns the item author in the right formatted style
    *
    * @return string author
    *
    * @author CommSy Development Group
    */
   function _getItemText ($text) {
      text;
      return $text;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getType ($item) {
      $title_text = $this->_text_as_html_short($item->getType());
      $params = array();
      $params['iid'] = $item->getItemID();
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           type2module($item->getType()),
                           'detail',
                           $params,
                           $title_text);
      unset($params);
      $title .= $this->_getChangeStatus($item);
      $title .= $this->_getAnnotationChangeStatus($item);
      return $title;
   }

   /** get the file list of the item
    * this method returns the item file list in the right formatted style
    *
    * @return string file list
    *
    * @author CommSy Development Group
    */
   function _getFiles ($item) {
      $file_list='';
      $files = $item->getFileList();
      $file = $files->getFirst();
      while($file){
         $url = $file->getUrl();
         $displayname = $file->getDisplayName();
         $filesize = $file->getFileSize();
         $fileicon = $file->getFileIcon();
         $file_list.='<a href="'.$url.'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
         $file = $files->getNext();
      }
      return $file_list;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getTitle ($item,$title,$module) {
      $title_text = $this->_text_as_html_short($title);
      $params = array();
      $params['iid'] = $item->getItemID();
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           $module,
                           'detail',
                           $params,
                           $title_text);
      unset($params);
#      $title .= $this->_getChangeStatus($item);
#      $title .= $this->_getAnnotationChangeStatus($item);
      return $title;
   }

// material methods
   /** get the lable of the item
    * this method returns the item lable in the right formatted style
    *
    * @return string file lable
    *
    * @author CommSy Development Group
    */
   function _getLabel ($item) {
      $label = $item->getLabel();
      if (empty($label)) {
         $label='';
      }
      return $label;
   }

   /** get the publishing info of the item
    * this method returns the item publishing info in the right formatted style
    *
    * @return string publishing info
    *
    * @author CommSy Development Group
    */
   function _getPublishingInfo ($item) {
      $publishing_info = '';
      $user = $this->_environment->getCurrentUser();
      if ($this->_environment->inCommunityRoom() and !$item->isPublished() and !$user->isUser() ){
         $publishing_info = $this->_translator->getMessage('MATERIAL_NOT_PUBLISHED');
      }
      return $publishing_info;
   }

   /** get the description of the item
    * this method returns the item description in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getDescription ($item, $module) {
      $len = 40;
      $description = $item->getDescription();
      if ( empty($description) ) {
         $description = '&nbsp;';
      } elseif (mb_strlen($description) > $len) {
         $description = chunkText($this->_cleanDataFromTextArea($description),$len).' ... ';
         $params = array();
         $params['iid'] = $item->getItemID();
         $description .= '('.ahref_curl( $this->_environment->getCurrentContextID(),
                                         $module,
                                         'detail',
                                         $params,
                                         $this->_translator->getMessage('COMMON_MORE')).')';
         unset($params);
      } else {
         $description = $this->_cleanDataFromTextArea($item->getDescription());
      }

     return $this->_text_as_html_short($description);
   }
}
?>