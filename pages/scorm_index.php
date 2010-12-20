<?php
include_once('classes/external_classes/scorm/scormlib.php');
include_once('classes/external_classes/scorm/weblib.php');

if ( !empty($_GET['iid']) ) {
	global $c_scorm_dir;
	
   $file_manager = $environment->getFileManager();
   $file = $file_manager->getItem($_GET['iid']);

   // is zip unpacked?
   $disc_manager = $environment->getDiscManager();
   $disc_manager->setPortalID($environment->getCurrentPortalID());
   $disc_manager->setContextID($environment->getCurrentContextID());
   $path_to_file = $disc_manager->getFilePath();
   unset($disc_manager);
   
   #if ( !is_dir('./'.$c_scorm_dir.'/') ) {
   #	mkdir('./'.$c_scorm_dir.'/');
   #}
   
   $dir = './htdocs/'.$c_scorm_dir.'/'.$path_to_file.'scorm_'.$file->getDiskFileNameWithoutFolder();
   if ( !is_dir($dir) ) {
      $zip = new ZipArchive;
		$target_directory = './htdocs/'.$c_scorm_dir.'/'.$path_to_file.'scorm_'.$file->getDiskFileNameWithoutFolder().'/';

		$source_file = $file->getDiskFileName();
		$res = $zip->open($source_file);
		if ( $res === TRUE ) {
		   $zip->extractTo($target_directory);
		   $zip->close();
		}
		unset($zip);
   }
   
   $manifest_file = './htdocs/'.$c_scorm_dir.'/'.$path_to_file.'scorm_'.$file->getDiskFileNameWithoutFolder().'/imsmanifest.xml';
   $manifest_file_xml = file_get_contents($manifest_file);
   
   $manifest_file_xml_array = explode("\n", $manifest_file_xml);
   
   $html_file = '';
   foreach($manifest_file_xml_array as $manifest_file_xml_line){
   	if(stristr($manifest_file_xml_line, 'type="webcontent"')){
   		$matches = array();
   		preg_match('~href="([^"])*"~isu', $manifest_file_xml_line, $matches);
   		if(isset($matches[0])){
   			if(stristr($matches[0], 'href')){
   			   $href_array = explode('"', $matches[0]);
   			   $html_file = $href_array[1];
   			}
   		}
   	}
   }
   
   #$html_include = file_get_contents('./'.$c_scorm_dir.'/'.$path_to_file.'scorm_'.$file->getDiskFileNameWithoutFolder().'/'.$html_file);
   
   
   //resource identifier
   
   /*$pattern = '/&(?!\w{2,6};)/';
   $replacement = '&amp;';
   $xmltext = preg_replace($pattern, $replacement, $manifest_file_xml);

   $objXML = new xml2Array();
   $blocks = $objXML->parse($xmltext);
    
   $scoes = new stdClass();
   $scoes->version = '';
   
   $scorm = scorm_get_manifest($blocks,$scoes);

   #pr($scorm);
   
   $toc = get_scorm_toc($scorm);*/
   
   $html  = '';
   $html .= '<html>';
   $html .= '<frameset rows="100%">';
   $html .= '<frame src="'.$c_scorm_dir.'/'.$path_to_file.'scorm_'.$file->getDiskFileNameWithoutFolder().'/'.$html_file.'">';
   $html .= '</frameset>';
   $html .= '</html>';
   
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