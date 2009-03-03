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

$current_user = $environment->getCurrentUser();
if ( !isset($c_message_management)
     or ( isset($c_message_management)
          and !$c_message_management
        )
     or $current_user->isGuest()
   ) {
   redirect($environment->getCurrentContextID(),'home','index');
}

$translator = $environment->getTranslationObject();
$message = $translator->getCompleteMessageArray();
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = true;
$language_form_view = $class_factory->getClass(LANGUAGE_FORM_VIEW,$params);
unset($params);
$language_form_view->setAction(curl($environment->getCurrentContextID(),$current_module,$current_function,''));
$language_form_view->setTitle('Management von Übersetzung und Sprachen');
if ( $environment->inPortal() or $environment->inServer() ){
   $page->addForm($language_form_view);
} else {
   $page->add($language_form_view);
}
?>