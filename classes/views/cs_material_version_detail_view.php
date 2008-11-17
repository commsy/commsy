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

$this->includeClass(MATERIAL_DETAIL_VIEW);

class cs_material_version_detail_view extends cs_material_detail_view {

   /** constructor: cs_material_detail_view
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_material_version_detail_view ($params) {
      $this->cs_material_detail_view($params);
   }

   /** get all the actions for this detail view as HTML
    * this method returns the actions in HTML-Code. It checks the access rights!
    *
    * @return string navigation as HMTL
    *
    * @version CommSy 2.1
    * @author CommSy Development Group
    */

   function _getDetailActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = &$this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '<noscript><div class="right_box_title">'.getMessage('COMMON_ACTIONS').'</div></noscript>';
      $html .= '<div class="right_box_main" >'.LF;
      if ( $item->mayEdit($current_user) ) {
         $params = array();
         $params['iid'] = $this->_item->getItemID();
         $params['act_version'] = $this->_item->getVersionID();
         $html .= '> '.ahref_curl( $this->_environment->getCurrentContextID(),
                                    'material',
                                    'detail',
                                    $params,
                                    $this->_translator->getMessage('VERSION_MAKE_NEW')).BRLF;
         unset($params);
      } else {
         $html .= '<div class="disabled">'.'> '.$this->_translator->getMessage('VERSION_MAKE_NEW').'</div>'.BRLF;
      }

      if ( $current_user->isUser() ) {
//         $params = array();
         $params = $this->_environment->getCurrentParameterArray();
         $params['iid'] = $this->_item->getItemID();
         $params['del_version'] = $this->_item->getVersionID();
         $params['action'] = 'delete';
         $html .= '> '.ahref_curl( $this->_environment->getCurrentContextID(),
                                   'material',
                                   'detail',
                                   $params,
                                   $this->_translator->getMessage('COMMON_VERSION_DELETE')).BRLF;
         unset($params);
      } else {
         $html .= '<span class="disabled">'.'> '.$this->_translator->getMessage('COMMON_VERSION_DELETE').'</span>'.BRLF;
      }
      $params = $this->_environment->getCurrentParameterArray();
      $params['mode']='print';
      $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'detail',$params,$this->_translator->getMessage('COMMON_LIST_PRINTVIEW')).BRLF;
      $html .= '</div>'.LF;
      $html .= '</div>'.LF;
      return $html;
   }

    /** set the ids of items to browse
    * this method sets the ids of items to browse
    *
    * @param array  $browse_ids
    *
    * @author CommSy Development Group
    */
    function setBrowseIDs($browse_ids) {
       $this->_browse_ids = array_values((array)$browse_ids);  // Re-Index array, starting by 0
       if ( empty($this->_browse_ids) ) {
          $this->_position = -1;
       } else {
          if ( isset($this->_item) ) {
             $pos = array_search($this->_item->getVersionID(), $this->_browse_ids);
             if ( $pos === NULL or $pos === false ) {
                $this->_position = -1;
             } else {
                $this->_position = $pos+1;
             }
          } else {
             $this->_position = -1;
          }
       }
    }


   /** get the browsing icons of the detail view as HTML
    * this method returns the browsing icons in HTML-Code
    *
    * @returns string browsing icons as HMTL
    *
    * @author CommSy Development Group
    */
   function _getBrowsingIconsAsHTML($current_item, $pos_number, $count) {
      $html ='<a name="'.$pos_number.'"></a>';
      $i =0;
      if ( $pos_number > 1 ) {
         $i = $pos_number-1;
         $image = '<img src="images/browse_left2.gif" alt="&lt;" border="0">';
         $html .= '<a href="#'.$i.'">'.$image.'</a>'.LF;
      } else {
         $html .= '         <span class="disabled"><img src="images/browse_left_grey2.gif" alt="&lt;" border="0"></span>'.LF;
      }
      $html .= '&nbsp;&nbsp;';
      if ( $pos_number < $count) {
         $i = $pos_number+1;
         $image = '<img src="images/browse_right2.gif" alt="&gt;" border="0">';
         $html .= '<a href="#'.$i.'">'.$image.'</a>'.LF;
      } else {
         $html .= '         <span class="disabled"><img src="images/browse_right_grey2.gif" alt="&gt;" border="0"></span>'.LF;
      }
      return $html;
   }
}
?>