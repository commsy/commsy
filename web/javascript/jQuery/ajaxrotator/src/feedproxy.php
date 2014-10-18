<?php
//visit http://thecodecentral.com/2008/11/12/ctrotator-a-flexible-itemimage-rotator-script-for-jquery
//a simple feed proxy
//change the feed URL of your choice

$feedUrl = 'http://thecodecentral.com/feed';

header('Content-Type: text/xml');
echo file_get_contents($feedUrl);