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

global $environment;
$current_user = $environment->getCurrentUser();
if ($current_user->isAllowedToCreateContext()) {
    include_once('pages/configuration_preferences.php');
} else {
    $params = array();
    $params['environment'] = $environment;
    $params['with_modifying_actions'] = true;
    $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
    unset($params);
    $errorbox->setText($translator->getMessage('USER_IS_ALLOWED_TO_CREATE_CONTEXT_FORM_ERROR'));
    $page->add($errorbox);
}

?>