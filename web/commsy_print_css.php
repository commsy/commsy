<?php
use Symfony\Component\HttpFoundation\Request;

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

header("Content-type: text/css");
// load required classes
chdir('..');
include_once('etc/cs_constants.php');
include_once('etc/cs_config.php');
include_once('classes/cs_environment.php');

// create environment of this page
$color = $cs_color['DEFAULT'];

// find out the room we're in
if (!empty($_GET['cid'])) {
    $cid = $_GET['cid'];

    $loader = require_once __DIR__.'/../app/bootstrap.php.cache';

    require_once __DIR__.'/../app/AppKernel.php';

    $kernel = new AppKernel('prod', true);
    $kernel->loadClassCache();
    $request = Request::createFromGlobals();
    $response = $kernel->handle($request);

    $container = $kernel->getContainer();
    $environment = $container->get('commsy_legacy.environment')->getEnvironment();
    $environment->setCurrentContextID($cid);
    $room = $environment->getCurrentContextItem();
    $color = $room->getColorArray();

    $kernel->terminate($request, $response);

    header('Content-type: text/css', true);
}
?>

/*General Settings */
body {
   margin: 0px;
   padding: 0px;
   font-family: Arial, Helvetica, sans-serif;
   font-size: 10pt;
   background-color: white;
}

img {
   border: 0px;
}


/* Hyperlinks*/
a {
   color: <?php echo($color['hyperlink'])?>;
   text-decoration: none;
}

a:hover, a:active {
   text-decoration: underline;
}




/* General purpose styles */
.infocolor{
   color: <?php echo($color['info_color'])?>;
}

.infoborder{
   border-top: 1px solid <?php echo($color['info_color'])?>;
}

table.detail td {
   vertical-align: baseline;
}

table.detail td.key {
   color: <?php echo($color['disabled'])?>;
   white-space: nowrap;
}

.disabled, .inactive, .key {
   color: <?php echo($color['disabled'])?>;
}

.changed {
   color: #7E7E7E;
   font-size: 8pt;
}

.required {
   color: #7E7E7E;
   font-weight: bold;
}

div.main{
  margin:0px;
  padding:0px 5px;
}

div.content_fader{
  width: 100%;
  margin:0px;
  padding:0px 5px;
}

div.content{
  padding:0px;
  margin:0px;
}


h1{
   margin:0px;
   padding:0px 0px 0px 10px;
   font-size:30px;
}

h2.pagetitle{
  margin:0px;
  font-size: 16pt;
  font-family: verdana, arial, sans-serif;
}

.normal{
   font-size: 10pt;
}

.desc {
   font-size: 8pt;
}

.bold{
   font-size: 10pt;
   font-weight: bold;
}

.list {
   border-collapse: collapse;
   width: 100%;
}

.list td.even  {
   background-color: #E5E5E5;
   padding: 2px 3px;
}

.list td.odd  {
   padding: 2px 3px;
}

.list td {
   padding: 2px 3px;
}

.list td.head {
   background-color: #7E7E7E;
   color: white;
   border-bottom: none;
   padding: 3px 3px;
   font-weight:bold;
   white-space:nowrap;
}


div.detail_sub_items_title{
   padding:3px 11px;
   border-bottom:1px solid #B0B0B0; vertical-align:top;
   background-color: <?php echo($color['myarea_title_backround'])?>;
}

h2.pagetitle{
  margin:0px;
  font-size: 16pt;
  font-family: verdana, arial, sans-serif;
}

span.sub_item_pagetitle{
  margin:0px;
  font-size: 16pt;
  font-weight:bold;
  font-family: arial, Nimbus Sans L, sans-serif;
  color: <?php echo($color['myarea_section_title'])?>;
}

div.gauge {
   height:5px;
   margin: 3px 10px;
   border: 1px solid #666;
   font-size:10px;
}

div.gauge-bar {
   background-color: #7E7E7E;
   height:5px;
   text-align: right;
   color:<?php echo($color['headline_text'])?>;
   font-size:10px;
}