<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
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

$this->includeClass(INDEX_VIEW);
include_once('classes/cs_reader_manager.php');
include_once('functions/text_functions.php');

/**
 *  class for CommSy list-view: material
 */
class cs_material_index_view extends cs_index_view {

   /** array of ids in clipboard*/
   var $_clipboard_id_array=array();
   var $_selected_institution = NULL;
   var $_available_institutions = NULL;
   var $_selected_topic = NULL;
   var $_available_topics = NULL;
   var $_selected_group = NULL;
   var $_available_groups = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            environemt of the commsy
    * @param string  viewname               e.g. material_list
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function __construct ($params) {
      $this->cs_index_view($params);
      $this->setTitle($this->_translator->getMessage('MATERIAL_INDEX'));
      $this->_show_buzzword_box = true;
   }

   function setClipboardIDArray($cia) {
      $this->_clipboard_id_array = $cia;
   }

   function getClipboardIDArray() {
      return $this->_clipboard_id_array;
   }

   function _getAdditionalRestrictionBoxAsHTML($field_length=14.5){
      $html = '';
      return $html;
   }


   function getAdditionalRestrictionTextAsHTML(){
/***Activating Code***/
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();

      $activation_limit= $this->getActivationLimit();
      if ( $activation_limit == 2 ){
         $this->_additional_selects = true;
         $html_text ='<tr>'.LF;
         $html_text .='<td>'.LF;
         $html_text .= '<span class="infocolor">'.$this->_translator->getMessage('COMMON_ACTIVATION_RESTRICTION').': </span>';
         $html_text .='</td>'.LF;
         $html_text .='<td style="text-align:right;">'.LF;
         $html_text .= '<span>'.$this->_translator->getMessage('COMMON_SHOW_ONLY_ACTIVATED_ENTRIES').'</span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         $new_params['selactivatingstatus'] = 1;
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</td>'.LF;
         $html_text .='</tr>'.LF;
         $html .= $html_text;
      }
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withActivatingContent()){
         return $html;
      }else{
         return '';
      }
/*********************/
   }

   function _getAdditionalActionsAsHTML(){
      #$current_context = $this->_environment->getCurrentContextItem();
      #$current_user = $this->_environment->getCurrentUserItem();
      #$html  = '';
      #$params = $this->_environment->getCurrentParameterArray();
      #$params['mode']='print';
      #if ($current_context->withMaterialImportLink() ){
      #   if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
      #      $image = '<img src="images/commsyicons_msie6/22x22/import.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_IMS_IMPORT').' id="import_icon"/>';
      #   } else {
      #      $image = '<img src="images/commsyicons/22x22/import.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_IMS_IMPORT').'" id="import_icon"/>';
      #   }
      #   $html .= ahref_curl($this->_environment->getCurrentContextID(),
      #                      CS_MATERIAL_TYPE,
      #                      'ims_import',
      #                      '',
      #                      $image,
      #                      $this->_translator->getMessage('MATERIAL_IMS_IMPORT')).LF;
      #}
      #return $html;
   }

   function _getTableheadAsHTML($with_links=TRUE) {
      include_once('functions/misc_functions.php');
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;
      $picture ='';
      $html ='';
      $html .= '   <tr class="head">'.LF;
      $html .= '      <td style="width:65%;" class="head" colspan="2">';

      if ( $this->getSortKey() == 'title' ) {
         $params['sort'] = 'title_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'title_rev' ) {
         $params['sort'] = 'title';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'title';
         $picture ='&nbsp;';
      }
      if($with_links) {
         if ( empty($params['download'])
              or $params['download'] != 'zip'
            ) {
            $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                $params, $this->_translator->getMessage('MATERIAL_TITLE'), '', '', $this->getFragment(),'','','','class="head"');
         } else {
            $html .= $this->_translator->getMessage('MATERIAL_TITLE');
         }
         $html .= $picture;
      } else {
         $html.=  '<span class="index_link">'.$this->_translator->getMessage('MATERIAL_TITLE').'</span>';
         $html .= $picture;
      }


      $html .= '</td>'.LF;

      $html .= '      <td style="width:15%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'date' ) {
         $params['sort'] = 'date_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'date_rev' ) {
         $params['sort'] = 'date';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'date';
         $picture ='&nbsp;';
      }
      if($with_links) {
         if ( empty($params['download'])
              or $params['download'] != 'zip'
            ) {
            $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('COMMON_MODIFIED_AT'), '', '', $this->getFragment(),'','','','class="head"');
         } else {
            $html .= $this->_translator->getMessage('COMMON_MODIFIED_AT');
         }
         $html .= $picture;
      } else {
         $html .= '<span class="index_link">'.$this->_translator->getMessage('COMMON_MODIFIED_AT').'</span>';
         $html .= $picture;
      }
      $html .= '</td>'.LF;

      $html .= '      <td style="width:20%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'modificator' ) {
         $params['sort'] = 'modificator_rev';
         $picture = '&nbsp;<img src="' . getSortImage('up') . '" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'modificator_rev' ) {
         $params['sort'] = 'modificator';
         $picture = '&nbsp;<img src="' . getSortImage('down') . '" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'modificator';
         $picture ='&nbsp;';
      }
      if($with_links) {
         if ( empty($params['download'])
              or $params['download'] != 'zip'
            ) {
            $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                                $params, $this->_translator->getMessage('COMMON_MODIFIED_BY'), '', '', $this->getFragment(),'','','','class="head"');
         } else {
            $html .= $this->_translator->getMessage('COMMON_MODIFIED_BY');
         }
         $html .= $picture;
      } else {
         $html .= '<span class="index_link">'.$this->_translator->getMessage('COMMON_MODIFIED_BY').'</span>';
         $html .= $picture;
      }
      $html .= '</td>'.LF;

      $html .= '   </tr>'.LF;
      return $html;
   }

   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      $html .= '<td colspan="4" style="padding:0px; margin:0px;">'.LF;
      $html .= '<table style="width:100%; margin:0px; padding:0px; border-collapse:collapse;" summary="Layout">'.LF;
      $html .= '<tr class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" ><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="foot_left" style="vertical-align:middle;">'.LF;
         $html .= '<span class="select_link">[</span>';
         $params = $this->_environment->getCurrentParameterArray();
         $params['select'] = 'all';
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_ALL_ENTRIES'), '', '', $this->getFragment(),'','','','class="select_link"');
         $html .= '<span class="select_link">]</span>'.LF;

         $html .= $this->_getViewActionsAsHTML();
      }
      $html .= '</td>'.LF;
      $html .= '<td class="foot_right"  style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
      if ( $this->hasCheckboxes() ) {
         if (count($this->getCheckedIDs())=='1'){
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED_ONE',count($this->getCheckedIDs()));
         }else{
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED',count($this->getCheckedIDs()));
         }
      }
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      $html .= '   </table>'.LF;
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;

   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item, $pos=0, $with_links=TRUE) {
      $html = '';
      $shown_entry_number = $pos + $this->_count_headlines;
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      if ($this->_clipboard_mode){
         $sort_criteria = $item->getContextID();
         if ( $sort_criteria != $this->_last_sort_criteria ) {
            $this->_last_sort_criteria = $sort_criteria;
            $this->_count_headlines ++;
            $room_manager = $this->_environment->getProjectManager();
            $sort_room = $room_manager->getItem($sort_criteria);
            $html .= '                     <tr class="list"><td '.$style.' width="100%" style="font-weight:bold;" colspan="5">'."\n";
            if ( empty($sort_room) ) {
               $community_manager = $this->_environment->getCommunityManager();
               $sort_community = $community_manager->getItem($sort_criteria);
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM').'&nbsp;'.$this->_translator->getMessage('COMMON_COMMUNITY_ROOM_TITLE').'&nbsp;"'.$sort_community->getTitle().'"'."\n";
            } elseif( $sort_room->isPrivateRoom() ){
               $user = $this->_environment->getCurrentUserItem();
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PRIVATEROOM').'&nbsp;"'.$user->getFullname().'"'.LF;
            } elseif( $sort_room->isCommunityRoom() ){
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_COMMUNITYROOM').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
            }elseif( $sort_room->isGroupRoom() ){
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_GROUPROOM').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
            }else {
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PROJECTROOM').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
            }
            $html .= '                     </td></tr>'."\n";
            if ( $style=='class="odd"' ){
               $style='class="even"';
            }else{
               $style='class="odd"';
            }
         }
      }
      $html  .= '   <tr class="list">'.LF;
      $checked_ids = $this->getCheckedIDs();
      $dontedit_ids = $this->getDontEditIDs();
      $key = $item->getItemID();
      if ( !(isset($_GET['mode']) and $_GET['mode']=='print')
           or ( !empty($download)
                and $download == 'zip'
              )
         ) {
         $html .= '      <td '.$style.' style="vertical-align:middle;" width="2%">'.LF;
         if ( empty($download)
              or $download != 'zip'
            ) {
            $html .= '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" type="checkbox" onClick="quark(this)" name="attach['.$key.']" value="1"';

            $user = $this->_environment->getCurrentUser();
            if($item->isNotActivated() and !($item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
               $html .= ' disabled="disabled"'.LF;
            }elseif ( isset($checked_ids)
                 and !empty($checked_ids)
                 and in_array($key, $checked_ids)
               ) {
               $html .= ' checked="checked"'.LF;
               if ( in_array($key, $dontedit_ids) ) {
                  $html .= ' disabled="disabled"'.LF;
               }
            }

            $html .= '/>'.LF;
            $html .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
         }
         $html .= '      </td>'.LF;

         if ($item->isNotActivated()){
            $title = $this->_getItemTitle($item);
            $title = $this->_compareWithSearchText($title);
            $user = $this->_environment->getCurrentUser();
            if ( $item->getCreatorID() == $user->getItemID()
                 or $user->isModerator()
               ) {
               $params = array();
               $params['iid'] = $item->getItemID();
               $title = ahref_curl( $this->_environment->getCurrentContextID(),
                                  CS_MATERIAL_TYPE,
                                  'detail',
                                  $params,
                                  $title,
                                  '','', '', '', '', '', '', '',
                                  CS_MATERIAL_TYPE.$item->getItemID());
               unset($params);
            }
            $activating_date = $item->getActivatingDate();
            if (strstr($activating_date,'9999-00-00')){
               $title .= BR.$this->_translator->getMessage('COMMON_NOT_ACTIVATED');
            }else{
               $title .= BR.$this->_translator->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
            }
            $title = '<span class="disabled">'.$title.'</span>';
            $html .= '      <td '.$style.'>'.$title.LF;
         }else{
             if($with_links) {
                $html .= '      <td '.$style.'>'.$this->_getItemTitle($item).LF;
             } else {
                $title = $this->_text_as_html_short($this->_getItemTitle($item));
                $html .= '      <td '.$style.'>'.$title.LF;
             }
         }

      }else{
            if($with_links) {
               $html .= '      <td colspan="2" '.$style.'>'.$this->_getItemTitle($item).LF;
            } else {
               $title = $this->_text_as_html_short($this->_getItemTitle($item));
               $html .= '      <td colspan="2" '.$style.'>'.$title.LF;
            }
      }
      if ( !$item->isNotActivated()
           or $item->getCreatorID() == $user->getItemID()
           or $user->isModerator()
         ) {
         $html .= '          '.$this->_getItemFiles($item, $with_links);
      }
      $html .= '</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemModificationDate($item).'</td>'.LF;

      ########################
      # EDU HACK - BEGIN
      ########################
      if ( $this->_environment->inConfigArray('c_material_auhtor_array',$this->_environment->getCurrentContextID()) ) {
         $text = $this->_getItemAuthor($item);
         if ( empty($text) ) {
            $text = $this->_getItemModificator($item);
         }
         $html .= '      <td '.$style.' style="font-size:8pt;">'.$text.'</td>'.LF;
      } else {
      ########################
      # EDU HACK - END
      ########################

         $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemModificator($item).'</td>'.LF;

      ########################
      # EDU HACK - BEGIN
      ########################
      }
      ########################
      # EDU HACK - END
      ########################

      $html .= '   </tr>'.LF;

      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getItemTitle($item){
      $title_text = $item->getTitle();
      $title_text = $this->_compareWithSearchText($title_text);
      $user = $this->_environment->getCurrentUser();
      $author_text = $this->_getItemAuthor($item);
      $year_text = $this->_getItemPublishingDate($item);
      $bib_kind = $item->getBibKind() ? $item->getBibKind() : 'none';
      if ( ( $item->isNotActivated()
             and $item->getCreatorID() != $user->getItemID()
             and !$user->isModerator()
           )
           or ( !$this->_environment->inProjectRoom()
                and !$item->isPublished()
                and !$user->isUser()
              )
         ) {
         if (!empty($author_text) and $bib_kind !='none'){
            if (!empty($year_text)){
                $year_text = ', '.$year_text;
            }else{
                $year_text = '';
            }
            $title = '<span class="disabled">'.$title_text.'</span>'.'<span class="disabled" style="font-size:8pt;"> ('.$this->_getItemAuthor($item).$year_text.')'.'</span>';
         }else{
            $title = '<span class="disabled">'.$title_text.'</span>'.LF;
         }
      } else {
         $params = array();
         $params['iid'] = $item->getItemID();
         $title = ahref_curl( $this->_environment->getCurrentContextID(),
                              CS_MATERIAL_TYPE,
                              'detail',
                              $params,
                              $this->_text_as_html_short($title_text),
                              '','', '', '', '', '', '', '',
                              CS_MATERIAL_TYPE.$item->getItemID());
         unset($params);
         if (!empty($author_text) and $bib_kind !='none'){
            if (!empty($year_text)){
                $year_text = ', '.$year_text;
            }else{
                $year_text = '';
            }
            $title = $title.' <span style="font-size:8pt;">('.$this->_getItemAuthor($item).$year_text.')</span>';
         } elseif(!empty($author_text)) {
            $title = $title.' <span style="font-size:8pt;">('.$this->_getItemAuthor($item).')</span>';
         } else {
            $title = $title.LF;
         }
         if ( !$this->_environment->inPrivateRoom() and !$item->isNotActivated()) {
            $title .= $this->_getItemChangeStatus($item);
            $title .= $this->_getItemAnnotationChangeStatus($item);
         }

      }
      return $title;
   }

   /** get the publishing info of the item
    * this method returns the item publishing info in the right formatted style
    *
    * @return string publishing info
    *
    * @author CommSy Development Group
    */
   function _getItemPublishingInfo($item){
      $publishing_info = '';
      $user = $this->_environment->getCurrentUser();
      if (!$this->_environment->inProjectRoom() and !$item->isPublished() and !$user->isUser() ){
         $publishing_info = $this->_translator->getMessage('MATERIAL_NOT_PUBLISHED');
      }
      return $publishing_info;
   }


   /** get the author of the item
    * this method returns the item author in the right formatted style
    *
    * @return string author
    *
    * @author CommSy Development Group
    */
   function _getItemAuthor($item){
         $author = $item->getAuthor();
         $author = $this->_compareWithSearchText($author);
         return $this->_text_as_html_short($author);
   }

   /** get the publishing date of the item
    * this method returns the item publishing date in the right formatted style
    *
    * @return string publishing date
    */
   function _getItemPublishingDate($item){
      $publishing_date = $this->_compareWithSearchText($item->getPublishingDate());
      return $this->_text_as_html_short($publishing_date);
   }

   /** get the lable of the item
    * this method returns the item lable in the right formatted style
    *
    * @return string file lable
    */
   function _getItemLabel($item){
      $label = $item->getLabel();
      if (!empty($label)){
         $label = $this->_compareWithSearchText($label);
      }else{
         $label='';
      }
      return $label;
   }

   /** get the file list of the item
    * this method returns the item file list in the right formatted style
    *
    * @return string file list
    */
   function _getItemFiles($item, $with_links=true){
      $retour = '';
      $file_list='';
      $files = $item->getFileListWithFilesFromSections();
      $files->sortby('filename');
      $file = $files->getFirst();
      $user = $this->_environment->getCurrentUser();
      while ($file) {
         $url = $file->getUrl();
         $displayname = $file->getDisplayName();
         $filesize = $file->getFileSize();
         $fileicon = $file->getFileIcon();
         if ($with_links and $this->_environment->inProjectRoom() || (!$this->_environment->inProjectRoom() and ($item->isPublished() || $user->isUser())) ) {
            if ( isset($_GET['mode'])
                 and $_GET['mode']=='print'
                 and ( empty($_GET['download'])
                       or $_GET['download'] != 'zip'
                     )
               ) {
               $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
            } else {
               if ( mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'png')
                    or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpg')
                    or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'jpeg')
                    or mb_stristr(mb_strtolower($file->getFilename(), 'UTF-8'),'gif')
                  ) {
                  $this->_with_slimbox = true;
                  // jQuery
                  //$file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                  $file_list.='<a href="'.$url.'" rel="lightbox-gallery'.$item->getItemID().'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                  // jQuery
               }else{
                  $file_list.='<a href="'.$url.'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
               }
            }
         } else {
            $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
         }
         $file = $files->getNext();
      }
      return $retour.$file_list;
   }

   public function _getAdditionalViewActionsAsHTML () {
      $retour = '';
      $retour .= '   <option value="download">'.$this->_translator->getMessage('COMMON_LIST_ACTION_DOWNLOAD').'</option>'.LF;
      include_once('functions/misc_functions.php');
      $retour .= plugin_hook_output_all('getAdditionalViewActionsAsHTML',array('module' => CS_MATERIAL_TYPE),LF);
      return $retour;
   }

   function _getAdditionalDropDownEntries() {
      $action_array = array();
      $current_context = $this->_environment->getCurrentContextItem();

      // ims import
      if ( $current_context->isOpen()
           and $current_context->withMaterialImportLink()
         ) {
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image_import = 'images/commsyicons_msie6/22x22/import.gif';
         } else {
            $image_import = 'images/commsyicons/22x22/import.png';
         }
         $href_import = curl($this->_environment->getCurrentContextID(),
                            CS_MATERIAL_TYPE,
                            'ims_import',
                            '');
         $text_import = $this->_translator->getMessage('MATERIAL_IMS_IMPORT');
         if ( !empty($text_import)
              and !empty($image_import)
              and !empty($href_import)
            ) {
            $temp_array = array();
            $temp_array['dropdown_image']  = "new_icon";
            $temp_array['text']  = $text_import;
            $temp_array['image'] = $image_import;
            $temp_array['href']  = $href_import;
            $action_array[] = $temp_array;
            unset($temp_array);
         }
      }

      unset($current_context);
      return $action_array;
   }
}
?>