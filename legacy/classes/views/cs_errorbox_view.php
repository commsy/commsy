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
class cs_errorbox_view extends cs_text_view {

   private $_with_login = false;
   var $_width = false;

   /** constructor: cs_errorbox_view
    * the only available constructor, initial values for internal variables
    *
    */
   function __construct($params) {
      if ( !empty($params['width']) ){
         $this->_width = $params['width'];
      }
      cs_text_view::__construct($params);
      $this->setTitle($this->_translator->getMessage('ERRORBOX_TITLE'));
   }

   function setWidth($value){
      $this->_width = $value;
   }

   function setWithLogin () {
      $this->_with_login = true;
   }

   /** get errorbox as HTML
    * this method returns the errorbox in HTML-Code
    *
    * @return string errorbox view as HMTL
    */
   function asHTML () {
      $html  = '';
      $html .= '<!-- BEGIN OF ERRORBOX -->'.LF;
      if ( !$this->_width
           and ( $this->_environment->inPortal()
                 or $this->_environment->inServer()
               )
         ) {
         $text = 'width: 60%;';
      } elseif ($this->_width and !mb_stristr($this->_width,'em') and !mb_stristr($this->_width,'%')) {
         $width = round($this->_width/12,0);
         $text = ' width: '.$width.'em;';
      } elseif (mb_stristr($this->_width,'em') or mb_stristr($this->_width,'%')) {
         $text = ' width: '.$this->_width;
      } else {
         $text = 'width: 35em;';
      }
      $html .= '<div style="width:100%; text-align:center; padding-top:5px; padding-bottom:0px;">'.LF;
      $html .= '<center>'.LF;
      $html .= '<table style="border: 2px solid #FF0000; margin: 0px; '.$text.'" summary="Layout">'.LF;
      if (!empty($this->_title)) {
         $html .= '   <tr><td style="text-align: left;">'.LF;
         #$html .= '<img src="images/warn.gif" style="vertical-align: top; text-align: left; padding-left: 0px; padding-right: 10px;" alt="'.$this->_translator->getMessage('RUBRIC_WARN_CHANGER').'"></img>'.LF;
         $html .= '<span style="font-size:12pt; font-weight:bold">'.$this->_title.'</span>'.LF;
         if (!empty($this->_description)) {
            $html .= '      <span>('.$this->_description.')</span>'.LF;
         }
         $html .= '   </td></tr>'.LF;
      }
      $html .= '   <tr>'.LF;
      $html .= '      <td style="text-align: left;">'.$this->_text.'</td>'.LF;
      $html .= '   </tr>'.LF;

      if ( $this->_with_login ) {
         $html .= '   <tr>'.LF;
         $html .= '      <td style="text-align: left;">'.LF;
         $html .= '         <form method="post" action="'.curl($this->_environment->getCurrentContextID(),'context','login').'" name="login">'.LF;
         // TBD
         $html .= '         </form>'.LF;
         $html .= '      </td>'.LF;
         $html .= '   </tr>'.LF;
      }

      $html .= '</table>'.LF;
      $html .= '</center>'.LF;
      $html .= '</div>'.LF;

      $html .= '<!-- END OF ERRORBOX -->'.LF.LF;
      return $html;
   }
}
?>