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
$form_folder = 'forms/';

$class_name = 'cs_rubric_form';
define('RUBRIC_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_announcement_form';
define('ANNOUNCEMENT_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_discussion_form';
define('DISCUSSION_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_discarticle_form';
define('DISCARTICLE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_material_form';
define('MATERIAL_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_date_form';
define('DATE_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_search_short_form';
define('SEARCH_SHORT_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = true;

$class_name = 'cs_configuration_agb_form';
define('CONFIGURATION_AGB_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;









$class_name = 'cs_form';
define('FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

$class_name = 'cs_configuration_htmltextarea_form';
define('CONFIGURATION_HTMLTEXTAREA_FORM',$class_name);
$class_config[$class_name]['name']       = $class_name;
$class_config[$class_name]['filename']   = $class_name.'.php';
$class_config[$class_name]['folder']     = $main_folder.$form_folder;
$class_config[$class_name]['switchable'] = false;

?>