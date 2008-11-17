<?php
// definitions and initialisiation
$main_folder = 'classes/';
$view_folder = 'views/';
$class_config = array();

// views
define('ACCOUNT_INDEX_VIEW','ACCOUNT_INDEX_VIEW');
$class_name = ACCOUNT_INDEX_VIEW;
$class_config[$class_name]['name'] = 'cs_account_index_view';
$class_config[$class_name]['filename'] = 'cs_account_index_view.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

define('ANNOUNCEMENT_DETAIL_VIEW','ANNOUNCEMENT_DETAIL_VIEW');
$class_name = ANNOUNCEMENT_DETAIL_VIEW;
$class_config[$class_name]['name'] = 'cs_announcement_detail_view';
$class_config[$class_name]['filename'] = 'cs_announcement_detail_view.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;

define('ACTIVITY_VIEW','ACTIVITY_VIEW');
$class_name = ACTIVITY_VIEW;
$class_config[$class_name]['name'] = 'cs_activity_view';
$class_config[$class_name]['filename'] = 'cs_activity_view.php';
$class_config[$class_name]['folder'] = $main_folder.$view_folder;
$class_config[$class_name]['switchable'] = false;
?>