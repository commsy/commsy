<?php
/*
 * 	Copyright (C) 2009 Fabian Gebert <fabiangebert@mediabird.net>
 *  All rights reserved.
 *
 *	This file is part of Mediabird Study Notes.
 */

//include database object and utility functions
include_once("dbo.php");
include_once("utility.php");

//include HTML purifier
if ( !class_exists('HTMLPurifier') ) {
   include_once("filterlib/HTMLPurifier.standalone.php");
}
// if-clause because CommSy from version 8.1.0 also uses HTMLPurifier

//include equation support
include_once("equationsupport/LaTeXrender.php");

//include model base class
include_once("models/model.php");

//include model implementations
include_once("models/content.php");
include_once("models/flashcard.php");
include_once("models/link.php");
include_once("models/markers.php");
include_once("models/question.php");
include_once("models/check.php");
include_once("models/tag_color.php");
include_once("models/topic.php");
include_once("models/upload.php");
include_once("models/user.php");

//include notification support
include_once("notifications/change_info.php");

//include server interface
include_once("controller.php");

?>
