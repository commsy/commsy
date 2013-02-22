<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2009 Fabian Gebert (Mediabird), Dr. Iver Jackewitz (CommSy),
//                   Frank Wolf (Mediabird)
//
// This file is part of the mediabird plugin for CommSy.
//
// This plugin is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This plugin is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You have received a copy of the GNU General Public License
// along with the plugin.

// some settings
$plugin_folder = 'plugins';
$plugin_name = '/mediabird';
$plugin_dir = $plugin_folder.$plugin_name;

// Define the proxy or cache expire time
$ExpireTime = 3600; // seconds (= one hour)
// Set cache/proxy informations:
header('Cache-Control: max-age=' . $ExpireTime); // must-revalidate
header('Expires: '.gmdate('D, d M Y H:i:s', time()+$ExpireTime).' GMT');

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="stylesheet" type="text/css" href="<?php echo($c_commsy_domain.$c_commsy_url_path.'/'.$plugin_dir); ?>/css/dummy.css">
        <title>Mediabird Study Notes</title>
    </head>
    <body>
        <span></span>
    </body>
</html>
<?php
exit();
?>