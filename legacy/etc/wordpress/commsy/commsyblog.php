<?php
/*
 * Copyright 2010 Edouard J. Simon, Dr. Iver Jackewitz
*/
/*
Plugin Name: CommSy Gateway
Description: This plugin connects CommSy with WordPress via WSDL. One WordPress-Blog for one CommSy workspace. If you are in a multi-user context, this plugin should only be activated for the root blog. Attention: you must configure and activate the plugin "CommSy Authentication" first.
Author: Edouard J. Simon, Dr. Iver Jackewitz
Version: 0.5
*/

define('CS_ROLE_MOD',         'moderator');
define('CS_ROLE_USER',        'user');
define('CS_STATUS_WP_ADMIN',  'administrator');
define('CS_STATUS_WP_EDITOR', 'editor');
define('CS_STATUS_WP_AUTHOR', 'author');
define('CS_STATUS_WP_CONTRI', 'contributor');
define('CS_STATUS_CS_MOD',    '3');
define('CS_STATUS_CS_USER',   '2');

include_once(dirname(__FILE__).'/commsy_auth_config.php');

class commsy_blog {
   private $_valid_session_id_array = array();
   private $_sessionid_to_userid_array = array();
   private $_userid_role_allow_array = array();
   private $_role_translator_array = array();
   private $_soap_client = NULL;

   private function _getSoapClient () {
      $retour = NULL;
      if ( !empty($this->_soap_client) ) {
         $retour = $this->_soap_client;
      } else {
         $options = array();
         if ( defined('WP_PROXY_HOST') ) {
            $options['proxy_host'] = WP_PROXY_HOST;
         }
         if ( defined('WP_PROXY_PORT') ) {
            $options['proxy_port'] = WP_PROXY_PORT;
         }
         global $commsy_auth_commsy_url;
         $this->_soap_client = new SoapClient($commsy_auth_commsy_url.'/soap_wsdl.php',$options);
         if ( isset($this->_soap_client) ) {
            $retour = $this->_soap_client;
         }
      }
      return $retour;
   }

   private function _checkCommSySessionID ($session_id) {
      $retour = false;
      try {
         $soapClient = $this->_getSoapClient();
         $user_id = $soapClient->authenticateViaSession($session_id);
         if ( !empty($user_id) ) {
            $retour = true;
            $this->_sessionid_to_userid_array[$session_id] = $user_id;
         }
      } catch(Exception $e) {
         throw new SoapFault('_checkCommSySessionID', 'can not establish connection to the commsy server: '.$e->getMessage());
      }
      return $retour;
   }

   private function _isSessionValid ($session_id) {
      $retour = false;
      if ( !empty($this->_valid_session_id_array[$session_id]) ) {
         $retour = true;
      } else {
         $success = $this->_checkCommSySessionID($session_id);
         if ( !is_soap_fault($success) and $success ) {
            $this->_valid_session_id_array[$session_id] = $session_id;
            $retour = true;
         }
      }
      return $retour;
   }

   private function _isUserAllowed ($session_id,$role,$blog_id='',$cs_room_id='',$ask_commsy = false) {
      $retour = false;
      if ( !empty($session_id)
           and !empty($role)
           and !empty($this->_sessionid_to_userid_array[$session_id])
         ) {
         $user_id = $this->_sessionid_to_userid_array[$session_id];
         if ( !empty($this->_userid_role_allow_array[$user_id][$role]) ) {
            $retour = $this->_userid_role_allow_array[$user_id][$role];
         } else {
            // first: initialize role-array
            $this->_initRoleArray();

            // second: ask wordpress
            $role_array = array();
            if ( !empty($blog_id) ) {  // ask commsy, don not ask root blog (blog_id = 0)
               $role_array = $this->_getRoleInBlog($user_id,$blog_id);
               if ( !empty($role_array) ) {
                  $retour = $this->_checkRoleArray($role_array,$role);
               }
            }

            // third: ask commsy, if user does not exists in special blog
            //        or get default_role
            if ( empty($role_array) ) {
               if ($ask_commsy) {
                  if ( empty($cs_room_id)
                       and !empty($blog_id)
                     ) {
                     switch_to_blog($blog_id);
                     $cs_room_id = get_option('commsy_context_id');
                     restore_current_blog();
                  }
                  $role_array = $this->_getRoleInCommSy($session_id,$cs_room_id);
               } elseif ( !empty($blog_id) ) {
                  switch_to_blog($blog_id);
                  $role_array[] = get_option('default_role');
                  restore_current_blog();
               }
               if ( !empty($role_array) ) {
                  $retour = $this->_checkRoleArray($role_array,$role);
               }
            }
            $this->_userid_role_allow_array[$user_id][$role] = $retour;
         }
      }
      return $retour;
   }

   private function _getRoleInCommSy ($session_id, $cs_room_id='') {
      $retour = array();
      if ( !empty($session_id)
           and !empty($cs_room_id)
         ) {
         $soapClient = $this->_getSoapClient();
         $user_info = $soapClient->getUserInfo($session_id,$cs_room_id);
         if ( !is_soap_fault($user_info) ) {
            if ( !empty($user_info) ) {
               $user_info = '<user>'.$user_info.'</user>';
               $user_info = commsy_XMLToArray($user_info);
               if ( isset($user_info['status']) and !empty($user_info['status'])  ) {
                  $retour[] = $user_info['status'];
               }
            }
         }
      }
      return $retour;
   }

   private function _isUserInCommSy ($session_id, $cs_room_id='') {
      $retour = false;
      if ( !empty($session_id) ) {
         if ( empty($cs_room_id) ) {
            $retour = $this->_isSessionValid($session_id);
         } else {
            $role_array = $this->_getRoleInCommSy($session_id, $cs_room_id);
            if ( !empty($role_array[0]) ) {
               $retour = true;
            }
         }
      }
      return $retour;
   }

   private function _getRoleInBlog ($user_id, $blog_id='') {
      $retour = array();
      if ( !empty($blog_id) ) {
         switch_to_blog($blog_id);
      }
      $user = new WP_User( $user_id );
      if ( !empty( $user->roles )
           and is_array( $user->roles )
         ) {
         $retour = $user->roles;
      }
      unset($user);
      if ( !empty($blog_id) ) {
         restore_current_blog();
      }
      return $retour;
   }

   private function _initRoleArray () {
      $this->_role_translator_array[CS_ROLE_MOD] = array(CS_STATUS_WP_ADMIN,CS_STATUS_CS_MOD);
      $this->_role_translator_array[CS_ROLE_USER] = array(CS_STATUS_CS_USER,CS_STATUS_WP_EDITOR,CS_STATUS_WP_AUTHOR,CS_STATUS_WP_CONTRI);
   }

   private function _checkRoleArray ( $role_array, $role ) {
      $retour = false;
      if ( !empty($role_array) ) {
         foreach ( $role_array as $user_role ) {
            if ( in_array($user_role,$this->_role_translator_array[$role]) ) {
               $retour = true;
               break;
            }
         }
      }

      // maybe user is admin
      if ( !$retour
           and $role == CS_ROLE_USER
         ) {
         $retour = $this->_checkRoleArray($role_array,CS_ROLE_MOD);
      }
      return $retour;
   }

  /**
   * @param string $session_id session_id to authenticate user
   * @param array $user
   * @param array $blog
   *
   * @return array
   */
   public function createBlog($session_id,$user, $blog) {
      if ( $this->_isSessionValid($session_id)
           and $this->_isUserAllowed($session_id,'moderator','',$blog['cid'],true)
         ) {
         try {
            if(!isset($user['login']) || empty($user['login'])
               || !isset($user['email']) || empty($user['email'])
               || !isset($blog['path']) || empty($blog['path'])
               || !isset($blog['title']) || empty($blog['title'])
               || !defined('DOMAIN_CURRENT_SITE')
               || !defined('PATH_CURRENT_SITE')
              ) {
               $vars = var_export(array('user' => $user, 'blog' => $blog, 'DOMAIN_CURRENT_SITE' => DOMAIN_CURRENT_SITE, 'PATH_CURRENT_SITE' => PATH_CURRENT_SITE), true);
               throw new Exception('Supplied data not valid:'.$vars);
            }

            $user_id = cs_update_user($user);
            $blog_id = wpmu_create_blog(DOMAIN_CURRENT_SITE, PATH_CURRENT_SITE.$blog['path'], $blog['title'], $user_id);
         } catch (Exception $e) {
            throw new SoapFault('createblog', 'Create Blog failed: '.$e->getMessage());
         }
         return array('user_id' => $user_id, 'blog_id' => $blog_id);
      } else {
         if ( !$this->_isSessionValid($session_id) ) {
            throw new SoapFault('createblog', 'Create Blog failed: Session-ID ('.$session_id.') is not valid');
         } else {
            throw new SoapFault('createblog', 'Create Blog failed: User ('.$this->_sessionid_to_userid_array[$session_id].') is not allowed to create a blog.');
         }
      }
   }

   public function deleteBlog($session_id,$blogId) {
      $retour = false;
      if ( $this->_isSessionValid($session_id)
           and $this->_isUserAllowed($session_id,CS_ROLE_MOD,$blogId)
         ) {
         switch_to_blog($blogId);
         include_once('wp-admin/includes/ms.php');
         wpmu_delete_blog($blogId, true);
         restore_current_blog();
         $retour = true;
      } else {
         if ( !$this->_isSessionValid($session_id) ) {
            throw new SoapFault('deleteblog', 'Delete Blog failed: Session-ID ('.$session_id.') is not valid');
         } else {
            throw new SoapFault('deleteblog', 'Delete Blog failed: User ('.$this->_sessionid_to_userid_array[$session_id].') is not allowed to delete this blog ('.$blogId.').');
         }
      }
      return $retour;
   }

   private function _isUserOfBlog ( $user_id, $blog_id ) {
      $retour = false;
      if ( !empty($user_id)
           and !empty($blog_id)
         ) {
         switch_to_blog($blog_id);
         $blog_users = get_users_of_blog();
         if ( !empty($blog_users) ) {
            foreach ( $blog_users as $blog_user) {
               if ( !empty($blog_user->user_login)
                    and strtolower($blog_user->user_login) == strtolower($user_id)
                  ) {
                  $retour = true;
                  break;
               }
            }
         }
         restore_current_blog();
      }
      return $retour;
   }

  /**
   * @param string $session_id session_id to authenticate user
   * @param array $post Data to insert
   * @param array $user Data to insert
   * @param int $blogId Data to insert
   * @param string $category Data to insert
   * @return int Id of Post or 0 on error
   */
  public function insertPost($session_id,$post, $user, $blogId, $category, $postId) {
    if ( $this->_isSessionValid($session_id)
         and $this->_isUserAllowed($session_id,CS_ROLE_USER,$blogId)
       ) {
      try {
        if($blogId == 0) throw new Exception('Invalid Blog ID!');
        switch_to_blog($blogId);
        $catId = get_cat_ID($category);
        if($catId == 0) {
          include_once('wp-admin/includes/taxonomy.php');
          $catId = wp_create_category( $category );
        }
        $post['post_category'] = array($catId);
        $post['post_author'] = cs_update_user($user);

        if ( !$this->_isUserOfBlog($user['login'],$blogId) ) {
           $success = add_user_to_blog($blogId, $post['post_author'], get_option('default_role'));
        }

        if($postId != ''){
        	  $post['ID'] = $postId;
           wp_update_post($post);
        } else {
           $postId = wp_insert_post($post);
        }
        restore_current_blog();
      } catch (Exception $e) {
        throw new SoapFault('insertpost', 'Insert Post failed: '.$e->getMessage());
      }
    } else {
      if ( !$this->_isSessionValid($session_id) ) {
        throw new SoapFault('insertPost', 'insert post failed: Session-ID ('.$session_id.') is not valid');
      } else {
        throw new SoapFault('insertPost', 'insert post failed: User ('.$this->_sessionid_to_userid_array[$session_id].') is not allowed to add post to this blog ('.$blogId.').');
      }
    }
    return $postId;
  }

  /**
   * @param array $file
   * @param int $blogId
   *
   * @return string URL to file
   */
  public function insertFile($session_id, $file, $blogId) {
    if ( $this->_isSessionValid($session_id)
         and $this->_isUserAllowed($session_id,CS_ROLE_USER,$blogId)
       ) {
      try {
        switch_to_blog($blogId);
        $uploadDir = wp_upload_dir();
        $blogDir = $uploadDir['path'].'/';
        if(file_exists($blogDir.$file['name'])) {
          $counter = 1;
          while(file_exists($blogDir.$counter.$file['name'])) $counter++;
          $file['name'] = $counter.$file['name'];
        }
        if(!file_put_contents($blogDir.$file['name'], base64_decode($file['data']))) {
          throw new Exception('copying of file '.$file['name'].' to '.$uploadDir['path'].'/'.$file['name'].' failed.');
        }
        restore_current_blog();
      } catch(Exception $e) {
        throw new SoapFault('file upload failed', $e->getMessage());
      }
    } else {
      if ( !$this->_isSessionValid($session_id) ) {
        throw new SoapFault('insertFile', 'insert file failed: Session-ID ('.$session_id.') is not valid');
      } else {
        throw new SoapFault('insertPost', 'insert file failed: User ('.$this->_sessionid_to_userid_array[$session_id].') is not allowed to add files to this blog ('.$blogId.').');
      }
    }
    return $uploadDir['url'].'/'.$file['name'];
  }

  /**
   * @param int $postId
   * @param int $blogId
   * @return boolean
   */
  public function getPostExists($session_id,$postId, $blogId) {
    if ( $this->_isSessionValid($session_id) ) {
      switch_to_blog($blogId);
      $post = get_post($postId);
      restore_current_blog();
      return is_object($post);
    } else {
      throw new SoapFault('getPostExists', 'get post exists failed: Session-ID ('.$session_id.') is not valid');
    }
  }

  /**
   * @return array
   */
  public function getSkins($session_id) {
    if ( $this->_isSessionValid($session_id) ) {
      return $this->_get_site_allowed_themes();
    } else {
      throw new SoapFault('getSkins', 'get skins failed: Session-ID ('.$session_id.') is not valid');
    }
  }

   private function _get_site_allowed_themes() {
      $themes = wp_get_themes(array('allowed' => 'site'));

      $response = array();

      foreach ($themes as $theme) {
          $themeName = $theme->get('Name');

          $response[$themeName] = array(
              'template' => $theme->get_template(),
              'screenshot' => $theme->get_screenshot());
      }

      return $response;
   }

  /**
   * @param string $name
   * @param string $value
   * @param int $blogId
   */
  public function insertOption($session_id, $name, $value, $blogId) {
    if ( $this->_isSessionValid($session_id)
         and $this->_isUserAllowed($session_id,CS_ROLE_MOD,$blogId)
       ) {
      switch_to_blog($blogId);
      add_option($name, $value);
      restore_current_blog();
    } else {
      if ( !$this->_isSessionValid($session_id) ) {
        throw new SoapFault('insertOption', 'insert option failed: Session-ID ('.$session_id.') is not valid');
      } else {
        throw new SoapFault('insertOption', 'insert option failed: User ('.$this->_sessionid_to_userid_array[$session_id].') is not allowed to add options to this blog ('.$blogId.').');
      }
    }
  }

  /**
   * @param string $name
   * @param string $value
   * @param int $blogId
   */
  public function updateOption($session_id,$name, $value, $blogId) {
    if ( $this->_isSessionValid($session_id)
         and $this->_isUserAllowed($session_id,CS_ROLE_MOD,$blogId)
       ) {
      switch_to_blog($blogId);
      update_option($name, $value);
      restore_current_blog();
    } else {
      if ( !$this->_isSessionValid($session_id) ) {
        throw new SoapFault('updateOption', 'update option failed: Session-ID ('.$session_id.') is not valid');
      } else {
        throw new SoapFault('updateOption', 'update option failed: User ('.$this->_sessionid_to_userid_array[$session_id].') is not allowed to update options to this blog ('.$blogId.').');
      }
    }
  }

  /**
   * @param string $name
   * @param int $blogId
   *
   * @return string Option value
   */
  public function getOption($session_id, $name, $blogId) {
    if ( $this->_isSessionValid($session_id)
         and $this->_isUserAllowed($session_id,CS_ROLE_MOD,$blogId)
       ) {
      switch_to_blog($blogId);
      $value = get_option($name);
      restore_current_blog();
      return $value;
    } else {
      if ( !$this->_isSessionValid($session_id) ) {
        throw new SoapFault('getOption', 'get option failed: Session-ID ('.$session_id.') is not valid');
      } else {
        throw new SoapFault('getOption', 'get option failed: User ('.$this->_sessionid_to_userid_array[$session_id].') is not allowed to get option from this blog ('.$blogId.').');
      }
    }
  }

   public function getUserRole ($session_id, $blog_id, $user_id) {
      $retour = '';
      if ( $this->_isSessionValid($session_id)
           and $this->_isUserInCommSy($session_id,$this->_blogID2CommSyID($blog_id))
         ) {
         $role_array = $this->_getRoleInBlog($user_id, $blog_id);
         if ( !empty($role_array)
              and !empty($role_array[0])
            ) {
            $retour = $role_array[0];
         } else {
            switch_to_blog($blog_id);
            $retour = get_option('default_role');
            restore_current_blog();
         }
      } else {
         if ( !$this->_isSessionValid($session_id) ) {
            throw new SoapFault('getUserRole', 'get user role failed: Session-ID ('.$session_id.') is not valid');
         } else {
            throw new SoapFault('getUserRole', 'get user role failed: User ('.$this->_sessionid_to_userid_array[$session_id].') is not allowed to get an user role from this blog ('.$blog_id.').');
         }
      }
      return $retour;
   }

   private function _blogID2CommSyID ($blog_id) {
      $retour = 0;
      if ( !empty($blog_id) ) {
         switch_to_blog($blog_id);
         $retour = get_option('commsy_context_id');
         restore_current_blog();
      }
      return $retour;
   }
}

function commsy_soap() {

  ini_set('soap.wsdl_cache_enabled', 0);
  ini_set('include_path', ini_get('include_path').':'.dirname(__FILE__));
  if(isset($_GET['wsdl'])) {
    include_once('commsyblog.wsdl.php');
    exit;


  } else if(isset($_SERVER['HTTP_SOAPACTION'])) {
    #$soap = new SoapServer('http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]."?wsdl");
    # wsdl mode does not work, if server is behind a proxy, php 5.3.2 (30.12.2010 ij)
    $soap = new SoapServer(null, array('uri' => 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']));
    $soap->setClass('commsy_blog');
    $soap->handle();
    exit;

  }
}
add_action('wp_loaded', 'commsy_soap');

#####################################################
# misc functions
#####################################################
function commsy_XMLToArray($xml) {
   if ($xml instanceof SimpleXMLElement) {
      $children = $xml->children();
      $return = null;
   } else {
      $xml = simplexml_load_string($xml);
      if ( $xml instanceof SimpleXMLElement ) {
         $children = $xml->children();
      } else {
         $children = array();
      }
      $return = null;
   }
   foreach ($children as $element => $value) {
      if ( strstr($element,'XML_') ) {
         $element_begin = substr($element,0,4);
         if ($element_begin = 'XML_') {
            $element = substr($element,4);
         }
      }
      if ($value instanceof SimpleXMLElement) {
         $counter = 0;
         foreach ($value->children() as $children) {
            $counter++;
         }

         if ($counter > 0) {
            $return[$element] = XMLToArray($value);
         } else {

            if ( !empty($element) and $element == 'extras') {
               $value = unserialize((string)$value);
            } elseif ( isset($value) ) {
               // convert > and < to their html entities (gt; and &lt;)
               if ( strstr($value,"%CS_AND;") ) {
                  $value = str_replace("%CS_AND;", "&", $value);
               }
               if ( strstr($value,"%CS_LT;") ) {
                  $value = str_replace("%CS_LT;", "<", $value);
               }
               if ( strstr($value,"%CS_GT;") ) {
                  $value = str_replace("%CS_GT;", ">", $value);
               }
            } else {
               $value = '';
            }
            if (!isset($return[$element])) {
               if ( is_array($value) ) {
                  $return[$element] = $value;
               } else {
                  $return[$element] = (string)$value;
               }
            } else {
               if (!is_array($return[$element])) {
                  $return[$element] = array($return[$element], (string)$value);
               } else {
                  $return[$element][] = (string)$value;
               }
            }
         }
      }
   }
   if (is_array($return)) {
      return $return;
   } else {
      return false;
   }
}
?>