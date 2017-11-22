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

/** upper class of the context item
 */
include_once('classes/cs_context_item.php');

/** class for a context
 * this class implements a context item
 */
class cs_guide_item extends cs_context_item {

   /** constructor: cs_server_item
    * the only available constructor, initial values for internal variables
    *
    * @param object environment the environment of the commsy
    */
   function __construct($environment) {
      cs_context_item::__construct($environment);
   }

   function isServer () {
      return false;
   }

   function isPortal () {
      return false;
   }

   function setServerNewsTitle ($value) {
      $this->_setServerNews('title',$value);
   }

   function setServerNewsText ($value) {
      $this->_setServerNews('text',$value);
   }

   function setServerNewsLink ($value) {
      $this->_setServerNews('link',$value);
   }

   function _setServerNewsShow ($value) {
      $this->_setServerNews('show',$value);
   }

   function _setServerNews ($mode,$value) {
      $server_news_array = $this->_getServerNewsArray();
      $server_news_array[mb_strtoupper($mode, 'UTF-8')] = $value;
      $this->_setServerNewsArray($server_news_array);
   }

   function _setServerNewsArray ($value) {
      $this->_addExtra('SERVER_NEWS',$value);
   }

   function _getServerNewsArray () {
      return $this->_getExtra('SERVER_NEWS');
   }

   function getServerNewsTitle () {
      return $this->_getServerNews('title');
   }

   function getServerNewsText () {
      return $this->_getServerNews('text');
   }

   function getServerNewsLink () {
      return $this->_getServerNews('link');
   }

   function _getServerNewsShow () {
      return $this->_getServerNews('show');
   }

   function _getServerNews ($mode) {
      $retour = '';
      $server_news_array = $this->_getServerNewsArray();
      if (!empty($server_news_array[mb_strtoupper($mode, 'UTF-8')])) {
         $retour = $server_news_array[mb_strtoupper($mode, 'UTF-8')];
      }
      return $retour;
   }

   function showServerNews () {
      $retour = false;
      $show_news = $this->_getServerNewsShow();
      if ($show_news == 1) {
         $retour = true;
      }
      return $retour;
   }

   function setDontShowServerNews () {
      $this->_setServerNewsShow(-1);
   }

   function setShowServerNews () {
      $this->_setServerNewsShow(1);
   }

   function setAvailableLanguageArray ($value) {
      $this->_addExtra('LANGUAGE_AVAILABLE',$value);
   }

   function getAvailableLanguageArray () {
      $retour = array();
      if ( $this->_issetExtra('LANGUAGE_AVAILABLE') ) {
         $retour = $this->_getExtra('LANGUAGE_AVAILABLE');
      } elseif ( $this->isServer() ) {
         $translator = $this->_environment->getTranslationObject();
         $retour = $translator->getAvailableLanguages();
      } elseif ( $this->isPortal() ) {
         $server_item = $this->_environment->getServerItem();
         $retour = $server_item->getAvailableLanguageArray();
      }
      return $retour;
   }

   /** get url of a portal/server
    * this method returns the url of the portal/server
    * - without http(s)://
    * - without /commsy.php?....
    *
    * @return string url of a portal/server
    */
   function getUrl () {
      return $this->_getValue('url');
   }

   /** set url of a portal
    * this method sets the url of the portal/server
    *
    * @param string value url of the portal/server
    */
   function setUrl ($value) {
      $this->_setValue('url', $value, TRUE);
   }
}
?>