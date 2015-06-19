<?php
// Release $Name$
//
// Copyright (c)2002-2003 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

function getCSS ( $file, $file_url ) {
   $out = fopen($file,'wb');
   if ( $out == false ) {
      include_once('functions/error_functions.php');
      trigger_error('can not open destination file. - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
   }
   if ( function_exists('curl_init') ) {
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_FILE,$out);
      curl_setopt($ch,CURLOPT_HEADER,0);
      curl_setopt($ch,CURLOPT_URL,$file_url);
      curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
      curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

      global $symfonyContainer;
      $c_proxy_ip = $symfonyContainer->getParameter('commsy.settings.proxy_ip');
      $c_proxy_port = $symfonyContainer->getParameter('commsy.settings.proxy_port');

      if ( !empty($c_proxy_ip) ) {
         $proxy = $c_proxy_ip;
         if ( !empty($c_proxy_port) ) {
            $proxy = $c_proxy_ip.':'.$c_proxy_port;
         }
         curl_setopt($ch,CURLOPT_PROXY,$proxy);
      }
      curl_exec($ch);
      $error = curl_error($ch);
      if ( !empty($error) ) {
         include_once('functions/error_functions.php');
         trigger_error('curl error: '.$error.' - '.$file_url.' - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
      }
      curl_close($ch);
   } else {
      include_once('functions/error_functions.php');
      trigger_error('curl library php5-curl is not installed - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
   }
   fclose($out);
}

     global $symfonyContainer;
     $export_temp_folder = $symfonyContainer->getParameter('commsy.settings.export_temp_folder');
     if(!isset($export_temp_folder)) {
        $export_temp_folder = 'var/temp/zip_export';
     }
     $directory_split = explode("/",$export_temp_folder);
     $done_dir = "./";
     foreach($directory_split as $dir) {
        if(!is_dir($done_dir.'/'.$dir)) {
           mkdir($done_dir.'/'.$dir, 0777);
        }
        $done_dir .= '/'.$dir;
     }
     $directory = './'.$export_temp_folder.'/'.time();
     mkdir($directory,0777);
     $filemanager = $environment->getFileManager();

     //create HTML-File
     $filename = $directory.'/index.html';
     $handle = fopen($filename, 'a');
     //Put page into string
     $output = $page->asHTML();

     //String replacements
     $output = str_replace('commsy_print_css.php?cid='.$environment->getCurrentContextID(),'stylesheet.css', $output);
     $params = $environment->getCurrentParameterArray();

     //find images in string
     $reg_exp = '~\<a\s{1}href=\"(.*)\"\s{1}t~u';
     preg_match_all($reg_exp, $output, $matches_array);
     $i = 0;
     $iids = array();

     if ( !empty($matches_array[1]) ) {
        mkdir($directory.'/images', 0777);
     }

     foreach($matches_array[1] as $match) {
        $new = parse_url($matches_array[1][$i],PHP_URL_QUERY);
        parse_str($new,$out);

        if(isset($out['amp;iid']))
         {
            $index = $out['amp;iid'];
         }
        elseif(isset($out['iid']))
         {
            $index = $out['iid'];
         }
        if(isset($index))
         {
          $file = $filemanager->getItem($index);
          if ( isset($file) ) {
             $icon = $directory.'/images/'.$file->getIconFilename();
             $filearray[$i] = $file->getDiskFileName();
             if(file_exists(realpath($file->getDiskFileName()))) {
                include_once('functions/text_functions.php');
                copy($file->getDiskFileName(),$directory.'/'.toggleUmlaut($file->getFilename()));
                $output = str_replace($match, toggleUmlaut($file->getFilename()), $output);
                copy('htdocs/images/'.$file->getIconFilename(),$icon);

                // thumbs gehen nicht
                // warum nicht allgemeiner mit <img? (siehe unten)
                // geht unten aber auch nicht
                $thumb_name = $file->getFilename() . '_thumb';
                $thumb_disk_name = $file->getDiskFileName() . '_thumb';
                if ( file_exists(realpath($thumb_disk_name)) ) {
                   copy($thumb_disk_name,$directory.'/images/'.$thumb_name);
                   $output = str_replace($match, $thumb_name, $output);
                }
             }
          }
       }
       $i++;
     }

     preg_match_all('~\<img\s{1}style=" padding:5px;"\s{1}src=\"(.*)\"\s{1}a~u', $output, $imgatt_array);
     $i = 0;
     foreach($imgatt_array[1] as $img)
     {
       $img = str_replace($c_single_entry_point.'/'.$c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$img);
       $img = str_replace($c_single_entry_point.'/'.$c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$img);
       #$img = str_replace($c_single_entry_point.'/','',$img);
       #$img = str_replace('?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$img);
       #$img = str_replace('?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$img);
       $imgatt_array[1][$i] = str_replace('_thumb.png','',$img);
       foreach($filearray as $fi)
       {
          $imgname = strstr($fi,$imgatt_array[1][$i]);
          $img = preg_replace('~cid\d{1,}_\d{1,}_~u','',$img);

           if($imgname != false)
         {
            $disc_manager = $environment->getDiscManager();
            $disc_manager->setPortalID($environment->getCurrentPortalID());
            $disc_manager->setContextID($environment->getCurrentContextID());
            $path_to_file = $disc_manager->getFilePath();
            unset($disc_manager);
            $srcfile = $path_to_file.$imgname;
            $target = $directory.'/'.$img;
            $size = getimagesize($srcfile);

            $x_orig= $size[0];
            $y_orig= $size[1];
            $verhaeltnis = $x_orig/$y_orig;
            $max_width = 200;

            if ($x_orig > $max_width) {
               $show_width = $max_width;
               $show_height = $y_orig * ($max_width/$x_orig);
             } else {
               $show_width = $x_orig;
               $show_height = $y_orig;
            }
            switch ($size[2]) {
                  case '1':
                     $im = imagecreatefromgif($srcfile);
                     break;
                  case '2':
                     $im = imagecreatefromjpeg($srcfile);
                     break;
                  case '3':
                     $im = imagecreatefrompng($srcfile);
                     break;
               }
            $newimg = imagecreatetruecolor($show_width,$show_height);
            imagecopyresampled($newimg, $im, 0, 0, 0, 0, $show_width, $show_height, $size[0], $size[1]);
               imagepng($newimg,$target);
               imagedestroy($im);
            imagedestroy($newimg);
         }
       }
       $i++;
     }

     // thumbs_new
     preg_match_all('~\<img(.*)src=\"((.*)_thumb.png)\"~u', $output, $imgatt_array);
     foreach($imgatt_array[2] as $img)
     {
       $img_old = $img;
       $img = str_replace($c_single_entry_point.'/','',$img);
       $img = str_replace('?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$img);
       $img = str_replace('?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$img);
       $img = mb_substr($img,0,mb_strlen($img)/2);
       $img = preg_replace('~cid\d{1,}_\d{1,}_~u','',$img);
       $output = str_replace($img_old,$img,$output);
     }

     $output = str_replace($c_single_entry_point.'/'.$c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$output);
     $output = str_replace($c_single_entry_point.'/'.$c_single_entry_point.'?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$output);
     $output = preg_replace('~cid\d{1,}_\d{1,}_~u','',$output);

     //write string into file
     fwrite($handle, $output);
     fclose($handle);
     unset($output);

     //copy CSS File
     $csssrc = 'htdocs/commsy_print_css.php';
     $csstarget = $directory.'/stylesheet.css';

     mkdir($directory.'/css', 0777);

     $url_to_style = $c_commsy_domain.$c_commsy_url_path.'/css/commsy_print_css.php?cid='.$environment->getCurrentContextID();
     getCSS($directory.'/css/stylesheet.css',$url_to_style);
     unset($url_to_style);

     //create ZIP File
     if(isset($params['iid'])) {
        $zipfile = $export_temp_folder.DIRECTORY_SEPARATOR.$environment->getCurrentModule().'_'.$params['iid'].'.zip';
     }
     else {
        $zipfile = $export_temp_folder.DIRECTORY_SEPARATOR.$environment->getCurrentModule().'_'.$environment->getCurrentFunction().'.zip';
     }
     if(file_exists(realpath($zipfile))) {
        unlink($zipfile);
      }

     if ( class_exists('ZipArchive') ) {
        include_once('functions/misc_functions.php');
        $zip = new ZipArchive();
        $filename = $zipfile;

        if ( $zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE ) {
            include_once('functions/error_functions.php');
            trigger_error('can not open zip-file '.$filename_zip,E_USER_WARNING);
        }
        $temp_dir = getcwd();
        chdir($directory);

        $zip = addFolderToZip('.',$zip);
        chdir($temp_dir);

        $zip->close();
        unset($zip);
        unset($params['downloads']);
     } else {
        include_once('functions/error_functions.php');
        trigger_error('can not initiate ZIP class, please contact your system administrator',E_USER_WARNING);
     }

     //send zipfile by header
     $translator = $environment->getTranslationObject();
     if($environment->getCurrentModule() == 'announcement'){
        $current_module = $translator->getMessage('ANNOUNCEMENT_EXPORT_ITEM_ZIP');
     } elseif($environment->getCurrentModule() == 'material'){
        $current_module = $translator->getMessage('MATERIAL_EXPORT_ITEM_ZIP');
     } elseif($environment->getCurrentModule() == 'date'){
        $current_module = $translator->getMessage('DATE_EXPORT_ITEM_ZIP');
     } elseif($environment->getCurrentModule() == 'discussion'){
        $current_module = $translator->getMessage('DISCUSSION_EXPORT_ITEM_ZIP');
     } elseif($environment->getCurrentModule() == 'todo'){
        $current_module = $translator->getMessage('TODO_EXPORT_ITEM_ZIP');
     } elseif($environment->getCurrentModule() == 'group'){
        $current_module = $translator->getMessage('GROUP_EXPORT_ITEM_ZIP');
     } elseif($environment->getCurrentModule() == 'topic'){
        $current_module = $translator->getMessage('TOPIC_EXPORT_ITEM_ZIP');
     } elseif($environment->getCurrentModule() == 'user'){
        $current_module = $translator->getMessage('USER_EXPORT_ITEM_ZIP');
     } else {
        $current_module = $environment->getCurrentModule();
     }
     if(isset($params['iid'])) {
        $downloadfile = $current_module.'_'.$params['iid'].'.zip';
     }
     else {
        $downloadfile = $current_module.'_'.$environment->getCurrentFunction().'.zip';
     }

    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename="'.$downloadfile.'"');
    readfile($zipfile);
?>