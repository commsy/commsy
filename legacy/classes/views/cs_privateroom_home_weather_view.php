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
$this->includeClass(VIEW);

/** class for a form view in commsy-style
 * this class implements a form view
 */
class cs_privateroom_home_weather_view extends cs_view {

	var $_location = '';
	
   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function __construct($params) {
      cs_view::__construct($params);
      $this->setViewName('weather');
      $user = $this->_environment->getCurrentUserItem();
      $room = $this->_environment->getCurrentContextItem();
      $this->_view_title = $this->_translator->getMessage('COMMON_WEATHER_BOX');
   }

   function setLocation($location){
      $this->_location = $location;
   }

   function asHTML(){
   	$session = $this->_environment->getSessionItem();
      if($session->issetValue('cookie')){
         if($session->getValue('cookie') == '1'){
		      $html = '<div id="'.get_class($this).'">'.LF;
		      $html .= '<div id="weather_widget"></div>'.LF;
		      $html .= '</div>'.LF;
		      $html .= '<script type="text/javascript">'.LF;
		      $html .= '<!--'.LF;
		      $html .= 'var portlet_weather_temp = "'.$this->_translator->getMessage('COMMON_WEATHER_TEMP').'"'.LF;
		      $html .= 'var portlet_weather_humidity = "'.$this->_translator->getMessage('COMMON_WEATHER_HUMIDITY').'"'.LF;
		      $html .= '-->'.LF;
		      $html .= '</script>'.LF;
         } else {
            $html = $this->_translator->getMessage('COMMON_COOKIES_NEEDED');
         }
      }
      return $html;
   }
   
   #function getPreferencesAsHTML(){
   #   $html = $this->_translator->getMessage('PORTLET_CONFIGURATION_WEATHER_LOCATION').': ';
   #   $html .= '<input type="text" id="portlet_weather_location" value="'.$this->_location.'">';
   #   $html .= '<input type="submit" id="portlet_weather_button" value="'.$this->_translator->getMessage('COMMON_SAVE_BUTTON').'">';
   #   return $html;
   #}
}
?>