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

// definitions and initialisiation
$class_config = array();
$main_folder = 'classes/';

// views
include_once('etc/config_classes_views.php');

// forms
include_once('etc/config_classes_forms.php');

// other classes
$sub_folder = '';


$class_name = 'misc_text_converter';

$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name . '.php';
$class_config[$class_name]['folder'] = $main_folder . $sub_folder;
$class_config[$class_name]['switchable'] = false;

if (!defined('MISC_TEXT_CONVERTER')) {
    define('MISC_TEXT_CONVERTER', $class_name);
}