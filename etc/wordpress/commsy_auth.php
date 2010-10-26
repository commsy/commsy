<?php
/* 
 * Copyright 2010 Edouard J. Simon
*/
/*
Plugin Name: CommSy Auth
Plugin URI: NONE
Description: Allows for authentication via CommSy using commsy_session_id
Author: Edouard J. Simon
Version: 0.1
Author URI: NONE
*/

function commsy_auth() {
  if (isset($_GET['commsy_session_id'])) {
    // try to authenticate via session
    try {
      if($GLOBALS['blog_id'] > 1) {

        $soapClient = new SoapClient('http://commsy/soap_wsdl.php');
        $username = $soapClient->authenticateViaSession($_GET['commsy_session_id']);

        include_once('wp-includes/registration.php');

        $userarray['user_login'] 	= $username;
        $userarray['user_pass'] 	= $_GET['commsy_session_id'];
        $userarray['first_name'] 	= null;
        $userarray['last_name'] 	= null;
        $userarray['user_url'] 	= null;
        $userarray['display_name'] = $username;

        if($id = username_exists($username)) {
          $userarray['ID'] = $id;
          wp_update_user($userarray);
        } else {
          $user_id = wp_insert_user($userarray);
          $userarray['ID'] = $user_id;
          add_user_to_blog($GLOBALS['blog_id'], $userarray['ID'], 'administrator');
        }

        wp_logout();
        $result = wp_signon(array('user_login' => $userarray['user_login'], 'user_password' => $userarray['user_pass']));
      }

    } catch(Exception $e) {
       // session not valid, do nothing
    }
  }
}

add_action('wp_loaded', 'commsy_auth');

?>