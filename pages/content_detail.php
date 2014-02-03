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

// include functions needed for this script
include_once('functions/curl_functions.php');

if ( empty($_GET['iid']) ) {
   $session = $environment->getSessionItem();
   $history = $session->getValue('history');
   redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$history[0]['parameter']);
} else {
   $iid = $_GET['iid'];
   $item_manager = $environment->getItemManager();
   $item = $item_manager->getItem($iid);
   if ( isset($item) ) {
      $context_id = $item->getContextID();
      $type = $item->getItemType();

      // discussion article
      if ($type == CS_DISCARTICLE_TYPE) {
         $discart_manager = $environment->getDiscussionArticlesManager();
         $discart_item = $discart_manager->getItem($iid);
         $sub_iid = 'anchor'.$iid;
         $iid = $discart_item->getDiscussionID();
      }

      // material section
      elseif ($type == CS_SECTION_TYPE) {
         $section_manager = $environment->getSectionManager();
         $section_item = $section_manager->getItem($iid);
         $sub_iid = 'anchor'.$iid;
         $iid = $section_item->getLinkedItemID();
      }

      // annotation
      elseif ($type == CS_ANNOTATION_TYPE) {
         $annotation_manager = $environment->getAnnotationManager();
         $annotation_item = $annotation_manager->getItem($iid);
         $sub_iid = $iid;
         $iid = $annotation_item->getLinkedItemID();
         $linked_item = $item_manager->getItem($iid);
         $type = $linked_item->getItemType();
      }

      $module = Type2Module($type);
      if ($module == CS_LABEL_TYPE) {
         $label_manager = $environment->getLabelManager();
         $item = $label_manager->getItem($iid);
         $module = $item->getLabelType();
      }

      // redirect to real content
      if ($module == CS_ITEM_TYPE) {
         //illegal iid, go back to start
         $session = $environment->getSessionItem();
         $history = $session->getValue('history');
         redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$history[0]['parameter']);
      } else {
         $params = array();
         $params['iid'] = $iid;
         if (isset($sub_iid)) {
            redirect($context_id,$module,'detail',$params,$sub_iid);
         } else {
            redirect($context_id,$module,'detail',$params);
         }
      }
   } else {
      //illegal iid, go back to start
      $session = $environment->getSessionItem();
      $history = $session->getValue('history');
      redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$history[0]['parameter']);
   }
}
?>