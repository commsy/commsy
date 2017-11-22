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

/*

$this->includeClass(HOME_VIEW);

class cs_homepage_informationbox_short_view extends cs_view {

   function cs_homepage_informationbox_short_view ($environment, $with_modifying_actions) {
      $this->cs_view($environment, $with_modifying_actions);
      $current_context = $this->_environment->getCurrentContextItem();
      $title = $current_context->getInformationBoxTitle();
      $this->setViewTitle($title);
      $this->_view_name = $this->_translator->getMessage('COMMON_INFORMATION_INDEX');
   }

   function _getDescriptionAsHTML() {
      #$all = $this->getCountAll();
      #$list = $this->getList();
      #$shown = $list->getCount();
      #$context = $this->_environment->getCurrentContextItem();
      #return ' ('.$this->_translator->getMessage('COMMON_ANNOUNCEMENT_SHORT_VIEW_DESCRIPTION',$shown,$all).')';
   }


    function asHTML () {
     $html = '';
      $html .= LF.'<!-- BEGIN OF INFORMATION VIEW -->'.LF;

      // Content
      $html .= '<div class="list">'.LF;
      $html .= $this->_getContentAsHTML();
      $html .= '</div>'.LF;

      $html .= '<!-- END OF HOME VIEW -->'.LF;
      return $html;
   }

   function _getTableheadAsHTML () {
      include_once('functions/error_functions.php');
      trigger_error('Method must be overridden in subclass', E_USER_ERROR);
   }

   function _getContentAsHTML() {
      $html = '<div class="right_box" style="padding:5px 5px;">';
      $current_context = $this->_environment->getCurrentContextItem();
      $html .= '<div>'.LF;
      $desc = $current_context->getInformationBoxDescription();
      if ( !empty($desc) ) {
         $desc = $this->_text_as_html_long($desc);
         $desc = $this->_show_images($desc,$current_context,true);
         $html .= $desc.LF;
      }
      $html .= '</div>'.LF;
      $html .= '<div style="clear:both;">'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>';
      unset($current_context);
      return $html;
   }

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
      $html .= '      <td '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).$fileicons.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemDate($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;" colspan="2">'.$this->_getItemModificator($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }


}*/


$this->includeClass(HOME_VIEW);

class cs_home_informationbox_view extends cs_home_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_home_view::__construct($params);
      $current_context = $this->_environment->getCurrentContextItem();
      $id = $current_context->getInformationBoxEntryID();
      $manager = $this->_environment->getItemManager();
      $item = $manager->getItem($id);
      $entry_manager = $this->_environment->getManager($item->getItemType());
      $entry = $entry_manager->getItem($id);
      $this->setViewTitle($this->_text_as_html_long(chunkText($entry->getTitle(),50)));
      $this->_view_name = $this->_translator->getMessage('COMMON_INFORMATION_INDEX');
   }

   function _getDescriptionAsHTML() {
   }


   function asHTML () {
     $html = '';
      $html .= LF.'<!-- BEGIN OF INFORMATION VIEW -->'.LF;

      // Content
      $html .= '<div class="list">'.LF;
      $html .= $this->_getContentAsHTML();
      $html .= '</div>'.LF;

      $html .= '<!-- END OF HOME VIEW -->'.LF;
      return $html;
   }

   function _getTableheadAsHTML () {
      include_once('functions/error_functions.php');
      trigger_error('Method must be overridden in subclass', E_USER_ERROR);
   }

   function _getContentAsHTML() {
      $current_context = $this->_environment->getCurrentContextItem();
      $id = $current_context->getInformationBoxEntryID();
      $manager = $this->_environment->getItemManager();
      $item = $manager->getItem($id);
      $entry_manager = $this->_environment->getManager($item->getItemType());
      $entry = $entry_manager->getItem($id);
      $html = '<div class="right_box" style="background-color:#ffffff;">';
      $current_context = $this->_environment->getCurrentContextItem();
      $html .= '<div class="right_box_title">';
      $html .= '<div id="right_box_title_link" style="float:right;">';
      $params = array();
      $params['iid'] = $id;
      $html .= ahref_curl($this->_environment->getCurrentContextID(), 'content', 'detail', $params, '&gt; '.$this->_translator->getMessage('COMMON_INFOBOX_FURTHER_INFORMATION'), '', '', '','','','','class="right_box_title"');
      $html .= '</div>';
      $html .= '<div id="information_box_title">'.$this->_text_as_html_short($this->getViewTitle()).'</div></div>';
      $html .= '<div class="right_box_main" style="padding:0px 0px 0px 5px;">'.LF;
      $desc = '';
      $desc = $entry->getDescription();
      if ( !empty($desc) ) {
         $desc = $this->_text_as_html_long($this->_cleanDataFromTextArea($desc));
      }
      $html .= '<div style="max-height: 220px; height:auto !important; height: 220px; overflow:auto;">';
      $html .= '<div style="padding:3px;">'.$desc.'</div></div>'.LF;
      $html .= '</div>'.LF;
      $html .= '</div>';
      unset($current_context);
      return $html;
   }

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
      $html .= '      <td '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).$fileicons.'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemDate($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;" colspan="2">'.$this->_getItemModificator($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }


}


?>