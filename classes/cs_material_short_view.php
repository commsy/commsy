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

include_once('classes/cs_home_view.php');
include_once('functions/text_functions.php');

/**
 *  class for CommSy list view: news
 */
class cs_material_short_view extends cs_home_view {

   /** array of ids in clipboard*/
   var $_clipboard_id_array=array();

   var $_shown_entry_number = 0;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param string  viewname               e.g. news_index
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_material_short_view ($environment, $with_modifying_actions) {
      $this->cs_home_view($environment, $with_modifying_actions);
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_MATERIAL_TYPE,
                           'index',
                           '',
                           $this->_translator->getMessage('COMMON_MATERIAL_INDEX'),'','','','','','','class="head"');
      $this->setViewTitle($title);
   }

   function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = (array)$cia;
   }

   function getClipboardIDArray() {
      return $this->_clipboard_id_array;
   }

   /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    */
   function _getDescriptionAsHTML() {
      $all = $this->getCountAll();
      $list = $this->getList();
      $shown = $list->getCount();
      if ($this->_environment->inProjectRoom()) {
         $context = $this->_environment->getCurrentContextItem();
         $period = $context->getTimeSpread();
         unset($context);
         return ' ('.$this->_translator->getMessage('COMMON_SHORT_VIEW_DESCRIPTION',$shown,$period,$all).')';
      } else {
         return ' ('.$this->_translator->getMessage('COMMON_SHORT_MATERIAL_VIEW_DESCRIPTION',$shown,$all).')';
      }
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML($item) {
      $style='';
      if ($this->_shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      $html  = '   <tr '.$style.'>'.LF;
      $this->_shown_entry_number ++;
      $html .= '      <td '.$style.' style="width:65%;">'.$this->_getItemTitle($item).LF;
      $html .= '          '.$this->_getItemFiles($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:23%;">'.$this->_getItemAuthor($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:12%;">'.$this->_getItemModificationDate($item).'</td>'.LF;
      $html .= '   </tr>'.LF;
      unset($item);
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
      $title_text = $item->getTitle();
      $title_text = $title_text;
      $user = $this->_environment->getCurrentUser();
      if (!$this->_environment->inProjectRoom() and !$item->isPublished() and !$user->isUser() ){
         $title = '<span class="disabled">'.$title_text.'</span>'."\n";
      } else {
         $params = array();
         $params['iid'] = $item->getItemID();
         $title = ahref_curl( $this->_environment->getCurrentContextID(),
                              'material',
                              'detail',
                              $params,
                              $this->_text_as_html_short($title_text));
         unset($params);
         if ($this->_environment->inProjectRoom()) {
            $title .= $this->_getItemChangeStatus($item);
            $title .= $this->_getItemAnnotationChangeStatus($item);
         }
      }
      unset($item);
      unset($user);
      return $title;
   }

   /** get the author of the item
    * this method returns the item author in the right formatted style
    *
    * @return string author
    *
    * @author CommSy Development Group
    */
   function _getItemAuthor($item){
      $author = $item->getAuthor();
      unset($item);
      return $this->_text_as_html_short($author);
   }

   /** get the publishing date of the item
    * this method returns the item publishing date in the right formatted style
    *
    * @return string publishing date
    *
    * @author CommSy Development Group
    */
   function _getItemPublishingDate($item){
      $publishing_date = $item->getPublishingDate();
      unset($item);
      return $this->_text_as_html_short($publishing_date);
   }

   /** get the modification-date of an item
    * Can be used in derived classes _getItemAsHTML()-methods
    * to display the modification date of an item in a
    * standardized manner.
    *
    * @return string modification_date
    *
    * @author CommSy Development Group
    */
   function _getItemModificationDate($item){
      if ( $item->getCreationDate() <> $item->getModificationDate() ) {
         $mod_date = $this->_translator->getDateInLang($item->getModificationDate());
      } else {
         $mod_date = $this->_translator->getDateInLang($item->getCreationDate());
      }
      unset($item);
      return $this->_text_as_html_short($mod_date);
   }

   /** get the file list of the item
    * this method returns the item file list in the right formatted style
    *
    * @return string file list
    */
   function _getItemFiles($item, $with_links=true){
      $retour = '';
      $file_list='';
      $files = $item->getFileListWithFilesFromSections();
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
         unset($file);
         $file = $files->getNext();
      }
      unset($user);
      unset($files);
      unset($item);
      return $retour.$file_list;
   }
}
?>