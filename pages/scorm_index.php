<?php
if ( !empty($_GET['iid']) ) {
   $file_manager = $environment->getFileManager();
   $file = $file_manager->getItem($_GET['iid']);

   // is zip unpacked?
   $disc_manager = $environment->getDiscManager();
   $disc_manager->setPortalID($environment->getCurrentPortalID());
   $disc_manager->setContextID($environment->getCurrentContextID());
   $path_to_file = $disc_manager->getFilePath();
   unset($disc_manager);
   $dir = './'.$path_to_file.'scorm_'.$file->getDiskFileNameWithoutFolder();
   if ( !is_dir($dir) ) {
      $zip = new ZipArchive;
		$target_directory = $path_to_file.'scorm_'.$file->getDiskFileNameWithoutFolder().'/';
		
		global $export_temp_folder;
		if ( !isset($export_temp_folder) ) {
		   $export_temp_folder = 'var/temp/scorm_export';
		}

		$source_file = $file->getDiskFileName();
		$res = $zip->open($source_file);
		if ( $res === TRUE ) {
		   $zip->extractTo($target_directory);
		   $zip->close();
		}
		unset($zip);
   }
   
   $manifest_file = './'.$path_to_file.'scorm_'.$file->getDiskFileNameWithoutFolder().'/imsmanifest.xml';
   $manifest_file_xml = file_get_contents($manifest_file);
   
   //resource identifier
   
   $html = '';
   
   $page->add($html);
}
?>