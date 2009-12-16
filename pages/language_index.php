<?PHP
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

$current_user = $environment->getCurrentUser();
if ( !isset($c_message_management)
     or ( isset($c_message_management)
          and !$c_message_management
        )
     or $current_user->isGuest()
   ) {
   redirect($environment->getCurrentContextID(),'home','index');
}

include_once('classes/cs_language.php');
$translator = $environment->getTranslationObject();
$message = $translator->getCompleteMessageArray();

if (!empty($_GET['sortby'])) {
   $sortby = $_GET['sortby'];
} else {
   $sortby = 'message';
}

$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = true;
$table_view = $class_factory->getClass(TABLE_VIEW,$params);
unset($params);
$table_view->addColumn($translator->getMessage('MESSAGE_TAG'),true,$sortby=='message',$translator->getMessage('MESSAGE_TAG'),'message','');
$table_view->addAction($translator->getMessage('LANGUAGE_EDIT'),true,'','language','edit');

foreach (array_keys(current($message)) as $item){
    $table_view->addColumn($translator->getMessage($item),true,$sortby==$item,$translator->getMessage($item),$item,'');
}

if (!($sortby == 'message')) {
      foreach($message as $key => $value){
          $tag= $key;
          $message[$tag]['messagetag'] = $tag;
      }
     usort($message,create_function('$a,$b','return strnatcasecmp($a[\''.$sortby.'\'],$b[\''.$sortby.'\']);'));

    foreach($message as $key => $value){
        $row = array();
        $params = array();
        $params['MessageID'] = $message[$key]['messagetag'];
        $row[] =ahref_curl( $environment->getCurrentContextID(), $current_module, 'edit', $params, $message[$key]['messagetag'], $title='', $target='', $fragment = '' );
        unset($params);
        foreach($value as $key => $value){
         $row[] = $value;
        }
    $table_view->addRow($row);
    }

}else{
    foreach($message as $key => $value){
        $row = array();
        $params = array();
        $params['MessageID'] = $key;
        $row[] = ahref_curl( $environment->getCurrentContextID(), $current_module, 'edit', $params, $key, $title='', $target='', $fragment = '' );
        unset($params);
        foreach($value as $key => $value){
            $row[] = $value;
        }
    $table_view->addRow($row);
    }
}
if ( $environment->inPortal() or $environment->inServer() ){
   $page->addConfigurationListView($table_view);
} else {
   $page->add($table_view);
}
?>