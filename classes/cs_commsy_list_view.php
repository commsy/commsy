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

include_once('classes/cs_list_view_plain.php');
include_once('functions/curl_functions.php');

/**
 *  class for CommSy list view: commsys
 */
class cs_commsy_list_view extends cs_list_view_plain {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the page
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_commsy_list_view ($environment, $with_modifying_actions) {
      $this->cs_plain_list_view($environment,'commsy_list_view',$with_modifying_actions);
   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    */
   function _getItemAsHTML($item) {
      $html = '';
      $html .= '   <tr>'."\n";
      $html .= '      <td class="commsy_list_view_plain_entry_logo">'."\n";
      $html .= '         '.$this->_getLogo($item)."\n";
      $html .= '      </td>'."\n";
      $html .= '      <td class="commsy_list_view_plain_entry_title">'."\n";
      $html .= '         '.$this->_getTitle($item)."\n";
      $html .= '      </td>'."\n";
      $html .= '      <td class="commsy_list_view_plain_entry_activity">'."\n";
      #$html .= '         '.$this->_getActivity($item)."\n";
      $html .= '      </td>'."\n";
      $html .= '   </tr>'."\n";
      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getTitle($item){
      $title = $item->getTitle();
      $title = ahref_curl( $item->getItemID(),
                           'home',
                           'index',
                           '',
                           $this->_text_as_html_short($title));
      return $title;
   }

   /** get the logo of the item
    * this method returns the item logo in the right formatted style
    *
    * @return string title
    */
   function _getLogo($item){
      $logo_description = '';
      $logo = $item->getLogoFileName();
      if (!empty($logo)) {
         $params = array();
         $params['picture'] = $item->getLogoFilename();
         $curl = curl($item->getItemID(), 'picture', 'getfile', $params);
         unset($params);
         $logo_description = '<img src="'.$curl.'" alt="'.$item->getTitle().' '.$this->_translator->getMessage('LOGO').'">'."\n";
         $logo_description = ahref_curl( $item->getItemID(),
                                         'home',
                                         'index',
                                         '',
                                         $logo_description);
      }
      return $logo_description;
   }

   /** get the activity of the item
    * this method returns the item activity in the right formatted style
    *
    * @return string title
    */
   function _getActivity($item){
      $retour = $item->getActivity();
      if (empty($retour)) {
         $retour = 0;
      }
      #$retour .= '%';
      return $retour;
   }
}
?>