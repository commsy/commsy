<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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

// pretend, we work from the CommSy basedir to allow
// giving include files without "../" prefix all the time.
chdir('..');

// include base-config
include_once('etc/cs_constants.php');
include_once('etc/cs_config.php');

// setup commsy-environment
include_once('classes/cs_environment.php');
$environment = new cs_environment();

// Die Methoden in dieser Klasse werden weiter unten als Soap Service bereit gestellt
require_once('classes/cs_connection_soap.php');

// Den WSDL Cache abschalten
#ini_set("soap.wsdl_cache_enabled", "0");

/*
    Erzeugt eine neue SoapServer Instanz. Der erste Parameter (null) bedeutet, dass keine WSDL Datei verwendet werden soll.
    Wenn keine WSDL Datei angegeben wird, muss die uri Option gesetzt sein.
*/
$server = new SoapServer(null, array('uri' => 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']));
#$wsdl_url = 'http://';
#$wsdl_url .= $_SERVER['HTTP_HOST'];
#$wsdl_url .= str_replace('soap.php','soap_wsdl.php',$_SERVER['PHP_SELF']);
#$wsdl_url .= str_replace('soap.php','commsy.wsdl',$_SERVER['PHP_SELF']);
#$server = new SoapServer($wsdl_url);
/*
    Bestimmt, dass alle �ffentlichen Funktionen der Klasse cs_connection_soap f�r den Client erreichbar sein sollen
*/
$server->setClass("cs_connection_soap",$environment);

/*
    Behandelt den Soap Request des Clients. Die Antwort wird in XML "verpackt" und an den Client zur�ckgeschickt
*/
$server->handle();
?>