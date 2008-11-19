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

// forms
$form_folder = 'form_views/';

$class_name = 'cs_form_view';
define('FORM_VIEW',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;






$class_name = 'cs_color_configuration_form_view';
define('CONFIGURATION_COLOR_FORM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_form_view';
define('CONFIGURATION_FORM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_home_form_view';
define('CONFIGURATION_HOME_FORM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_rubric_form_view';
define('CONFIGURATION_RUBRIC_FORM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_form_view_left';
define('FORM_LEFT_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_form_view_plain';
define('FORM_PLAIN_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_language_form_view';
define('LANGUAGE_FORM_VIEW',$class_name);
$class_config[$class_name]['name'] = $class_name;
$class_config[$class_name]['filename'] = $class_name.'.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

?>