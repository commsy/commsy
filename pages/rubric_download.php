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


     global $export_temp_folder;
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
     $output = str_replace('commsy_pda_css.php?cid='.$environment->getCurrentContextID(),'stylesheet.css', $output);
     $params = $environment->getCurrentParameterArray();

     //find images in string
     $reg_exp = '/\<a\s{1}href=\"(.*)\"\s{1}t/';
     preg_match_all($reg_exp, $output, $matches_array);
     $i = 0;
     $iids = array();

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
          $icon = $directory.'/'.$file->getIconFilename();
          $filearray[$i] = $file->getDiskFileName();
          if(file_exists(realpath($file->getDiskFileName()))) {
             copy($file->getDiskFileName(),$directory.'/'.$file->getFilename());
             $output = str_replace($match, $file->getFilename(), $output);
             copy('htdocs/images/'.$file->getIconFilename(),$icon);
             $output = str_replace('images/'.$file->getIconFilename(),$file->getIconFilename(), $output);

             $thumb_name = $file->getFilename();
             $point_position = strrpos($thumb_name,'.');
             $thumb_name = substr_replace ( $thumb_name, '_thumb.png', $point_position , strlen($thumb_name));
             $thumb_disk_name = $file->getDiskFileName();
             $point_position = strrpos($thumb_disk_name,'.');
             $thumb_disk_name = substr_replace ( $thumb_disk_name, '_thumb.png', $point_position , strlen($thumb_disk_name));
             if ( file_exists(realpath($thumb_disk_name)) ) {
                copy($thumb_disk_name,$directory.'/'.$thumb_name);
                $output = str_replace($match, $thumb_name, $output);
             }
          }
       }
       $i++;
     }

     preg_match_all('/\<img\s{1}style=" padding:5px;"\s{1}src=\"(.*)\"\s{1}a/', $output, $imgatt_array);
     $i = 0;
     foreach($imgatt_array[1] as $img)
     {
       $img = str_replace('commsy.php/commsy.php?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$img);
       $img = str_replace('commsy.php/commsy.php?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$img);
       #$img = str_replace('commsy.php/','',$img);
       #$img = str_replace('?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$img);
       #$img = str_replace('?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$img);
       $imgatt_array[1][$i] = str_replace('_thumb.png','',$img);
       foreach($filearray as $fi)
       {
          $imgname = strstr($fi,$imgatt_array[1][$i]);
          $img = preg_replace('/cid\d{1,}_\d{1,}_/','',$img);

           if($imgname != false)
         {
            $srcfile = 'var/'.$environment->getCurrentPortalID().'/'.$environment->getCurrentContextID().'/'.$imgname;
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
     preg_match_all('/\<img(.*)src=\"((.*)_thumb.png)\"/', $output, $imgatt_array);
     foreach($imgatt_array[2] as $img)
     {
       $img_old = $img;
       $img = str_replace('commsy.php/','',$img);
       $img = str_replace('?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$img);
       $img = str_replace('?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$img);
       $img = substr($img,0,strlen($img)/2);
       $img = preg_replace('/cid\d{1,}_\d{1,}_/','',$img);
       $output = str_replace($img_old,$img,$output);
     }

     $output = str_replace('commsy.php/commsy.php?cid='.$environment->getCurrentContextID().'&amp;mod=picture&amp;fct=getfile&amp;picture=','',$output);
     $output = str_replace('commsy.php/commsy.php?cid='.$environment->getCurrentContextID().'&mod=picture&fct=getfile&picture=','',$output);
     $output = preg_replace('/cid\d{1,}_\d{1,}_/','',$output);
     //write string into file
     fwrite($handle, $output);
     fclose($handle);
     unset($output);
     //copy CSS File
     if (isset($params['view_mode'])){
        $csssrc = 'htdocs/commsy_pda_css.php';
     } else {
        $csssrc = 'htdocs/commsy_print_css.php';
     }
     $csstarget = $directory.'/stylesheet.css';
     copy($csssrc,$csstarget);

     //create ZIP File
     if(isset($params['iid'])) {
        $zipfile = $export_temp_folder.'/'.$environment->getCurrentModule().'_'.$params['iid'].'.zip';
     }
     else {
        $zipfile = $export_temp_folder.'/'.$environment->getCurrentModule().'_'.$environment->getCurrentFunction().'.zip';
     }
     if(file_exists(realpath($zipfile))) {
        unlink($zipfile);
      }
     //include zip class
     include_once('classes/external_classes/zip.php');

     $zip = new ziparch();
     //copy file into ziparchive
     // mkzip(srcdirectory, targetfile, catch, include_basedir)
     $zip->mkzip($directory,$zipfile, true, false);

    unset($zip);
    unset($params['downloads']);

    //send zipfile by header
     if(isset($params['iid'])) {
        $downloadfile = $environment->getCurrentModule().'_'.$params['iid'].'.zip';
     }
     else {
        $downloadfile = $environment->getCurrentModule().'_'.$environment->getCurrentFunction().'.zip';
     }

    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename="'.$downloadfile.'"');
    readfile($zipfile);


?>