<?php
if ( !empty($_GET['iid']) ) {
   $file_manager = $environment->getFileManager();
   $file = $file_manager->getItem($_GET['iid']);
   unset($file_manager);
   
   // are we allowed to open this file?
   $link_manager = $environment->getLinkManager();
   $material_id = $link_manager->getMaterialIDForFileID($file->getFileID());
   $material_manager = $environment->getMaterialManager();
   $material = $material_manager->getItem($material_id);
   unset($material_manager);
   $current_user = $environment->getCurrentUser();
   
   if($material->isNotActivated() && $current_user->getItemID() != $material->getCreatorID() && !$current_user->isModerator()) {
     $params = array();
     $params['environment'] = $environment;
     $params['with_modifying_actions'] = true;
     $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
     unset($params);
     $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
     $page->add($errorbox);
   } elseif(!$material->maySee($current_user) && !$material->mayExternalSee($current_user)) {
     $params = array();
     $params['environment'] = $environment;
     $params['with_modifying_actions'] = true;
     $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
     unset($params);
     $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
     $page->add($errorbox);
   } else {
     // is zip unpacked?
     $disc_manager = $environment->getDiscManager();
     $disc_manager->setPortalID($environment->getCurrentPortalID());
     $disc_manager->setContextID($environment->getCurrentContextID());
     $path_to_file = $disc_manager->getFilePath();
     unset($disc_manager);
     
     $dir = $path_to_file . 'public/scorm/scorm_' . $file->getDiskFileNameWithoutFolder();
     if ( true) {//!is_dir($dir) ) {
        $zip = new ZipArchive;
    
        $source_file = $file->getDiskFileName();
        $res = $zip->open($source_file);
        if ( $res === TRUE ) {
           $zip->extractTo($dir);
           $zip->close();
        }
        unset($zip);
        
        // create .htaccess
        if(true) {
          $hta = 'RewriteEngine on' . "\r\n";
          $hta .= 'RewriteRule ^.*$ ./../../../../htdocs/route.php' . "\r\n";
          $handle = fopen($path_to_file . 'public/.htaccess', 'w');
          fwrite($handle, $hta);
          fclose($handle);
        }
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
       $navgiation .= '<head>' . LF;
       $navigation .= '<link rel="stylesheet" media="screen" type="text/css" href="'.$c_commsy_url_path.'/css/commsy_portal_room_merged_css.php?cid='.$environment->getCurrentContextID().'"/>'.LF;
       $navigatio .= '</head>' . LF;
       $navigation .= '<body>' . LF;
       
       // create navigation from active organization
       $result = $xml_object->xpath('//ns:organizations/ns:organization[@identifier="' . $organization . '"]/ns:title');
       $organization_title = (string) $result[0];
       
       $navigation .= '<div class="scorm_navigation_block">' . LF;
       $navigation .= $organization_title . LF;
       
       // get items in organization hierarchie(first level)
       $result = $xml_object->xpath('//ns:organizations/ns:organization[@identifier="' . $organization . '"]/ns:item');
       foreach($result as $item) {
         $identifier = (string) $item->attributes()->identifier;
         $navigation .= '<div class="scorm_navigation_block">' . LF;
         $navigation .= (string) $item->title . LF;
         
         // get sub-items
         $sub_result = $xml_object->xpath('//ns:organizations/ns:organization[@identifier="' . $organization . '"]/ns:item[@identifier="' . $identifier . '"]/ns:item/ns:title');
         foreach($sub_result as $title) {
           $navigation .= '<div class="scorm_navigation_block">' . LF;
           $navigation .= (string) $title . LF;
           $navigation .= '</div>' . LF;
         }
         $navigation .= '</div>' . LF;
       }
       $navigation .= '</div>' . LF;
       
       $navigation .= '</body>' . LF;
       $navigation .= '</html>' . LF;
       
       // write to disk
       $handle = fopen($dir . '/cs_navigation_generated.html', 'w');
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
     $html .= '<frame name="scorm_navigation" marginwidth="0" marginheight="0" style="background-color: rgb(200, 200, 200);" src="./../' . $dir . '/cs_navigation_generated.html"/>' . LF;
     $html .= '<frame name="scorm_content" marginwidth="0" marginheight="0" src="./../' . $dir . '/' . $html_file.'"/>' . LF;
     $html .= '</frameset>' . LF;
     $html .= '</html>' . LF;
     
     echo $html;
   }
}
?>