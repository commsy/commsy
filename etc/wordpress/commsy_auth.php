<?php
/* 
 * Copyright 2010 Edouard J. Simon
*/
/*
Plugin Name: CommSy Auth
Plugin URI: NONE
Description: Allows for authentication via CommSy, using either commsy_session_id or login data
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
        $user = $soapClient->wordpressAuthenticateViaSession($_GET['commsy_session_id']);
        if(isset($user['login'])) {
          $addUserToBlog = (false == get_user_by('login', $user['login']));
          $user_id = cs_update_user($user);
          if($addUserToBlog) add_user_to_blog($blogId, $user_id, get_option('default_role'));
        }

        if($user_id) {
          wp_logout();
          add_filter('authenticate', 'cs_authenticate_hashed', 99, 3);
          $result = wp_signon(array('user_login' => $user['login'], 'user_password' => $user['password']));
          remove_filter('authenticate', 'cs_authenticate_hashed');
//          wp_login();
        }
      }

    } catch(Exception $e) {
      echo $e->getMessage();
      // session not valid, do nothing
    }
  }
}


function cs_authenticate_hashed($user, $login, $password) {
  global $wpdb;
  $dbUser = get_user_by('login', $login);
  if($hash == $dbUser->password) {
    $user =  new WP_User($dbUser->ID);
  }
  return $user;
}

function cs_set_hashed_password($hash, $user_id) {
  global $wpdb;
  $wpdb->update($wpdb->users, array('user_pass' => $hash, 'user_activation_key' => ''), array('ID' => $user_id) );

  wp_cache_delete($user_id, 'users');
}

function cs_update_user($user) {
  if(!isset($user['login']) || empty($user['login'])
          || !isset($user['password']) || empty($user['password'])
  ) throw new Exception('login and / or password missing');

  include_once('wp-includes/registration.php');

  $userarray['user_login'] 	= $user['login'];
  $userarray['user_pass'] 	= isset($user['password']);
  $userarray['user_email'] 	= $user['email'];
  if(isset($user['firstname']) && isset($user['lastname'])) {
    $userarray['first_name'] 	= $user['firstname'];
    $userarray['last_name'] 	= $user['lastname'];
  }
  $userarray['display_name'] = $user['login'];

  if($user_id = username_exists($user['login'])) {
    $userarray['ID'] = $user_id;
    $result = wp_update_user($userarray);
  } else {
    $user_id = wp_insert_user($userarray);
    if($user_id instanceof WP_Error) throw new Exception(var_export($user_id, true));
    $userarray['ID'] = $user_id;
    if($blogId) add_user_to_blog($blogId, $userarray['ID'], get_option('default_role'));
  }
  // set md5 hashed password
  cs_set_hashed_password($user['password'], $user_id);
  return $userarray['ID'];

}

add_action('wp_loaded', 'commsy_auth');

?>
