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

/** upper class of the form view
 */
$this->includeClass(CONFIGURATION_FORM_VIEW);
include_once('classes/cs_link.php');
include_once('classes/cs_list.php');

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_configuration_inactive_form_view extends cs_configuration_form_view {

   var $_item_saved = false;

   /** constructor: cs_configuration_form_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   public function __construct ($params) {
      cs_configuration_form_view::__construct($params);
   }


   /** get textline as HTML - internal, do not use
    * this method returns a string contains a text in HMTL-Code
    *
    * @param array value form element: text, see class cs_form
    *
    * @return string textline as HMTL
    */
   function _getTextAsHTML ($form_element) {
      $html  = '';
      if (!empty($form_element['anchor'])){
        $html='<a name="'.$form_element['anchor'].'"></a>';
      }
      if (!empty($form_element['value'])) {
         if ($form_element['isbold']) {
            $html .= '<b>'.$this->_text_as_html_long($form_element['value']).'<b>';
         } else {
            $html .= $form_element['value'];
         }
         if (!empty($form_element['button_text']) and !empty($form_element['button_name'])) {
            $html .= '         &nbsp;';
            $html .= $this->_getButtonAsHTML($form_element['button_text'],$form_element['button_name'],'',false).LF;
         }
         $html .= '<br />'."\n";
      }
      return $html;
   }



}
?>