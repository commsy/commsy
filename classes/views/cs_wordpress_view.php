<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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
include_once('classes/cs_list.php');
include_once('functions/curl_functions.php');

/**
 *  generic upper class for CommSy detail views
 */
class cs_wordpress_view extends cs_view {

   /**
    * item - containing the item to display
    */
   var $_item = NULL;

   /**
    * subitems - cs_list containing the item to display below the actual item (e.g. sections)
    */
   var $_subitems = NULL;


   /** constructor: cs_wiki_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_wordpress_view ($params) {
      $this->cs_view($params);
   }

   /**
    * Set the cs_item and optionally a list of subitems (also
    * of type cs_item) to display.
    */
   function setItem ($item){
      $this->_item = $item;
   }

   function getItem () {
      return $this->_item;
   }

   function setSubItemList ($subitems) {
      $this->_subitems = $subitems;
   }

   function getSubItemList () {
      return $this->_subitems;
   }
  
   /*
    * @TODO further formating of the text
    */
   function formatForWordpress($text){      
      return $text;
   }
}
?>
