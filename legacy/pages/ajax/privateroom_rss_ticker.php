<?php
global $context_item;
/*
======================================================================
LastRSS bridge script- By Dynamic Drive (http://www.dynamicdrive.com)
Communicates between LastRSS.php to Advanced Ajax ticker script using Ajax. Returns RSS feed in XML format
Created: Feb 9th, 2006. Updated: Feb 9th, 2006
======================================================================
*/

header('Content-type: text/xml');

// include lastRSS
include_once('htdocs/javascript/jQuery/rsstickerajax/lastrss/lastRSS.php'); //path to lastRSS.php on your server from this script ("bridge.php")

// Create lastRSS object
$rss = new lastRSS();

// proxy support
global $symfonyContainer;
$c_proxy_ip = $symfonyContainer->getParameter('commsy.settings.proxy_ip');
$c_proxy_port = $symfonyContainer->getParameter('commsy.settings.proxy_port');

if ( !empty($c_proxy_ip) ) {
   $rss->setProxyIP($c_proxy_ip);
}
if ( !empty($c_proxy_port) ) {
   $rss->setProxyPort($c_proxy_port);
}

$rss->cache_dir = 'cache'; //path to cache directory on your server from this script. Chmod 777!
$rss->date_format = 'M d, Y g:i:s A'; //date format of RSS item. See PHP date() function for possible input.

// List of RSS URLs
$portlet_array = $context_item->getPortletRSSArray();
$rsslist=array();
foreach($portlet_array as $rss_item){
   $rsslist[$rss_item['title']] = $rss_item['adress'];
}

////Beginners don't need to configure past here////////////////////

$rssid=$_GET['id'];
$rssurl=isset($rsslist[$rssid])? $rsslist[$rssid] : die("Error: Can't find requested RSS in list.");

// -------------------------------------------------------------------
// outputRSS_XML()- Outputs the "title", "link", "description", and "pubDate" elements of an RSS feed in XML format
// -------------------------------------------------------------------

function outputRSS_XML($url,$rss) {
   $cacheseconds=(int) $_GET["cachetime"]; //typecast "cachetime" parameter as integer (0 or greater)
   $rss->cache_time = $cacheseconds;
   if ($rs = $rss->get($url)) {
      $encoding = 'ISO-8859-1';
      if ( !empty($rs['encoding']) ) {
         $encoding = $rs['encoding'];
      }
      echo "<?xml version=\"1.0\" encoding=\"".$encoding."\"?>\n<rss version=\"2.0\">\n<channel>\n";
      foreach ($rs['items'] as $item) {
         if (isset($item['description'])){
            echo "<item>\n<link>$item[link]</link>\n<title>$item[title]</title>\n<description>$item[description]</description>\n<pubDate>$item[pubDate]</pubDate>\n</item>\n\n";
         }else{
            echo "<item>\n<link>$item[link]</link>\n<title>$item[title]</title>\n<description>...</description>\n<pubDate>$item[pubDate]</pubDate>\n</item>\n\n";
         }
      }
      echo "</channel></rss>";
      if ($rs['items_count'] <= 0) { echo "<li>Sorry, no items found in the RSS file :-(</li>"; }
   } else {
      echo "Sorry: It's not possible to reach RSS file $url\n<br />";
      // you will probably hide this message in a live version
   }
}

// ===============================================================================

outputRSS_XML($rssurl,$rss);
?>