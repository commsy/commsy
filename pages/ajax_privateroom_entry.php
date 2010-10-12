<?php
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

include_once('functions/development_functions.php');
if(isset($_GET['do'])and isset($_GET['action'])){
	if($_GET['do'] == 'update_buzzwords' and $_GET['action']= 'add_item'){
	   if (isset($_GET['buzzword_id'])and isset($_GET['item_id'])){
	      $buzzword_manager = $environment->getBuzzwordManager();
	      $buzzword_item = $buzzword_manager->getItem($_GET['buzzword_id']);
	      $id_array = $buzzword_item->getAllLinkedItemIDArrayLabelVersion();
	      if (!in_array($_GET['item_id'],$id_array)){
	         $id_array[] = 	$_GET['item_id'];
	      }
	      $buzzword_item->saveLinksByIDArray($id_array);
	      $count = count($id_array);
	      $mincount = 0;
	      $maxcount = 30;
	      $minsize = 10;
	      $maxsize = 20;
	      $tresholds=0;
          if( empty($tresholds) ) {
             $tresholds = $maxsize-$minsize;
             $treshold = 1;
          } else {
             $treshold = ($maxsize-$minsize)/($tresholds-1);
          }
          $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
          $font_size = round($minsize+round($a)*$treshold);
	      $page->add($_GET['item_id'],$font_size);
	   }
	}elseif($_GET['do'] == 'update_mylist' and $_GET['action']= 'add_item'){
	   if (isset($_GET['mylist_id'])and isset($_GET['item_id'])){
	      $mylist_manager = $environment->getMylistManager();
	      $mylist_item = $mylist_manager->getItem($_GET['mylist_id']);
	      $id_array = $mylist_item->getAllLinkedItemIDArrayLabelVersion();
	      if (!in_array($_GET['item_id'],$id_array)){
	         $id_array[] = 	$_GET['item_id'];
	      }
	      $mylist_item->saveLinksByIDArray($id_array);
	      $page->add($_GET['item_id'],count($id_array));
	   }
	}elseif($_GET['do'] == 'update_matrix' and $_GET['action']= 'add_item'){
      if (isset($_GET['row_id']) and isset($_GET['column_id']) and isset($_GET['item_id'])){
      	$matrix_manager = $environment->getMatrixManager();
         $counter = $matrix_manager->insertItem($_GET['item_id'],$_GET['column_id'],$_GET['row_id']);
         $count = '<a href=\"commsy.php?cid='.$environment->getCurrentContextID().'&mod=entry&fct=index&selmatrix='.$_GET['column_id'].'_'.$_GET['row_id'].'\">'.$counter.'</a>';
      	$page->add('matrix_counter',$count);
      }
   }
}
?>