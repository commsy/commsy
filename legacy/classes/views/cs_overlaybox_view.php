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

/** upper class of the errorbox
 */
$this->includeClass(TEXT_VIEW);

/** class for an errorbox in commsy-style
 * this class implements an errorbox, it is a special text_view
 */
class cs_overlaybox_view extends cs_text_view {

   private $_back_link = '';

   /** constructor: cs_overlaybox_view
    * the only available constructor, initial values for internal variables
    *
    */
   public function __construct ($params) {
      cs_text_view::__construct($params);
   }

   public function setBackLink ( $value ) {
      $this->_back_link = $value;
   }

   private function _getBackLink () {
      $retour = '';
      if ( !empty($this->_back_link) ) {
         include_once('functions/curl_functions.php');
         $retour = ahref_curl2($this->_back_link,'X',
                               '','', '', '', '', 'class="titlelink"');
      } else {
         $params = $this->_environment->getCurrentParameterArray();
         $retour = ahref_curl( $this->_environment->getCurrentContextID(),
                              $this->_environment->getCurrentModule(),
                              $this->_environment->getCurrentFunction(),
                              $params,
                              'X',
                              '','', '', '', '', '', 'class="titlelink"');
      }
      return $retour;
   }

   /** get overlaybox as HTML
    * this method returns the overlaybox in HTML-Code
    *
    * @return string overlaybox view as HMTL
    */
   public function asHTML () {
      $html  = '';
      $html .= '<!-- BEGIN OF OVERLAYBOX -->'.LF;
      $html .= '<div class="overlay_box">'.LF;
      $html .= '   <div>'.LF;
      $html .= '   <div class="overlay_title_backlink">'.$this->_getBackLink().'</div>';
      if ( !empty($this->_title) ) {
         $html .= '      <h2 class="overlay_title">'.$this->_text_as_html_short($this->_title).'</h2>';
      }
      $html .= '   </div>'.LF;
      if ( !empty($this->_text) ) {
         $html .= '   <div class="overlay_content">'.LF;
         $html .= '      '.$this->_text_as_html_long($this->_text).LF;
         $html .= '   </div>'.LF;
      }
      $html .= '</div>'.LF;
      $html .= '<!-- END OF OVERLAYBOX -->'.LF.LF;
      return $html;
   }

   public function getInfoForHeaderAsHTML () {
      $retour  = '';
      $retour .= '   <link rel="stylesheet" href="css/commsy_overlay_css.php?cid='.$this->_environment->getCurrentContextID().'" />';
      return $retour;
   }
}
?>