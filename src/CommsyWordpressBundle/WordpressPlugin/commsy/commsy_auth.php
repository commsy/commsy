<?php
/*
 * Copyright 2010 Edouard J. Simon, Dr. Iver Jackewitz
*/
/*
Plugin Name: CommSy Authentication
Description: Allows authentication via CommSy, using either a commsy_session_id or CommSy login data. If you are in a multi-user context, please activate this plugin for all blogs and configure the path to CommSy in the file commsy_auth_config.php in the plugin folder. To be able to use this plugin, you must also install and activate the plugins "md5-password-hashes".
Author: Edouard J. Simon, Dr. Iver Jackewitz
Version: 0.3
*/

include_once(dirname(__FILE__) . '/commsy_auth_config.php');

function commsy_auth() {
  global $commsy_auth_commsy_url;

  if (isset($_GET['commsy_session_id'])) {
// try to authenticate via session
    try {
      if($GLOBALS['blog_id'] > 1) {
        $options = array();
        if ( defined('WP_PROXY_HOST') ) {
           $options['proxy_host'] = WP_PROXY_HOST;
        }
        if ( defined('WP_PROXY_PORT') ) {
           $options['proxy_port'] = WP_PROXY_PORT;
        }
        $soapClient = new SoapClient($commsy_auth_commsy_url.'/api/soap?wsdl',$options);
        $user = $soapClient->wordpressAuthenticateViaSession($_GET['commsy_session_id']);
        
        if(isset($user['login'])) {
          #$addUserToBlog = (false == get_user_by('login', $user['login']));
          $user_id = cs_update_user($user);

          // $blogId ??? (15.11.2010 IJ)
          $blogId = $GLOBALS['blog_id']; // (12.05.2011 js)
          $blogusers = get_users(array('blog_id' => $blogId));
          $addUserToBlog = true;
          foreach($blogusers as $bloguser){
          	if($bloguser->ID == $user_id){
          		$addUserToBlog = false;
          	}
          }
          if($addUserToBlog) add_user_to_blog($blogId, $user_id, get_option('default_role'));
        }

        if($user_id) {
          if ( is_user_logged_in() ) {
            wp_logout();
	      }
          add_filter('authenticate', 'cs_authenticate_hashed', 99, 3);
          $result = wp_signon(array('user_login' => $user['login'], 'user_password' => $user['password']));
          if ( !is_wp_error($result) ) {
             add_action('get_header','commsy_clean_permalink',0);
          }
         remove_filter('authenticate', 'cs_authenticate_hashed');

          //wp_login();
        }
      }

    } catch(Exception $e) {
      echo $e->getMessage();
      // session not valid, do nothing
    }
  }
}

function cs_pr ($value) {
   if ( is_object($value)
        and !empty($value->_environment)
      ) {
      $env = $value->_environment;
      unset($value->_environment);
   }
   echo('<pre>');
   print_r($value);
   echo('</pre>'."\n\n");
   if ( !empty($env) ) {
      $value->_environment = $env;
   }
}

function cs_authenticate_hashed($user, $login, $password) {
  global $wpdb;
  $dbUser = get_user_by('login', $login);
/*  // $hash ??? (29.11.2010 ij)
  if($hash == $dbUser->password) {
    $user =  new WP_User($dbUser->ID);
  }
  return $user;*/
return $dbUser;
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

  $userarray['user_login']    = $user['login'];
  $userarray['user_pass']    = isset($user['password']);
  $userarray['user_email']    = $user['email'];
  if(isset($user['firstname']) && isset($user['lastname'])) {
    $userarray['first_name']    = $user['firstname'];
    $userarray['last_name']    = $user['lastname'];
    $userarray['display_name'] = trim($user['firstname']).' '.trim($user['lastname']);
  } else {
    $userarray['display_name'] = $user['login'];
  }

  if($user_id = username_exists($user['login'])) {
    $userarray['ID'] = $user_id;
    // check if something has changed and only than update user
    // check if display is $user['login'] than change to firstname lastname
    // TBD (04.02.2011 ij)
    $result = wp_update_user($userarray);
  } else {
    $user_id = wp_insert_user($userarray);
    if($user_id instanceof WP_Error) throw new Exception(var_export($user_id, true));
    $userarray['ID'] = $user_id;
    // $blogId ??? (15.11.2010 IJ)
    if($blogId) add_user_to_blog($blogId, $userarray['ID'], get_option('default_role'));
  }
  // set md5 hashed password
  cs_set_hashed_password($user['password'], $user_id);
  return $userarray['ID'];
}

add_action('wp_loaded', 'commsy_auth');

// to delete commsy_session_id from URL after login
function commsy_clean_permalink() {
   global $post, $cat, $tag_id;
   if ($_GET || $_SERVER['QUERY_STRING'] != "" || substr($_SERVER['REQUEST_URI'],-1) == "?") {
      if (!is_search() && !is_preview()) {
         if (is_single() || is_page()) {
            $url = get_permalink($post->ID);
         } else if (is_category()) {
            $url = get_category_link($cat);
         } else if (is_tag()) {
            $url = get_tag_link($tag_id);
         } else {
            $url = get_bloginfo('url');
         }
         wp_redirect($url,301);
      }
   }
}
?>
