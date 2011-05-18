<?PHP
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Manuel Gonzalez Vazquez, Johannes Schultze
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

include_once('functions/text_functions.php');

/** date functions are needed for method _newVersion()
 */
include_once('functions/date_functions.php');

/*
 * some spezific constants
 */
define('CS_STATUS_WP_ADMIN',  'administrator');
define('CS_STATUS_WP_EDITOR', 'editor');
define('CS_STATUS_WP_AUTHOR', 'author');
define('CS_STATUS_WP_CONTRI', 'contributor');

/** class for database connection to the database table "homepage"
 * this class implements a database manager for the table "homepage_page"
 */
class cs_wordpress_manager extends cs_manager {

  protected $CW = false;
  protected $wp_user = false;
  private $_wp_screenshot_array = array();
  private $_wp_skin_option_array = array();
  private $_with_session_caching = false;

  function cs_wordpress_manager($environment) {
    global $c_use_soap_for_wordpress, $c_wordpress_path_url;
    parent::cs_manager($environment);

    $this->wp_user = $this->_environment->getCurrentUser()->_getItemData();
    
    global $c_wordpress;
    if($c_wordpress){
       $this->CW = $this->getSoapClient();
    }
  }



  /** create a wordpress - internal, do not use -> use method save
   * this method creates a wordpress
   *
   */
  function createWordpress ($item) {
    try {
      $contextItem = $this->_environment->getCurrentContextItem();

      $wordpressId = $contextItem->getWordpressId();
      if($wordpressId == 0) {
        $wpUser = $this->_getCurrentAuthItem();
        $wpBlog = array('title' => $this->_environment->getCurrentContextItem()->getTitle(),
                        'path' => $this->_environment->getCurrentPortalID().'_'.$this->_environment->getCurrentContextID(),
                        'cid' => $this->_environment->getCurrentContextID(),
                        'pid' => $this->_environment->getCurrentPortalID()
                       );
        $result = $this->CW->createBlog($this->_environment->getSessionID(),$wpUser, $wpBlog);
        $contextItem->setWordpressId($result['blog_id']);
        $contextItem->save();

        // set timezone
        $this->_setWordpressOption('timezone_string', date_default_timezone_get());
      }

      // set commsy context id
      $this->_setWordpressOption('commsy_context_id', $item->getItemID());

////    // set Title
      $this->_setWordpressOption('blogname', $item->getWordpressTitle());
//    // set Description
      $this->_setWordpressOption('blogdescription', $item->getWordpressDescription());
      // set default role
      $this->_setWordpressOption('default_role', $item->getWordpressMemberRole());
//    // set Comments
      $this->_setWordpressOption('default_comment_status', ($item->getWordpressUseComments()==1)?'open':'closed');
      $this->_setWordpressOption('comment_moderation', ($item->getWordpressUseCommentsModeration()==1)?'1':'');
//    // set theme
      $this->_setWordpressOption('template', $item->getWordpressSkin());
      $this->_setWordpressOption('stylesheet', $item->getWordpressSkin());
//    // set plugin calendar
//
      $calendar = $item->getWordpressUseCalendar();
      $tagcloud = $item->getWordpressUseTagCloud();
      $plugins = $this->_getWordpressOption('active_plugins');
      $pluginsArray = @unserialize  ( $plugins );
      if(!$pluginsArray) $pluginsArray = array();
      if(!$plugins) {
        // insert
        if($calendar=='1') {
          $pluginsArray[] = 'calendar/calendar.php';
        }
        if($tagcloud=='1') {
          $pluginsArray[] = 'nktagcloud/nktagcloud.php';
        }
        if(count($pluginsArray)>0) {
          $this->_setWordpressOption('active_plugins', $pluginsArray, false);
        }
      }else {
        // update
        if($calendar=='1' && strstr($plugins, 'calendar')==false) {
          $pluginsArray[] = 'calendar/calendar.php';
        }elseif($calendar!=='1') {
          $key = array_search( 'calendar/calendar.php'  , $pluginsArray  );
          unset($pluginsArray[$key]);
        }
        if($tagcloud=='1' && strstr($plugins, 'nktagcloud')==false) {
          $pluginsArray[] = 'nktagcloud/nktagcloud.php';
        }elseif($tagcloud!=='1') {
          $key = array_search( 'nktagcloud/nktagcloud.php'  , $pluginsArray  );
          unset($pluginsArray[$key]);
        }
        $this->_setWordpressOption('active_plugins', $pluginsArray, true);
      }
    } catch(Exception $e) {
      return new SoapFault('createWordpress', $e->getMessage());
    }
    return true;
  }

  function deleteWordpress ($wordpress_id) {
    $retour = $this->CW->deleteBlog($this->_environment->getSessionID(),$wordpress_id);
    if ( is_soap_fault($retour) ) {
       $retour = false;
    }
    return $retour;
  }

  //------------------------------------------
  //------------- Materialexport -------------
  function exportItemToWordpress($current_item_id,$rubric) {
    global $c_wordpress_path_url;
    global $c_commsy_domain;
    global $c_commsy_url_path;
    global $c_single_entry_point;
    global $class_factory;

    $wpUser = $this->_getCurrentAuthItem();
    
    $context = $this->_environment->getCurrentContextItem();
    $wordpressId = $context->getWordpressId();
    $comment_status = $context->getWordpressUseComments();
    #$wordpress_post_id = '';
    
    // Material Item
    $material_manager = $this->_environment->getMaterialManager();
    $material_version_list = $material_manager->getVersionList($current_item_id);
    $item = $material_version_list->getFirst();
    
    // Informationen
    $author = $item->getAuthor();
    $description = $item->getDescription();
    if (empty($author)) {
      $author = $item->getModificatorItem()->getFullName();
    }

    // Dateien
    $file_links = '';
    $file_list = $item->getFileList();
    if(!$file_list->isEmpty()) {
      $file_array = $file_list->to_array();
      $file_link_array = array();
      $file_link_array_images = array();
      foreach ($file_array as $file) {
//        array($file->getUrl(), $file->getMime());
//        $rel_path = '/wp/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wp-content/uploads/commsy/'.$current_item_id.'/';
      	
        $fileUrl  = $this->CW->insertFile($this->_environment->getSessionID(),array('name' => $file->getDisplayName(), 'data' => base64_encode(file_get_contents($file->getDiskFileName()))), $wordpressId);//$this->exportFileToWordpress($file, $current_item_id, $rel_path);
        if ( !is_soap_fault($fileUrl) ) {
//        $fileUrl  = 'http://'.$_SERVER['HTTP_HOST'].'/'.$file->getUrl();//$this->exportFileToWordpress($file, $current_item_id, $rel_path);
          $file_link_array[] = '<a href="'.$fileUrl.'" title="'.$file->getDisplayName().'">'.$file->getDisplayName().'</a>' ;
          $file_link_array_images[$file->getDisplayName()] = $fileUrl;
          
          // Add files to Wordpress media-library
          $file_post = array(
            'post_content'         =>'',
            'post_content_filtered'=>'',
            'post_title'           =>mysql_escape_string($file->getDisplayName()),
            'post_excerpt'         =>'',
            'post_status'          =>'inherit',
            'post_type'            =>'attachment',
            'comment_status'       =>(($comment_status=='1')?'open':'closed'), // aus einstellungen übernehmen
            'ping_status'          =>'open',
            'post_password'        =>'',
            'post_name'            =>mysql_escape_string($file->getDisplayName()),
            'to_ping'              =>'',
            'pinged'               =>'',
            'post_modified'        =>$item->getModificationDate(),
            'post_modified_gmt'    =>$item->getModificationDate(),
            'post_parent'          =>'0',  // wenn kein parent
            'menu_order'           =>'0',
            'guid'                 =>$fileUrl,
      	   'post_mime_type'		  =>mysql_escape_string($file->getMime()));
          
          
          $wordpress_post_id = $file->getWordpressPostId();
          $wpPostId = $this->CW->insertPost($this->_environment->getSessionID(),$file_post, $wpUser, $wordpressId, 'Material', $wordpress_post_id);
          if($wordpress_post_id == ''){
             $file->setWordpressPostId($wpPostId);
             $file->update();
          }
          
        }
      }
      if ( !empty($file_link_array) ) {
        $file_links = '<br />Dateien:<br /> '.implode(' | ', $file_link_array);
        #$post_content .= $file_links;
        #unset($file_links);
      }
    }
    
    $params = array();
    $params['environment'] = $this->_environment;
    $wordpress_view = $class_factory->getClass(WORDPRESS_VIEW,$params);
    $wordpress_view->setItem($item);
    $description = $wordpress_view->formatForWordpress($description, $file_link_array_images);
    
    $post_content = $this->encodeUmlaute($description);
    $post_content = 'Author: '.$author.'<br />'.$this->encodeUrl($post_content);
    $post_title = $item->getTitle();

    $post_content_complete = '';
    
    $post_content .= $file_links;
    
    // Abschnitte
    $sub_item_list = $item->getSectionList();
    $sub_item_descriptions = '';
    $post_content_complete .= $post_content;
    if(!$sub_item_list->isEmpty()) {
      $size = $sub_item_list->getCount();
      $index_start = 1;
      $sub_item_link_array = array();
      $sub_item_description_array = array();
      for ($index = $index_start; $index <= $size; $index++) {
        $sub_item = $sub_item_list->get($index);

        $sub_item_link_array[] = '<a href="#section'.$index.'" title="' . $sub_item->getTitle() . '">' . $sub_item->getTitle() . '</a>';
        // Abschnitt fuer Wordpress vorbereiten
        $description = $sub_item->getDescription();
        $params = array();
        $description = $this->encodeUmlaute($description);
        $description = $this->encodeUrl($description);
        $description = '<a name="section'.$index.'"></a>'.$description;

        // Dateien (Abschnitte)
        $file_list = $sub_item->getFileList();
        if(!$file_list->isEmpty()) {
          $file_array = $file_list->to_array();
          $file_link_array = array();
          foreach ($file_array as $file) {
//            $rel_path = '/wp/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wp-content/uploads/commsy/'.$current_item_id.'/';
//            $new_filename  = $this->exportFileToWordpress($file, $current_item_id, $rel_path);
            $fileUrl  = $this->CW->insertFile($this->_environment->getSessionID(),array('name' => $file->getDisplayName(), 'data' => base64_encode(file_get_contents($file->getDiskFileName()))), $wordpressId);
            //$this->exportFileToWordpress($file, $current_item_id, $rel_path);
            if ( !is_soap_fault($fileUrl) ) {
              $file_link_array[] = '<a href="'.$fileUrl.'" title="'.$file->getDisplayName().'">'.$file->getDisplayName().'</a>' ;
              //$file_link_array[] = '<a href="'.$c_wordpress_path_url . $rel_path . $new_filename.'" title="'.$new_filename.'">'.$new_filename.'</a>';
            }
          }
          $file_links = '';
          if ( !empty($file_link_array) ) {
            $file_links = '<br />Dateien:<br /> '.implode(' | ', $file_link_array);
          }
        }

        if ( !empty($file_links) ) {
          $sub_item_description_array[] = $description.$file_links;
        } else {
          $sub_item_description_array[] = $description;
        }
      }

      $sub_item_links = 'Abschnitte: <br />'.implode('<br />', $sub_item_link_array);
      $sub_item_descriptions = '<hr />'.implode('<hr />', $sub_item_description_array);
      // attach each section to the item
      $post_content_complete .= '<br />'.$sub_item_links.$sub_item_descriptions;
    }

    // Link zurueck ins CommSy
    $post_content_complete .= '<hr /><a href="' . $c_commsy_domain .  $c_commsy_url_path . '/'.$c_single_entry_point.'?cid=' . $this->_environment->getCurrentContextID() . '&mod='.$rubric.'&fct=detail&iid=' . $current_item_id . '" title="'.$item->getTitle().'">"' . $item->getTitle() . '" im CommSy</a>';

    $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
    $modifiers = $link_modifier_item_manager->getModifiersOfItem($item->getItemID());

//    $currentUser = $this->_environment->getCurrentUserItem();
//    $wpUser = array();
//    $wpUser['login'] = $currentUser->getUserId();
//    $wpUser['email'] = $currentUser->getEmail();
//
    #$wpUser = $this->_getCurrentAuthItem();
    //$this->updateExportLists($rubric);
    // write posts


    #$comment_status = $context->getWordpressUseComments();
//    include_once($c_wordpress_absolute_path_file.'/commsy/commsy_wordpress.php');

    // delete rn out of post_content
    if ( stristr($post_content_complete,'<!-- KFC TEXT') ) {
       $post_content_complete = str_replace("\r\n",'',$post_content_complete);
    } else {
       $post_content_complete = str_replace("\r\n",'<br/>',$post_content_complete);
    }

    $post = array(
            'post_content'         => mysql_escape_string($post_content_complete),
            'post_content_filtered'=> '',
            'post_title'           => mysql_escape_string($post_title),
            'post_excerpt'         => mysql_escape_string($post_content),
            'post_status'          =>'publish',
            'post_type'            =>'post',
            'comment_status'       =>(($comment_status=='1')?'open':'closed'), // aus einstellungen übernehmen
            'ping_status'          =>'open',
            'post_password'        =>'',
            'post_name'            => mysql_escape_string(str_replace(' ', '-',$post_title)),
            'to_ping'              =>'',
            'pinged'               =>'',
            'post_modified'        =>$item->getModificationDate(),
            'post_modified_gmt'    =>$item->getModificationDate(),
            'post_parent'          =>'0',  // wenn kein parent
            'menu_order'           =>'0',
            'guid'                 =>'');

    $wordpress_post_id = $item->getExportToWordpress();
    $wpPostId = $this->CW->insertPost($this->_environment->getSessionID(),$post, $wpUser, $wordpressId, 'Material', $wordpress_post_id);
    if ( !is_soap_fault($wpPostId) ) {
       $item->setExportToWordpress($wpPostId);
       $item->save();
    }
  }

  function encodeUmlaute($html) {
    $html = str_replace("ä", "&auml;", $html);
    $html = str_replace("Ä", "&Auml;", $html);
    $html = str_replace("ö", "&ouml;", $html);
    $html = str_replace("Ö", "&Ouml;", $html);
    $html = str_replace("ü", "&uuml;", $html);
    $html = str_replace("Ü", "&Uuml;", $html);
    $html = str_replace("ß", "&szlig;", $html);
    return $html;
  }

  function encodeUrl($html) {
    $html = str_replace("%E4", "ae", $html);
    $html = str_replace("%C4", "AE", $html);
    $html = str_replace("%F6", "oe", $html);
    $html = str_replace("%D6", "OE", $html);
    $html = str_replace("%FC", "ue", $html);
    $html = str_replace("%DC", "UE", $html);
    $html = str_replace("%DF", "ss", $html);
    return $html;
  }

  function encodeUrlToHtml($html) {
    $html = str_replace("%E4", "&auml;", $html);
    $html = str_replace("%C4", "&Auml;", $html);
    $html = str_replace("%F6", "&ouml;", $html);
    $html = str_replace("%D6", "&Ouml;", $html);
    $html = str_replace("%FC", "&uuml;", $html);
    $html = str_replace("%DC", "&Uuml;", $html);
    $html = str_replace("%DF", "&szlig;", $html);
    return $html;
  }

  function existsItemToWordpress($wordpress_post_id) {
    $contextItem = $this->_environment->getCurrentContextItem();
    $retour = $this->CW->getPostExists($this->_environment->getSessionID(),(int)$wordpress_post_id, $contextItem->getWordpressId());
    if ( is_soap_fault($retour) ) {
       $retour = false;
    }
    return $retour;
  }

  function getExportToWordpressLink($wordpress_post_id) {
    global $c_wordpress_path_url;
    return '<a target="_blank" href="' . $c_wordpress_path_url . $this->_environment->getCurrentPortalID() . '_' . $this->_environment->getCurrentContextID() . '/?p='.$wordpress_post_id.'&commsy_session_id='.$this->_environment->getSessionID().'">zum Artikel</a>';
  }

  function getSoapWsdlUrl() {
    global $c_wordpress_path_url;
    return $c_wordpress_path_url . '?wsdl';
  }

  function getSoapClient() {
    $options = array("trace" => 1, "exceptions" => 1);
    if ( $this->_environment->getConfiguration('c_proxy_ip') ) {
      $options['proxy_host'] = $this->_environment->getConfiguration('c_proxy_ip');
    }
    if ( $this->_environment->getConfiguration('c_proxy_port') ) {
      $options['proxy_port'] = $this->_environment->getConfiguration('c_proxy_port');
    }
    $retour = NULL;
    try {
       $retour = new SoapClient($this->getSoapWsdlUrl(), $options);
    } catch ( SoapFault $sf ) {
       include_once('functions/error_functions.php');
       trigger_error('SOAP Error: '.$sf->faultstring,E_USER_ERROR);
    }
    return $retour;
  }

  protected function _setWordpressOption($option_name, $option_value, $update=true) {
    // TBD: error handling
    $option_value = is_array($option_value) ? serialize($option_value) : $option_value;
    if($update==true) {
      $this->CW->updateOption($this->_environment->getSessionID(),$option_name, $option_value, $this->_environment->getCurrentContextItem()->getWordpressId());
    }else {
      $this->CW->insertOption($this->_environment->getSessionID(),$option_name, $option_value, $this->_environment->getCurrentContextItem()->getWordpressId());
    }
  }

  protected function _getWordpressOption($option_name) {
    return $this->CW->getOption($this->_environment->getSessionID(), $option_name, $this->_environment->getCurrentContextItem()->getWordpressId());
  }

  protected function _getCurrentAuthItem() {
    // get user data out of the current portal user object
    // and status from the current user in the current context
    $current_user_item = $this->_environment->getPortalUserItem();
    $result = array(
            'login' => $current_user_item->getUserID(),
            'email' => $current_user_item->getEmail(),
            'firstname'  => $current_user_item->getFirstName(),
            'lastname'  => $current_user_item->getLastName(),
            'commsy_id' => $this->_environment->getCurrentPortalID(),
            'display_name' => trim($current_user_item->getFirstName()).' '.trim($current_user_item->getLastName())
    );
    unset($current_user_item);

    // TBD: change to soap authentication at WP

    // for commsy internal accounts get md5-password
    $session_manager = $this->_environment->getSessionManager();
    $session_item = $session_manager->get($this->_environment->getSessionID());
    $auth_source_id = $session_item->getValue('auth_source');
    $auth_source_manager = $this->_environment->getAuthSourceManager();
    $auth_source_item = $auth_source_manager->getItem($auth_source_id);
    if ( $auth_source_item->isCommSyDefault() ) {
      $user_id = $session_item->getValue('user_id');
      $commsy_id = $session_item->getValue('commsy_id');
      $authentication = $this->_environment->getAuthenticationObject();
      $authManager = $authentication->getAuthManagerByAuthSourceItem($auth_source_item);
      $authManager->setContextID($commsy_id);
      $auth_item = $authManager->getItem($user_id);
      $result['password'] = $auth_item->getPasswordMD5();
      unset($auth_item);
      unset($authManager);
      unset($authentication);
    } else {
      // dummy password for external accounts
      include_once('functions/date_functions.php');
      $result['password'] = md5(getCurrentDateTimeInMySQL().rand(1,999).$this->_environment->getConfiguration('c_security_key'));
    }
    unset($auth_source_manager);
    unset($auth_source_item);
    return $result;
  }

   // returns an array of default skins
   public function getSkins () {
      $retour = '';
      if ( !empty($this->_wp_skin_option_array) ) {
         $retour = $this->_wp_skin_option_array;
      } else {
         try {
            $skins = $this->CW->getSkins($this->_environment->getSessionID());
            $skinOptions = array();
            if ( !empty($skins) ) {
               foreach($skins as $name => $skin) {
                  $skinOptions[$name] = $skin['Template'];
                  if ( !empty($skin['Screenshot']) ) {
                     $this->_wp_screenshot_array[$skin['Template']] = $skin['Screenshot'];
                  } else {
                     $this->_wp_screenshot_array[$skin['Template']] = 'screenshot.png';
                  }
               }
            }
            $this->_wp_skin_option_array = $skinOptions;
            $retour = $this->_wp_skin_option_array;
         } catch (Exception $e) {
            echo $e->getMessage();
            exit;
         }
      }
      return $retour;
   }

   public function getScreenshotFilenameForTheme ( $name ) {
      $retour = 'screenshot.png';
      if ( !empty($this->_wp_screenshot_array[$name]) ) {
         $retour = $this->_wp_screenshot_array[$name];
      }
      return $retour;
   }

  /** ask, if user is allowed to export an item to wordpress
   * this method returns a boolean, if an user is allowed to export an item
   *
   * @param int wordpress_id the id of the wordpress blog
   * @param string user_login the user_id of the user
   * @return bool retour true: user is allowed, false: user is not allowed
   */
   public function isUserAllowedToExportItem ( $wordpress_id, $user_login ) {
      $retour = false;
      if ( !empty($wordpress_id) ) {
         $session_item = $this->_environment->getSessionItem();
         if ( $this->_with_session_caching
              and $session_item->issetValue('wordpress_allowed_export_item_'.$wordpress_id)
            ) {
            $value = $session_item->getValue('wordpress_allowed_export_item_'.$wordpress_id);
            if ( $value == 1 ) {
               $retour = true;
            }
         } else {
            $role = $this->CW->getUserRole($session_item->getSessionID(),$wordpress_id,$user_login);
            if ( empty($role)
                 or is_soap_fault($role)
               ) {
               $current_context = $this->_environment->getCurrentContextItem();
               $role = $current_context->getWordpressMemberRole();
            }
            if ( !empty($role) ) {
               if ( $role == CS_STATUS_WP_ADMIN
                    or $role == CS_STATUS_WP_EDITOR
                    or $role == CS_STATUS_WP_AUTHOR
                    or $role == CS_STATUS_WP_CONTRI
                  ) {
                  $retour = true;
               }
               if ( $this->_with_session_caching ) {
                  $session_value = -1;
                  if ( $retour ) {
                     $session_value = 1;
                  }
                  $session_item->setValue('wordpress_allowed_export_item_'.$wordpress_id,$session_value);
               }
            }
         }
         unset($session_item);
      }
      return $retour;
   }

  /** ask, if user is allowed to configure the wordpress blog
   * this method returns a boolean, if an user is allowed to configure a wordpress blog
   *
   * @param int wordpress_id the id of the wordpress blog
   * @param string user_login the user_id of the user
   * @return bool retour true: user is allowed, false: user is not allowed
   */
   public function isUserAllowedToConfig ( $wordpress_id, $user_login ) {
      $retour = false;
      if ( !empty($wordpress_id) ) {
         $session_item = $this->_environment->getSessionItem();
         if ( $this->_with_session_caching
              and $session_item->issetValue('wordpress_allowed_config_'.$wordpress_id)
            ) {
            $value = $session_item->getValue('wordpress_allowed_config_'.$wordpress_id);
            if ( $value == 1 ) {
               $retour = true;
            }
         } else {
            $role = $this->CW->getUserRole($session_item->getSessionID(),$wordpress_id,$user_login);
            if ( empty($role)
                 or is_soap_fault($role)
               ) {
               $current_context = $this->_environment->getCurrentContextItem();
               $role = $current_context->getWordpressMemberRole();
               unset($current_context);
            }
            if ( empty($role) ) {
               $current_user_item = $this->_environment->getCurrentUserItem();
               if ( $current_user_item->isModerator() ) {
                  $role = CS_STATUS_WP_ADMIN;
               }
               unset($current_user_item);
            }
            if ( !empty($role) ) {
               if ( $role == CS_STATUS_WP_ADMIN ) {
                  $retour = true;
               }
               if ( $this->_with_session_caching ) {
                  $session_value = -1;
                  if ( $retour ) {
                     $session_value = 1;
                  }
                  $session_item->setValue('wordpress_allowed_config_'.$wordpress_id,$session_value);
               }
            }
         }
         unset($session_item);
      } else {
         $current_user_item = $this->_environment->getCurrentUserItem();
         if ( $current_user_item->isModerator() ) {
            $role = CS_STATUS_WP_ADMIN;
         }
         unset($current_user_item);
         if ( !empty($role) ) {
            if ( $role == CS_STATUS_WP_ADMIN ) {
               $retour = true;
            }
            if ( $this->_with_session_caching ) {
               $session_value = -1;
               if ( $retour ) {
                  $session_value = 1;
               }
               $session_item->setValue('wordpress_allowed_config_'.$wordpress_id,$session_value);
            }
         }
      }
      return $retour;
   }
}
?>