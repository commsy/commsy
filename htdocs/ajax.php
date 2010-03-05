<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

mb_internal_encoding('UTF-8');
if ( isset($_GET['cid']) ) {
	if ( isset($_GET['hid']) ) {
		chdir('..');
	   include_once('etc/cs_constants.php');
	   include_once('etc/cs_config.php');
	   
	   // start of execution time
	   include_once('functions/misc_functions.php');
	   $time_start = getmicrotime();
	   
	   include_once('classes/cs_environment.php');
	   $environment = new cs_environment();
	   $environment->setCurrentContextID($_GET['cid']);
	   $context_item = $environment->getCurrentContextItem();
	   $hash_manager = $environment->getHashManager();
	   $translator = $environment->getTranslationObject();
	
	   $validated = false;
	   if ( $context_item->isOpenForGuests() ) {
	      $validated = true;
	   }
	
	   if ( !$context_item->isPortal()
	   and !$context_item->isServer()
	   and isset($_GET['hid'])
	   and !empty($_GET['hid'])
	   and !$validated
	   ) {
	      if ( !$context_item->isLocked()
	      and $hash_manager->isAjaxHashValid($_GET['hid'],$context_item)
	      ) {
	         $validated = true;
	      }
	   }
	   if($validated) {
	   	if(isset($_GET['fct'])){
	   		if($_GET['fct'] == 'privateroom_rss_ticker'){
	   			privateroom_rss_ticker();
	   		}
	   	}
	   }
	} else {
		chdir('..');
	   include_once('etc/cs_constants.php');
	   include_once('etc/cs_config.php');
	   include_once('classes/cs_environment.php');
	   $environment = new cs_environment();
	   $translator = $environment->getTranslationObject();
	   die($translator->getMessage('AJAX_NO_HID_GIVEN'));
	}
} else {
	chdir('..');
	include_once('etc/cs_constants.php');
	include_once('etc/cs_config.php');
	include_once('classes/cs_environment.php');
	$environment = new cs_environment();
	$translator = $environment->getTranslationObject();
	die($translator->getMessage('AJAX_NO_CID_GIVEN'));
}

function privateroom_rss_ticker(){
	/*
	======================================================================
	LastRSS bridge script- By Dynamic Drive (http://www.dynamicdrive.com)
	Communicates between LastRSS.php to Advanced Ajax ticker script using Ajax. Returns RSS feed in XML format
	Created: Feb 9th, 2006. Updated: Feb 9th, 2006
	======================================================================
	*/
	
	header('Content-type: text/xml');
	
	// include lastRSS
	include "javascript/jQuery/rsstickerajax/lastrss/lastRSS.php"; //path to lastRSS.php on your server from this script ("bridge.php")
	
	// Create lastRSS object
	$rss = new lastRSS;
	$rss->cache_dir = 'cache'; //path to cache directory on your server from this script. Chmod 777!
	$rss->date_format = 'M d, Y g:i:s A'; //date format of RSS item. See PHP date() function for possible input.
	
	// List of RSS URLs
	$rsslist=array(
	"Spiegel" => "http://www.spiegel.de/schlagzeilen/index.rss",
	"Sport1" => "http://www.sport1.de/de_1/startseite/rss.xml",
	"Tagesschau" => "http://www.tagesschau.de/xml/rss2",
	"BBC" => "http://newsrss.bbc.co.uk/rss/newsonline_uk_edition/front_page/rss.xml",
	"news.com" => "http://news.com.com/2547-1_3-0-5.xml",
	"slashdot" => "http://rss.slashdot.org/Slashdot/slashdot",
	"dynamicdrive" => "http://www.dynamicdrive.com/export.php?type=new"
	);
	
	#global $rss_ticker_array;
	#$rsslist = $rss_ticker_array;
	
	////Beginners don't need to configure past here////////////////////
	
	$rssid=$_GET['id'];
	$rssurl=isset($rsslist[$rssid])? $rsslist[$rssid] : die("Error: Can't find requested RSS in list.");
	
	// -------------------------------------------------------------------
	// outputRSS_XML()- Outputs the "title", "link", "description", and "pubDate" elements of an RSS feed in XML format
	// -------------------------------------------------------------------
	
	function outputRSS_XML($url) {
	    global $rss;
	    $cacheseconds=(int) $_GET["cachetime"]; //typecast "cachetime" parameter as integer (0 or greater)
	    $rss->cache_time = $cacheseconds;
	    if ($rs = $rss->get($url)) {
	       echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<rss version=\"2.0\">\n<channel>\n";
	            foreach ($rs['items'] as $item) {
	                echo "<item>\n<link>$item[link]</link>\n<title>$item[title]</title>\n<description>$item[description]</description>\n<pubDate>$item[pubDate]</pubDate>\n</item>\n\n";
	            }
	       echo "</channel></rss>";
	            if ($rs['items_count'] <= 0) { echo "<li>Sorry, no items found in the RSS file :-(</li>"; }
	    }
	    else {
	        echo "Sorry: It's not possible to reach RSS file $url\n<br />";
	        // you will probably hide this message in a live version
	    }
	}
	
	// ===============================================================================
	
	outputRSS_XML($rssurl);
}
?>