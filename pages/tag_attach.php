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


include_once('classes/cs_list.php');

// Get the translator object
$translator = $environment->getTranslationObject();

if ( isset($_GET['ref_attach_iid']) ) {
   $ref_iid = $_GET['ref_attach_iid'];
} elseif ( isset($_POST['ref_attach_iid']) ) {
   $ref_iid = $_POST['ref_attach_iid'];
}

if ( isset($_POST['option']) ) {
   $option = $_POST['option'];
} elseif ( isset($_GET['option']) ) {
   $option = $_GET['option'];
} else {
   $option = '';
}

if ( isset($_POST['return_attach_tag_list']) ) {
   $second_call = true;
} elseif ( isset($_GET['return_attach_tag_list']) ) {
   $second_call = true;
} else {
   $second_call = false;
}



if ($environment->getCurrentFunction() != 'edit'){
   $params = $environment->getCurrentParameterArray();
   $item_manager = $environment->getItemManager();
   $tmp_item = $item_manager->getItem($params['iid']);
   $manager = $environment->getManager($tmp_item->getItemType());
   $item = $manager->getItem($params['iid']);

}
if ( !empty($option)
      and (isOption($option, $translator->getMessage('COMMON_TAG_NEW_ATTACH')))
    ) {
    $tag_array = array();
    if (isset($_POST['taglist'])){
       $selected_id_array = $_POST['taglist'];
       foreach($selected_id_array as $id => $value){
          $tag_array[] = $id;
       }
    }
    $item->setTagListByID($tag_array);
    $item->save();
    unset($params['attach_view']);
    unset($params['attach_type']);
    redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
}

$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $with_modifying_actions;
$tag_view = $class_factory->getClass(TAG_INDEX_VIEW,$params);
unset($params);

// Set data for buzzword_view
$tag_view->setItem($item);

?>