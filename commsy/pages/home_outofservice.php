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

if ( isset($_POST['option'])
     and $_POST['option'] == $translator->getMessage('MYAREA_LOGIN_BUTTON')
   ) {
   include_once('pages/context_login.php');
   exit();
}

$server = $environment->getServerItem();
$text = $environment->getTextConverter()->cleanDataFromTextArea($server->getOutOfService());

$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = false;
$text_view = $class_factory->getClass(OUTOFSERVICE_VIEW,$params);
unset($params);
$text_view->setText($text);
if ( $environment->inPortal() or $environment->inServer() ) {
   $page->addAGBView($text_view);
   $page->setShowAGBs();
} else {
   $page->add($text_view);
}
$page->setNavigationBar($translator->getMessage('SERVER_OUTOFSERVICE_NAVBAR_TITLE'));

$page->setWithoutNavigationLinks();
$page->setWithoutLeftMenue();
$page->withoutCommSyFooter();
$page->setFocusOnload();
?>