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

/** class for database connection to the database table "homepage"
 * this class implements a database manager for the table "homepage_page"
 */

class cs_wordpress_manager extends cs_manager {

  protected $CW = false;
  protected $wp_user = false;


  function cs_wordpress_manager($environment) {
    global $c_use_soap_for_wordpress, $c_wordpress_path_url;
    parent::cs_manager($environment);

    $this->wp_user = $this->_environment->getCurrentUser()->_getItemData();
    $this->CW = $this->getSoapClient();
  }



  /** create a wordpress - internal, do not use -> use method save
   * this method creates a wordpress
   *
   */
  function createWordpress ($item) {
//    $old_dir = getcwd();
//    global $c_wordpress_path_file, $c_wordpress_absolute_path_file;
//
//    // wordpress directory
//    $this->CW->mk_folder($c_wordpress_absolute_path_file.'/wp');
//
//
//    // make wp directories (portal/room)
//    if ($item->isPortal()){
//      $this->CW->mk_folder($c_wordpress_absolute_path_file.'/wp/'.$item->getContextID());
//    }else{
//      $this->CW->mk_folder($c_wordpress_absolute_path_file.'/wp/'.$item->getContextID());
//      $this->CW->mk_folder($c_wordpress_absolute_path_file.'/wp/'.$item->getContextID().'/'.$item->getItemID());
//    }
//
//    // copy default wp into folder
//    $this->CW->createWordpressFiles($item->getContextID(), $item->getItemID());
//
//    $wp_path = '/wp/'.$item->getContextID().'/'.$item->getItemID();
//    // name of the database
//    $db_name = 'commsy_wp_'.$item->getContextID().'_'.$item->getItemID();
//
//
//    // copy db default into new room db, if not exists commsy_wp_portalid_roomid
//    if(!$this->existWordpressDB($db_name)){
//      $this->_createWordpressDB ($db_name, $wp_path);
//    }
//
//    // set Title
//    $this->_setWordpressOption('blogname', $item->getWordpressTitle());
//    // set Description
//    $this->_setWordpressOption('blogdescription', $item->getWordpressDescription());
//    // set Comments
//    $this->_setWordpressOption('default_comment_status', ($item->getWordpressUseComments()==1)?'open':'closed');
//    $this->_setWordpressOption('comment_moderation', ($item->getWordpressUseCommentsModeration()==1)?'1':'');
//    // set theme
//    $this->_setWordpressOption('template', $item->getWordpressSkin());
//    $this->_setWordpressOption('stylesheet', $item->getWordpressSkin());
//    // set plugin calendar
//
//    $calendar = $item->getWordpressUseCalendar();
//    $tagcloud = $item->getWordpressUseTagCloud();
//    $plugins = $this->_getWordpressOption('active_plugins');
//    $pluginsArray = unserialize  ( $plugins );
//    if(!$plugins){
//      // insert
//      if($calendar=='1'){
//        $pluginsArray[] = 'calendar/calendar.php';
//      }
//      if($tagcloud=='1'){
//        $pluginsArray[] = 'nktagcloud/nktagcloud.php';
//      }
//      if(count($pluginsArray)>0){
//        $this->_setWordpressOption('active_plugins', serialize($pluginsArray), false);
//      }
//    }else{
//      // update
//      if($calendar=='1' && strstr($plugins, 'calendar')==false){
//        $pluginsArray[] = 'calendar/calendar.php';
//      }elseif($calendar!=='1'){
//        $key = array_search( 'calendar/calendar.php'  , $pluginsArray  );
//        unset($pluginsArray[$key]);
//      }
//      if($tagcloud=='1' && strstr($plugins, 'nktagcloud')==false){
//        $pluginsArray[] = 'nktagcloud/nktagcloud.php';
//      }elseif($tagcloud!=='1'){
//        $key = array_search( 'nktagcloud/nktagcloud.php'  , $pluginsArray  );
//        unset($pluginsArray[$key]);
//      }
//      $this->_setWordpressOption('active_plugins', serialize($pluginsArray), true);
//    }
//
//    // change commsyconfig
//    global $c_commsy_domain, $c_commsy_url_path;
//    $str = '$PATH_TO_COMMSY_SERVER = "'.$c_commsy_domain.$c_commsy_url_path.'";'.LF;
//    $str .= '$COMMSY_ROOM_ID = "'.$item->getItemID().'";'.LF;
//    $this->CW->mkCommsyConfig($wp_path, $str);
//
//    chdir($old_dir);
    try {
      $wpUser = array('name' => $this->wp_user['user_id'], 'password' => uniqid()); // random password
      $wpBlog = array('title' => $this->_environment->getCurrentContextItem()->getTitle(), 'path' => $this->_environment->getCurrentPortalID().'_'.$this->_environment->getCurrentContextID());
      $result = $this->CW->createBlog($wpUser, $wpBlog);
      $contextItem = $this->_environment->getCurrentContextItem();
      var_export($result);
      $contextItem->setWordpressId($result['blog_id']);
      $contextItem->save();
    } catch(Exception $e) {
      echo 'Es ist ein Fehler aufgetreten:'.$e->getMessage();
      echo $e->getTraceAsString();
      exit;
    }
  }

  function deleteWordpress ($item) {

    if (!$item->isPortal()) {
      $this->CW->deleteWordpress($item->getContextID(),$item->getItemID());
    }

  }


//  function _rmdir_rf($dirname) {
//    if ($dirHandle = opendir($dirname)) {
//      chdir($dirname);
//      while ($file = readdir($dirHandle)) {
//        if ($file == '.' || $file == '..') continue;
//        if (is_dir($file)) $this->_rmdir_rf($file);
//        else unlink($file);
//      }
//      chdir('..');
//      rmdir($dirname);
//      closedir($dirHandle);
//    }
//  }



  //------------------------------------------
  //------------- Materialexport -------------
  function exportItemToWordpress($current_item_id,$rubric) {
    global $c_wordpress_path_url;
    global $c_commsy_domain;
    global $c_commsy_url_path;
    global $c_single_entry_point;


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


    $post_content = $this->encodeUmlaute($description);
    $post_content = 'Author: '.$author.'<br />'.$this->encodeUrl($post_content);
    $post_title = $item->getTitle();

    $post_content_complete = '';

    // Dateien
    $file_list = $item->getFileList();
    if(!$file_list->isEmpty()) {
      $file_array = $file_list->to_array();
      $file_link_array = array();
      foreach ($file_array as $file) {
        $rel_path = '/wp/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wp-content/uploads/commsy/'.$current_item_id.'/';
        $new_filename  = $this->exportFileToWordpress($file, $current_item_id, $rel_path);
        $file_link_array[] = '<a href="'.$c_wordpress_path_url . $rel_path . $new_filename.'" title="'.$new_filename.'">'.$new_filename.'</a>' ;
      }
      $file_links = '<br />Dateien:<br /> '.implode(' | ', $file_link_array);
      $post_content .= $file_links;
    }
    // Abschnitte
    $sub_item_list = $item->getSectionList();
    $sub_item_descriptions = '';
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
            $rel_path = '/wp/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/wp-content/uploads/commsy/'.$current_item_id.'/';
            $new_filename  = $this->exportFileToWordpress($file, $current_item_id, $rel_path);
            $file_link_array[] = '<a href="'.$c_wordpress_path_url . $rel_path . $new_filename.'" title="'.$new_filename.'">'.$new_filename.'</a>';
          }
          $file_links = '<br />Dateien:<br /> '.implode(' | ', $file_link_array);
        }

        $sub_item_description_array[] = $description.$file_links;

      }

      $sub_item_links = 'Abschnitte: <br />'.implode('<br />', $sub_item_link_array);
      $sub_item_descriptions = '<hr />'.implode('<hr />', $sub_item_description_array);
      // attach each section to the item
      $post_content_complete .= $post_content.'<br />'.$sub_item_links.$sub_item_descriptions;
    }

    // Link zurueck ins CommSy
    $post_content_complete .= '<hr /><a href="' . $c_commsy_domain .  $c_commsy_url_path . '/'.$c_single_entry_point.'?cid=' . $this->_environment->getCurrentContextID() . '&mod='.$rubric.'&fct=detail&iid=' . $current_item_id . '" title="'.$item->getTitle().'">"' . $item->getTitle() . '" im CommSy</a>';

    $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
    $modifiers = $link_modifier_item_manager->getModifiersOfItem($item->getItemID());

    $wpLogin = $this->_environment->getCurrentUserItem()->getUserId();
    //$this->updateExportLists($rubric);
    // write posts


    $context = $this->_environment->getCurrentContextItem();
    $comment_status = $context->getWordpressUseComments();
//    include_once($c_wordpress_absolute_path_file.'/commsy/commsy_wordpress.php');
    $post = array(
//            'post_date'            => $item->getCreationDate(), // zeit ohne zeitverschiebung
//            'post_date_gmt'        => $item->getCreationDate(), // zeit ohne zeitverschiebung
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

    $wpPostId = $this->CW->insertPost($post, $wpLogin, $context->getWordpressId(), 'Material');
    $item->setExportToWordpress($wpPostId);
    $item->save();

  }

  function exportFileToWordpress($file, $current_item_id, $rel_path) {
    global $c_wordpress_absolute_path_file, $c_commsy_path_file;
    // copy file
    $new_filename = $this->encodeUrl($file->getDiskFileNameWithoutFolder());
    $new_filename = preg_replace('~cid([0-9]*)_~u', '', $new_filename);
    $new_filename = date('U').$new_filename;

    $path = $c_wordpress_absolute_path_file . $rel_path;
    if(!is_dir($path)) {
      $path_uploads = substr  ( $path , 0  , strrpos($path, 'uploads') ).'uploads';
      if(!is_dir($path_uploads)) {
        mkdir($path_uploads);
      }
      $path_commsy = substr  ( $path , 0  , strrpos($path, 'commsy') ).'commsy';
      if(!is_dir($path_commsy)) {
        mkdir($path_commsy);
      }
      mkdir($path);
    }
    copy($c_commsy_path_file . '/' . $file->getDiskFileName(), $path.$new_filename);
    return $new_filename;
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

  function existsItemToWordpress($current_item_id) {
    return $this->CW->getPostExists($current_item_id);
  }

  function getExportToWordpressLink($current_item_id) {
    global $c_wordpress_path_url;
    return '<a href="' . $c_wordpress_path_url . $this->_environment->getCurrentPortalID() . '_' . $this->_environment->getCurrentContextID() . '/?p='.$current_item_id.'">zum Artikel</a>';
  }

  function getSoapWsdlUrl() {
    global $c_wordpress_path_url;
    return $c_wordpress_path_url . '?wsdl';
  }

  function getSoapClient() {
    return new SoapClient($this->getSoapWsdlUrl(), array("trace" => 1, "exceptions" => 1));
  }



//  function recurse_copy_dir($src,$dst) {
//    $dir = opendir($src);
//    @mkdir($dst);
//    while(false !== ( $file = readdir($dir)) ) {
//      if (( $file != '.' ) && ( $file != '..' )) {
//        if ( is_dir($src . '/' . $file) ) {
//          $this->recurse_copy_dir($src . '/' . $file,$dst . '/' . $file);
//        }
//        else {
//          copy($src . '/' . $file,$dst . '/' . $file);
//        }
//      }
//    }
//    closedir($dir);
//  }



//  function existWordpressComplete($portalid, $roomid, $db_name) {
//    if(!$this->existWordpressFiles($portalid, $roomid)) return false;
//    return $this->existWordpressDB($db_name);
//  }
//
//  function existWordpressFiles($portalid, $roomid) {
//    return $this->CW->getFilesExists($portalid, $roomid);
//  }
//
//  function existWordpressDB($db_name) {
//    return $this->CW->getDBExists($db_name);
//  }

//  // create db, create all tables, create options
//  private function _createWordpressDB ($db_name, $wp_path) {
//    $this->CW->createDB($db_name);
//  }
//
//  private function _setWordpressOption($option_name, $option_value, $update=true) {
//    if($update==true) {
//      $this->CW->updateWPOptions($option_name, $option_value);
//    }else {
//      $this->CW->insertWPOptions($option_name, $option_value);
//    }
//  }
//  private function _getWordpressOption($option_name) {
//    return $this->CW->getWPOptions($option_name);
//  }

  // returns an array of default skins
  public function getSkins() {
    try {
      $skins = $this->CW->getSkins();
      return $skins;
    } catch (Exception $e) {
      echo $e->getMessage();
      exit;
    }
  }

}

?>
