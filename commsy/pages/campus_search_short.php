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

// Get the translator object
$translator = $environment->getTranslationObject();

if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} elseif(!empty($_POST['enter'])) {
   $command = $_POST['enter'];
} else {
   $command = '';
}

// Initialize the form
$class_params= array();
$class_params['environment'] = $environment;
$search_short_form = $class_factory->getClass(SEARCH_SHORT_FORM,$class_params);
unset($class_params);

if (isOption($command,$translator->getMessage('HOME_SEARCH_SHORT_BUTTON'))) {
   $search_short_form->setFormPost($_POST);
   $search_short_form->prepareForm();
   $search_short_form->loadValues();
   if ($search_short_form->check()) {
      $campus_search_limit = $_POST['search_text'];
      $campus_search_rubric = $_POST['selrubric'];
      if ( isset($_POST['only_files']) and !empty($_POST['only_files']) ) {
         $session->setValue('cid'.$environment->getCurrentContextID().'_campus_search_index_files',1);
      }
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_campus_search_index_selrestriction');
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_campus_search_index_search');
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_campus_search_index_selrubric');
      $campus_search_from = 1;
      $session->setValue('cid'.$environment->getCurrentContextID().'_campus_search_index_from',$campus_search_from);
      $params = $environment->getCurrentParameterArray();
      $params['selrubric'] = $campus_search_rubric;
      if ( isset($_POST['only_files']) and !empty($_POST['only_files']) ) {
         $params['only_files'] = 1;
      }
      $params['search'] = $campus_search_limit;
      redirect($environment->getCurrentContextID(), $environment->getCurrentModule(), 'index',$params);
   }
} else {
   // Prepare view object
   $search_short_form->prepareForm();
   $search_short_form->loadValues();
}
// Add form view to page
$params = array();
$params['environment'] = $environment;
$campus_search_short_view = $class_factory->getClass(SEARCH_SHORT_VIEW,$params);
unset($params);
$campus_search_short_view->setForm($search_short_form);
$campus_search_short_view->setAction(curl($environment->getCurrentContextID(),'campus_search','short',''));
$page->addRight($campus_search_short_view);
?>