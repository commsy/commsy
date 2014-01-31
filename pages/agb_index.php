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
include_once('functions/language_functions.php');
$commsy = $environment->getCurrentContextItem();
$text_array = $commsy->getAGBTextArray();
$language = $commsy->getLanguage();
if ( $language == 'user' ) {
   $language = getSelectedLanguage();
}
$text = $environment->getTextConverter()->cleanDataFromTextArea($text_array[mb_strtoupper($language, 'UTF-8')]);
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = false;
$text_view = $class_factory->getClass(TEXT_VIEW,$params);
unset($params);
$text_view->setText($text);
if ( $environment->inPortal() ) {
   $page->addAGBView($text_view);
   $page->setShowAGBs();
} else {
   $page->add($text_view);
}

$page->setWithoutNavigationLinks();
$page->setWithoutLeftMenue();
$page->withoutCommSyFooter();
$page->setFocusOnload();
?>