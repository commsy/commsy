<?php
include_once('classes/external_classes/scorm/scormlib.php');
include_once('classes/external_classes/scorm/weblib.php');

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
   
   $pattern = '/&(?!\w{2,6};)/';
   $replacement = '&amp;';
   $xmltext = preg_replace($pattern, $replacement, $manifest_file_xml);

   $objXML = new xml2Array();
   $blocks = $objXML->parse($xmltext);
    
   $scoes = new stdClass();
   $scoes->version = '';
   
   $scorm = scorm_get_manifest($blocks,$scoes);

   #pr($scorm);
   
   $toc = get_scorm_toc($scorm);
   
   $html = '';
   
   $page->add($html);
}

function get_scorm_toc($scorm){
	$manifest_array = array();
	$keys = array_keys($scorm->elements);
   foreach($keys as $key){
   	if(stristr($key, 'MANIFEST')){
   		$manifest_array = $scorm->elements[$key];
   	}
   }
   #pr($manifest_array);
   $organisation_array = array();
   $organisation_keys = array_keys($manifest_array);
   foreach($organisation_keys as $key){
      if(stristr($key, 'ORG')){
         $organisation_array[] = $manifest_array[$key];
      }
   }
   #pr($organisation_array);
}
?>