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

// Get data from database
$item_manager = $environment->getItemManager();
$item_manager->setContextLimit($environment->getCurrentContextID());
$item_manager->setCommunityHomeLimit();
$item_manager->setIntervalLimit(20);
$item_manager->select();
$item_list = $item_manager->get();            // returns a cs_list of items
#$ids = $item_manager->getIDs();

$item = $item_list->getFirst();
$new_item_list = new cs_list();
while($item){
   $type = $item->getItemType();
#   $item_manager = $environment->getManager();
#   $new_item = $labelmanager->getItem($item->getItemID());
#   $new_item_list->add($new_item);

   switch ($type){
      case 'label':
         $labelmanager = $environment->getLabelmanager();
         $new_item = $labelmanager->getItem($item->getItemID());
         $new_item_list->add($new_item);
         break;
      case 'materials':
         $materialmanager = $environment->getMaterialmanager();
         $new_item = $materialmanager->getItem($item->getItemID());
         $new_item_list->add($new_item);
         break;
      case CS_MATERIAL_TYPE:
         $materialmanager = $environment->getMaterialmanager();
         $new_item = $materialmanager->getItem($item->getItemID());
         $new_item_list->add($new_item);
         break;
   }
   $item = $item_list->getNext();
}

// Prepare view object
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = true;
$item_short_view = $class_factory->getClass(ITEM_SHORT_VIEW,$params);
unset($params);

// Set data for view
$item_short_view->setList($new_item_list);
// Add list view to page
$page->addLeft($item_short_view);

?>