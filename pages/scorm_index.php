<?php
//include_once('classes/external_classes/scorm/scormlib.php');
//include_once('classes/external_classes/scorm/weblib.php');

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
   
   $dir = './htdocs/'.$c_scorm_dir.'/'.$path_to_file.'scorm_'.$file->getDiskFileNameWithoutFolder();
   if ( !is_dir($dir) ) {
      $zip = new ZipArchive;
  
  		$source_file = $file->getDiskFileName();
  		$res = $zip->open($source_file);
  		if ( $res === TRUE ) {
  		   $zip->extractTo($target_directory);
  		   $zip->close();
  		}
  		unset($zip);
   }
   
   // read xml manifest and create object
   $manifest_file = $dir. '/imsmanifest.xml';
   $xml_object = simplexml_load_file($manifest_file);
   $namespaces = $xml_object->getDocNamespaces();
   $xml_object->registerXPathNamespace('ns', $namespaces['']);
   
   // find organization
   $result = $xml_object->xpath('//ns:organizations[@default]');
   $organization = '';
   if(sizeof($result) == 0) {
     // use first
     $xpath_first_organization = $xml_object->xpath('//ns:organizations/ns:organization[position() = 1]');
     $organization = (string) $xpath_first_organization[0]->attributes()->identifier;
     
   } else {
     // use default
     $organization = (string) $result[0]->attributes()->default;
   }
   
   // find entry point
   $result = $xml_object->xpath('//ns:organizations/ns:organization[@identifier="' . $organization . '"]//ns:item[@identifierref][position() = 1]');
   $entry_identifier = (string) $result[0]->attributes()->identifierref;
   
   // get href for entry point
   $result = $xml_object->xpath('//ns:resources/ns:resource[@identifier="' . $entry_identifier . '"]');
   $html_file = $result[0]->attributes()->href;
   
   // create navigation html
   if(true/*!file_exists($dir . '/navigation.html')*/) {
     $navigation = '';
     $navigation .= '<html>' . LF;
     $navigation .= '<body>' . LF;
     $navigation .= '123' . LF;
     $navigation .= '</body>' . LF;
     $navigation .= '</html>' . LF;
     
     // write to disk
     $handle = fopen($dir . '/navigation_generated.html', 'w');
     fwrite($handle, $navigation);
     fclose($handle);
   }
   
   include_once('functions/misc_functions.php');
   
   // set output mode
   $environment->setOutputMode('BLANK');
         
   $html  = '';
   $html .= '<html>' . LF;
   $html .= '<head>' . LF;
   $html .= '<script type="text/javascript" src="javascript/jQuery/commsy/' . 'scorm_functions_7_5_1_2.js"></script>' . LF;
   $html .= '</head>' . LF;
   $html .= '<frameset rows="100%" framespacing="0" frameborder="0" border="0" cols="300,*">' . LF;
   $html .= '<frame name="scorm_navigation" marginwidth="0" marginheight="0" style="background-color: rgb(200, 200, 200);" src="'.$c_scorm_dir.'/'.$path_to_file.'scorm_'.$file->getDiskFilenameWithoutFolder().'/navigation_generated.html"/>' . LF;
   $html .= '<frame name="scorm_content" marginwidth="0" marginheight="0" src="'.$c_scorm_dir.'/'.$path_to_file.'scorm_'.$file->getDiskFileNameWithoutFolder().'/'.$html_file.'"/>' . LF;
   $html .= '</frameset>' . LF;
   $html .= '</html>' . LF;
   
   //$page->add($html);
   echo $html;
}

// function get_scorm_toc($scorm){
// 	$manifest_array = array();
// 	$keys = array_keys($scorm->elements);
//    foreach($keys as $key){
//    	if(stristr($key, 'MANIFEST')){
//    		$manifest_array = $scorm->elements[$key];
//    	}
//    }
//    #pr($manifest_array);
//    $organisation_array = array();
//    $organisation_keys = array_keys($manifest_array);
//    foreach($organisation_keys as $key){
//       if(stristr($key, 'ORG')){
//          $organisation_array[] = $manifest_array[$key];
//       }
//    }
//    #pr($organisation_array);
// }
?>