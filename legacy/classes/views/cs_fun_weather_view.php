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

$this->includeClass(VIEW);

/**
 *  class for CommSy list view: news
 */
class cs_fun_weather_view extends cs_view {

   private $_country = 'DE';
   private $_plz = 22527;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   public function __construct ($params) {
      cs_view::__construct($params);
   }

   public function setCountry ($value) {
      $this->_country = mb_strtoupper($value, 'UTF-8');
   }

   public function setPLZ ($value) {
      $this->_plz = (int)$value;
   }

   public function asHTML () {
        $retour  = '';
        if ( !empty($this->_country) and !empty($this->_plz) ) {
           $retour .= '<!-- BEGIN OF VIEW FUN WEATHER -->'.LF;
           $retour .= '<div class="right_box">'.LF;
           $retour .= '<div class="right_box_title"><span class="bold">Wetter</span></div>'.LF;
           $retour .= '<center>'.LF;
           $retour .= '<script language="JavaScript" type="text/javascript" src="http://www.wetter.com/v2/woys2/woys2.js.php?173972,cd258506f84cd643f75aef2ea5ec1ea5,'.$this->_country.'PLZ,'.$this->_plz.'"></script>';
           $retour .= '</center>'.LF;
           $retour .= '</div>'.LF;
           $retour .= '<!-- END OF VIEW FUN WEATHER -->'.LF;
        }
        return $retour;
   }
}
?>