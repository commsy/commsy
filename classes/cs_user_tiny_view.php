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

include_once('classes/cs_tiny_view.php');

/**
 *  class for CommSy tiny list view: user
 */
class cs_user_tiny_view extends cs_tiny_view {

   /**
    * int - length of whole list
    */
   var $_visible_count_all = NULL;

   /** set count_all counter of the list view
    * this method sets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    *
    * @author CommSy Development Group
    */
    function setVisibleCountAll ($count_all) {
       $this->_visible_count_all = (int)$count_all;
    }

   /** get count_all counter of the list view
    * this method gets the whole entries of the list view
    *
    * @param int  $this->_count_all          lenght of the whole list
    *
    * @author CommSy Development Group
    */
    function getVisibleCountAll () {
       return $this->_visible_count_all;
    }


   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environment of the commsy
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    *
    * @author CommSy Development Group
    */
   function cs_user_tiny_view ($environment, $with_modifying_actions) {
      $this->cs_tiny_view($environment, $with_modifying_actions);
      $this->setOneLine();
      $context = $this->_environment->getCurrentContextItem();
      $title = $this->_translator->getMessage('COMMON_USER_INDEX');
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           'user',
                           'index',
                           '',
                           $title,'','','','','','','class="head"');
      $this->setTitle($title);
   }

   /** get the description of the list view title as HTML
    * this method returns the description in HTML-Code
    *
    * @return string $this->_description as HMTL
    *
    * @author CommSy Development Group
    */
   function _getDescriptionAsHTML() {

      $visible_all = $this->getVisibleCountAll();
      $all = $this->getCountAll();
      $list = $this->_list;
      if (!empty($list)){
         $shown = $list->getCount();
      }else{
         $shown = '0';
      }
      $context = $this->_environment->getCurrentContextItem();
      $user = $this->_environment->getCurrentUserItem();
      if ($this->_environment->inCommunityRoom() and $user->isGuest()){
         $period = '180';
         return '<span class="desc">'.$this->_translator->getMessage('COMMON_USER_TINY_VIEW_DESCRIPTION',$visible_all,$all).'</span>';
      }else{
         return '<span class="desc">'.$this->_translator->getMessage('COMMON_TINY_VIEW_DESCRIPTION',$all).'</span>';
      }
   }


}
?>