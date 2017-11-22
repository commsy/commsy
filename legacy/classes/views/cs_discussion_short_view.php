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

/**
 *  class for CommSy list view: news
 */
class cs_discussion_short_view extends cs_home_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_home_view::__construct($params);
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DISCUSSION_TYPE,
                           'index',
                           '',
                           $this->_translator->getMessage('COMMON_DISCUSSION_INDEX'),'','','','','','','class="head"');
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
      if ( $this->_environment->inProjectRoom() or $this->_environment->inGroupRoom() ) {
         $period = $context->getTimeSpread();
         $retour = ' ('.$this->_translator->getMessage('COMMON_SHORT_VIEW_DESCRIPTION', $shown, $period, $all).')';
      } elseif ( $this->_environment->inCommunityRoom() ) {
         if ( $shown != 1 ) {
            $retour = ' ('.$this->_translator->getMessage('COMMON_SHORT_VIEW_DESCRIPTION_CR', $shown, $all).')';
         } else {
            $retour = ' ('.$this->_translator->getMessage('COMMON_SHORT_VIEW_DESCRIPTION_CR_ONE', $shown, $all).')';
         }
      }
      return $retour;
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML($item,$pos,$with_links=true) {
      $shown_entry_number = $pos;
      if ($shown_entry_number%2 == 0) {
         $style='class="odd"';
      } else {
         $style='class="even"';
      }
      $fileicons = $this->_getItemFiles($item, $with_links);
      if ( !empty($fileicons) ) {
         $fileicons = ' '.$fileicons;
      }
      $html  = '   <tr class="list">'.LF;
      $html .= '      <td '.$style.' style="font-size:10pt; width:42%;">'.$this->_getItemTitle($item).$fileicons.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:8%;">'.$this->_getFastItemArticleCount($item).'</td>'.LF;
      $html .= '      <td  '.$style.' style="font-size:8pt; width:20%;">'.$this->_getItemLastArticleDate($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:30%;">'.$this->_getItemModificator($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getItemTitle ($item) {
      $title = $this->_text_as_html_short($item->getTitle());
      $params = array();
      $params['iid'] = $item->getItemID();
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           'discussion',
                           'detail',
                           $params,
                           $title);
      unset($params);
      if($item->isClosed()){
         $title .= ' <span class="closed">('.$this->_translator->getMessage('DISCUSSION_IS_CLOSED').')</span>';
      }
      if ( !$this->_environment->inPrivateRoom() ) {
         $title .= $this->_getItemChangeStatus($item);
      }
      return $title;
   }

   /** get the modificator-name of an item
    * Can be used in derived classes _getItemAsHTML()-methods
    * to display the modificator of an item in a standardized
    * manner.
    *
    * @return string modificator_fullname
    *
    * @author CommSy Development Group
    */
   function _getItemModificator($item){
      $modificator = $item->getModificatorItem();
      if (!$modificator->isDeleted()){
         $current_user_item = $this->_environment->getCurrentUserItem();
         if ( $current_user_item->isGuest()
              and $modificator->isVisibleForLoggedIn()
            ) {
            $fullname = $this->_translator->getMessage('COMMON_USER_NOT_VISIBLE');
         } else {
            $fullname = $modificator->getFullName();
         }
         unset($current_user_item);
      }else{
         $fullname = $this->_translator->getMessage('COMMON_DELETED_USER');
      }
      return $this->_text_as_html_short($fullname);
   }

   /** get article count of a discussion
    * Returns the total and unread number of articles
    * for a discussion-item in a formatted string.
    *
    * @return string article_count
    *
    * @author CommSy Development Group
    */
   function _getItemArticleCount($item){
     $all_articles = $item->getAllArticlesCount();
     $unread_articles = $item->getUnreadArticles();
     return $unread_articles.' / '.$all_articles;
     #.' ('.$unread_articles.' <span class="desc">'.$this->_translator->getMessage('COMMON_UNREAD').'</span>)';
   }


   function _getFastItemArticleCount ($item) {
     $array = $item->getAllAndUnreadArticles();
     return $array['count'].' / '.$array['unread'];
   }

   /** get the date of last added article
    * this method returns the number in the right formatted style
    *
    * @return date last_article_date
    */
   function _getItemLastArticleDate($item){
     $last_article_date = $item->getLatestArticleModificationDate();
     $last_article_date = $this->_translator->getDateInLangWithoutOClock($last_article_date);
     return $this->_text_as_html_short($last_article_date);
   }

   /** get the modification-date of an item
    * Can be used in derived classes _getItemAsHTML()-methods
    * to display the modification date of an item in a
    * standardized manner.
    *
    * @return string modification_date
    */
   function _getItemModificationDate($item){
      if ( $item->getCreationDate() <> $item->getModificationDate() ) {
         $mod_date = $this->_translator->getDateInLangWithoutOClock($item->getModificationDate());
      } else {
         $mod_date = $this->_translator->getDateInLangWithoutOClock($item->getCreationDate());
      }
      return $mod_date;
   }

   /** get the file list of the item
    * this method returns the item file list in the right formatted style
    *
    * @return string file list
    */
   function _getItemFiles($item, $with_links=true){
      $retour='';
      $file_list='';
      $files = $item->getFileListWithFilesFromArticles();
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
         unset($file);
         $file = $files->getNext();
      }
      unset($user);
      unset($files);
      return $retour.$file_list;
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
                            CS_DISCUSSION_TYPE,
                            'index',
                            array(),
                            $this->_translator->getMessage("HOME_RUBRIC_LIST_SHORTENED"));
      
      $html .= '<tr class="list"><td ' . $style . ' colspan="4">' . $link . '</td></tr>';
      
      return $html;
   }
}
?>