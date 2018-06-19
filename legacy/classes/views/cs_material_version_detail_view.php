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
   function __construct($params) {
      cs_material_detail_view::__construct($params);
   }

   /** get all the actions for this detail view as HTML
    * this method returns the actions in HTML-Code. It checks the access rights!
    *
    * @return string navigation as HMTL
    *
    * @version CommSy 2.1
    * @author CommSy Development Group
    */

   function _getDetailItemActionsAsHTML ($item) {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      if ( $item->mayEdit($current_user) and $this->_with_modifying_actions ) {
         $params = array();
         $params['iid'] = $item->getItemID();
         $params['act_version'] = $this->_item->getVersionID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                          $this->_environment->getCurrentModule(),
                                          'edit',
                                          $params,
                                          $image,
                                          $this->_translator->getMessage('COMMON_EDIT_ITEM')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/edit_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/edit_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_EDIT_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
      }

      if ( $item->mayEdit($current_user)  and $this->_with_modifying_actions ) {
         $params = $this->_environment->getCurrentParameterArray();
         $params = array();
         $params = $this->_environment->getCurrentParameterArray();
         $params['iid'] = $this->_item->getItemID();
         $params['act_version'] = $this->_item->getVersionID();
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/material_version_current.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('VERSION_MAKE_NEW').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/material_version_current.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('VERSION_MAKE_NEW').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                     $this->_environment->getCurrentModule(),
                                     'detail',
                                     $params,
                                     $image,
                                     $this->_translator->getMessage('VERSION_MAKE_NEW')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/material_version_current_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('VERSION_MAKE_NEW').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/material_version_current_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('VERSION_MAKE_NEW').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('VERSION_MAKE_NEW')).' "class="disabled">'.$image.'</a>'.LF;
      }

      if ( $item->mayEdit($current_user)  and $this->_with_modifying_actions ) {
         $params = $this->_environment->getCurrentParameterArray();
         $params = array();
         $params = $this->_environment->getCurrentParameterArray();
         $params['iid'] = $this->_item->getItemID();
         $params['del_version'] = $this->_item->getVersionID();
         $params['action'] = 'delete';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                     $this->_environment->getCurrentModule(),
                                     'detail',
                                     $params,
                                     $image,
                                     $this->_translator->getMessage('COMMON_DELETE_ITEM')).LF;
         unset($params);
      } else {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_DELETE_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
      }

      return $html;
   }

   function _getAdditionalActionsAsHTML ($item) {
      $html  = '';
      return $html;
   }

   /** set the ids of items to browse
    * this method sets the ids of items to browse
    *
    * @param array  $browse_ids
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