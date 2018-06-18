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
class cs_date_short_view extends cs_home_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_home_view::__construct($params);
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'index',
                           '',
                           $this->_translator->getMessage('COMMON_DATE_INDEX'),'','','','','','','class="head"');
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

      return ' ('.$this->_translator->getMessage('HOME_DATES_SHORT_VIEW_DESCRIPTION',$shown,$all).')';
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
      $html  = '   <tr class="list">'.LF;

      $fileicons = $this->_getItemFiles($item, $with_links);
      if ( !empty($fileicons) ) {
         $fileicons = ' '.$fileicons;
      }
      $html .= '      <td '.$style.' style="font-size:10pt; width:50%;">'.$this->_getItemTitle($item).$fileicons.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt; width:20%;">'.$this->_getItemDate($item);
      $time = $this->_getItemTime($item);
      if (!empty($time)) {
         $html .= ', '.$time;
      }
      $html .= '</td>'.LF;
      $html .= '      <td colspan="3" '.$style.' style="font-size:8pt; width:30%;">'.$this->_getItemPlace($item).'</td>'.LF; // layout hack, should be colspan="2" (TBD)
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
      $title = $this->_text_as_html_short($item->getTitle());
      $params = array();
      $params['iid'] = $item->getItemID();
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DATE_TYPE,
                           'detail',
                           $params,
                           $title);
      unset($params);
      if ( !$this->_environment->inPrivateRoom() ) {
          $title .= $this->_getItemChangeStatus($item);
          $title .= $this->_getItemAnnotationChangeStatus($item);
      }
      return $title;
   }

   /** get the place of the item
    * this method returns the item place in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemPlace($item){
      $place = $item->getPlace();
      return $this->_text_as_html_short($place);
   }

   /** get the time of the item
    * this method returns the item place in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemTime($item){
      $parse_time_start = convertTimeFromInput($item->getStartingTime());
      $conforms = $parse_time_start['conforms'];
      if ($conforms == TRUE) {
         $time =getTimeLanguage($parse_time_start['datetime']);
      } else {
         $time = $item->getStartingTime();
      }

      return $this->_text_as_html_short($time);
   }

   /** get the date of the item
    * this method returns the item place in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemDate($item){
      $parse_day_start = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
      $conforms = $parse_day_start['conforms'];
      if ($conforms == TRUE) {
         $date = $this->_translator->getDateInLang($parse_day_start['datetime']);
      } else {
         $date = $item->getStartingDay();
      }
      return $this->_text_as_html_short($date);
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
                            CS_DATE_TYPE,
                            'index',
                            array(),
                            $this->_translator->getMessage("HOME_RUBRIC_LIST_SHORTENED"));
      
      $html .= '<tr class="list"><td ' . $style . ' colspan="5">' . $link . '</td></tr>';
      
      return $html;
   }
}
?>