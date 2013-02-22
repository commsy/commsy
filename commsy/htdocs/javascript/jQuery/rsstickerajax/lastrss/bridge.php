<?php

/*
======================================================================
LastRSS bridge script- By Dynamic Drive (http://www.dynamicdrive.com)
Communicates between LastRSS.php to Advanced Ajax ticker script using Ajax. Returns RSS feed in XML format
Created: Feb 9th, 2006. Updated: Feb 9th, 2006
======================================================================
*/

header('Content-type: text/xml');

// include lastRSS
include "lastRSS.php"; //path to lastRSS.php on your server from this script ("bridge.php")

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

?>