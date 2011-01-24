<?php
  // get requested uri
  $uri = $_SERVER['REQUEST_URI'];
  
  // adjust uri
  $pattern = '/.*\/htdocs\/scorm\/(.*)/';
  preg_match($pattern, $uri, $matches);
  $uri = $matches[1];
  
  // authentification
  if(true) {// :p
    // deliver secured content
    readfile($uri);
  }
?>