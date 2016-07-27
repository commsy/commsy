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

// case: login with CommSy
if ( isset($session) ) {
   $history = $session->getValue('history');
   $cookie = $session->getValue('cookie');
   $javascript = $session->getValue('javascript');
   $https = $session->getValue('https');
   $flash = $session->getValue('flash');
}
// case: login with external login box
else {
   $history = array();
   $cookie = '';
   $javascript = '';
   $https = '';
   $flash = '';
}

$session = new cs_session_item();
$session->createSessionID($user_id);
if ( $cookie == '1' ) {
   $session->setValue('cookie',2);
} elseif ( empty($cookie) ) {
   // do nothing, so CommSy will try to save cookie
} else {
   $session->setValue('cookie',0);
}
if ($javascript == '1') {
   $session->setValue('javascript',1);
} elseif ($javascript == '-1') {
   $session->setValue('javascript',-1);
}
if ($https == '1') {
   $session->setValue('https',1);
} elseif ($https == '-1') {
   $session->setValue('https',-1);
}
if ($flash == '1') {
   $session->setValue('flash',1);
} elseif ($flash == '-1') {
   $session->setValue('flash',-1);
}

// save portal id in session to be sure, that user didn't
// switch between portals
if ( $environment->inServer() ) {
   $session->setValue('commsy_id',$environment->getServerID());
} else {
   $session->setValue('commsy_id',$environment->getCurrentPortalID());
}

// auth_source
if ( empty($auth_source) ) {
   $auth_source = $authentication->getAuthSourceItemID();
}
$session->setValue('auth_source',$auth_source);
?>