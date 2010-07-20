<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2008 Iver Jackewitz
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

// error reporting and debugging
// Log errors to this database.
// Only errors that are reported through the CommSy
// error handler go to this log database.
$c_error_log_db = true;

// Support for debugging message-tags
// if set, message-tags will not be resolved
// $dont_resolve_messagetags = true;
// if set, used messagetag without translation will be logged to the db
$c_messagetag_log_db = true;

// message tags in configuration (default == false) --- only for developers !!!!
$c_message_management = true;

// send emails (default == true) --- only for developers !!!!
// true = send emails
// false = don't send emails
// print = don't send emails but echo them in the htmlpage (usefull for debugging)
$c_send_email = 'print';

// support for debugging
$c_show_debug_infos = false;

// enable and configure logging to file
$c_enable_logging_to_file = false;
$c_logging_file = '';

// enable security for HTML textarea
$c_enable_htmltextarea_security = false;

// use cron new
// save cron result in log file
// don't show it in HTML
// ATTENTION: maybe the newsletter will be send twice
// default = false -> use old cron
// $c_cron_use_new = true;

// use project ids stored in community room items
// default = false -> use LEFT JOIN room -> link_items -> room
//$c_cache_cr_pr = true;
?>