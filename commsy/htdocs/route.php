<?php
  // pretend, we work from the CommSy basedir to allow
  // giving include files without "../" prefix all the time.
  chdir('..');
  
  // setup commsy-environment
  include_once('etc/cs_constants.php');
  include_once('etc/cs_config.php');
  include_once('classes/cs_environment.php');
  $environment = new cs_environment();
  $environment->setCacheOff();

  // get requested uri
  $uri = $_SERVER['REQUEST_URI'];
  
  // adjust uri
  $pattern = '/.*\/commsy\/(.*)/'; 
  preg_match($pattern, $uri, $matches);
  $uri = $matches[1];
  
  // try to get file id from scorm package
  $pattern = '/var\/.*\/.*\/public\/scorm\/scorm_(.*).zip\/.*/';
  preg_match($pattern, $uri, $matches);
  if(isset($matches[1])) {
    $file_id = $matches[1];
	
    // authentification
    $file_manager = $environment->getFileManager();
    $file = $file_manager->getItem($file_id);
    unset($file_manager);
    
    // are we allowed to open this file?
    $link_manager = $environment->getLinkManager();
    $material_id = $link_manager->getMaterialIDForFileID($file->getFileID());
    $material_manager = $environment->getMaterialManager();
    $material = $material_manager->getItem($material_id);
    unset($material_manager);
    $current_user = $environment->getCurrentUser();
    
    // set current context
    $environment->setCurrentContextID($material->getContextID());
    var_dump($environment->getCurrentContextItem()->mayEnter($current_user));
    
    if( !($material->isNotActivated() && $current_user->getItemID() != $material->getCreatorID() && !$current_user->isModerator()) &&
        $environment->getCurrentContextItem()->mayEnterByUserID($current_user->getUserID(), $current_user->getAuthSource())) {
        //($material->maySee($current_user) || $material->mayExternalSee($current_user))) {
      // deliver secured content
      readfile($uri);
    }
  }
?>