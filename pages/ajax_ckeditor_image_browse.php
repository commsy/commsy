<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
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

include_once('functions/development_functions.php');

$item_manager = $environment->getItemManager();
$item = $item_manager->getItem($_GET['iid']);

$temp_files = array();
$file_list_files = $item->getFileList();

$file_list_files_array = $file_list_files->to_array();
pr(size_of($file_list_files_array));

if ( !$file_list_files->isEmpty() ) {
   $file = $file_list_files->getFirst();
   while( $file ) {
   	$filename = $file->getFilename();
   	if(mb_stristr(mb_strtolower($filename, 'UTF-8'),'png')
         or mb_stristr(mb_strtolower($filename, 'UTF-8'),'jpg')
         or mb_stristr(mb_strtolower($filename, 'UTF-8'),'jpeg')
         or mb_stristr(mb_strtolower($filename, 'UTF-8'),'gif')){
         $temp_files[$file->getFileID()] = $filename;
         $file = $file_list_files->getNext();
      }
   }
}

$temp_files_upload = array();
$file_manager = $environment->getFileManager();
$file_manager->reset();
$file_manager->setTempUploadSessionIdLimit($environment->getSessionId());
$file_manager->select();
$file_list_files_upload = $file_manager->get();
$file_item = $file_list_files_upload->getFirst();
while($file_item){
   $temp_files_upload[$file_item->getFileID()] = $file_item->getFilename();
   $file_item = $file_list_files_upload->getNext();
}
unset($file_manager);

$files = array_merge($temp_files, $temp_files_upload);

$page->addHtml();
$html  = '';
$html .= '<script type="text/javascript" src="javascript/jQuery/jquery-1.4.1.min.js"></script>'.LF;
$html .= '<script type="text/javascript" src="javascript/jQuery/commsy/commsy_ckeditor.js"></script>'.LF;
$html .= '<div style="text-align:center; widht:100%;">';
$html .= '<br/><br/>'.LF;
$html .= '<form id="ckeditor_file_form">'.LF;
$html .= '<input type="hidden" id="ckeditor_file_func_number" value="'.$_GET['CKEditorFuncNum'].'" />'.LF;
$html .= '<select id="ckeditor_file_select">'.LF;
foreach($files as $id => $filename){
   $link = 'commsy.php/'.$filename.'?cid='.$environment->getCurrentContextID().'&mod=material&fct=getfile&iid='.$id;
   $html .= '<option name="ckeditor_file" value="'.$link.'">'.$filename.'</option>'.LF;
}
$html .= '</select>'.LF;
$html .= '<br/><br/>'.LF;
$html .= '<input type="submit" value="'.$translator->getMessage('COMMON_CHOOSE_BUTTON').'">'.LF;
$html .= '</form>'.LF;
$html .= '</div>';
$page->add($html);

?>