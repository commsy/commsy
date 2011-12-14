<?php
	require_once('classes/controller/cs_detail_controller.php');

	class cs_material_detail_controller extends cs_detail_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'material_detail';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
			
			// assign rubric to template
			$this->assign('room', 'rubric', CS_MATERIAL_TYPE);
		}
		
		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		public function actionDetail() {
			// try to set the item
			$this->setItem();
			
			$this->setupInformation();
			
			$session = $this->_environment->getSessionItem();
			if(isset($_GET['export_to_wiki'])){
		         $wiki_manager = $this->_environment->getWikiManager();
		         //$wiki_manager->exportItemToWiki($current_item_iid,CS_MATERIAL_TYPE);
		         global $c_use_soap_for_wiki;
		         if(!$c_use_soap_for_wiki){
		            $wiki_manager->exportItemToWiki($current_item_iid,CS_MATERIAL_TYPE);
		         } else {
		            $wiki_manager->exportItemToWiki_soap($current_item_iid,CS_MATERIAL_TYPE);
		         }
		         $params = $this->_environment->getCurrentParameterArray();
		         unset($params['export_to_wiki']);
		         redirect($this->_environment->getCurrentContextID(),CS_MATERIAL_TYPE, 'detail', $params);
		      }
		
		      if(isset($_GET['remove_from_wiki'])){
		         $wiki_manager = $this->_environment->getWikiManager();
		         global $c_use_soap_for_wiki;
		         if($c_use_soap_for_wiki){
		            $wiki_manager->removeItemFromWiki_soap($current_item_iid,CS_MATERIAL_TYPE);
		         }
		         $params = $this->_environment->getCurrentParameterArray();
		         unset($params['remove_from_wiki']);
		         redirect($this->_environment->getCurrentContextID(),CS_MATERIAL_TYPE, 'detail', $params);
		      }
		
		      // Get clipboard
		      if ( $session->issetValue('material_clipboard') ) {
		         $clipboard_id_array = $session->getValue('material_clipboard');
		      } else {
		         $clipboard_id_array = array();
		      }
		
		      // Copy to clipboard
		      if ( isset($_GET['add_to_material_clipboard'])
		           and !in_array($current_item_id, $clipboard_id_array) ) {
		         $clipboard_id_array[] = $current_item_id;
		         $session->setValue('material_clipboard', $clipboard_id_array);
		      }
			
			$this->assign('detail', 'content', $this->getDetailContent());
		}
		
		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/
		
		protected function setBrowseIDs() {
			$session = $this->_environment->getSessionItem();
			
			if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_material_index_ids')) {
				$this->_browse_ids = array_values((array) $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_material_index_ids'));
			}
		}
		
		protected function getDetailContent() {
            $converter = $this->_environment->getTextConverter();
            
            // prepare description
            $description = $this->_item->getDescription();
			$description = $converter->cleanDataFromTextArea($description);
			$description = $converter->text_as_html_long($description);
			$description = $converter->showImages($description, $this->_item, true);	
			
			$return = array(
				'title'			=> $this->_item->getTitle(),
				'description'	=> $description,
				'creator'		=> $this->_item->getCreatorItem()->getFullName(),
				'creation_date'	=> getDateTimeInLang($this->_item->getCreationDate()),
				'assessments'	=> $this->getAssessmentInformation(),
				'bib'			=> $this->getBibliographic(),
				'sections'		=> $this->getSections()
				//'material'			=> $this->getMaterialContent()
			);
			
			return $return;
		}
		
		private function getBibliographic() {
			$return = array();
			
			// append return
			$return = array(
				
			);
			
			return $return;
		}
		
/*
 * 
 * 
// Files
      $files = $this->_getFilesForFormalData($item);
      if ( !empty($files) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('MATERIAL_FILES');
         $temp_array[] = implode(BRLF, $files);
         $formal_data1[] = $temp_array;
      }

      // World-public status
      $current_context = $this->_environment->getCurrentContextItem();
      if ( $current_context->isCommunityRoom() and $current_context->isOpenForGuests() ) {
         $temp_array = array();
         $world_public = $item->getWorldPublic();
         if ( $world_public == 0 ) {
            $public_info = $this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_STATUS_0');
         } elseif ( $world_public == 1 ) {
            $public_info = $this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_STATUS_1');
         } elseif ( $world_public == 2 ) {
            $public_info = $this->_translator->getMessage('MATERIAL_WORLD_PUBLISH_STATUS_2');
         }
         $temp_array[0] = $this->_translator->getMessage('MATERIAL_WORLD_PUBLISH');
         $temp_array[1] = $public_info;
         $formal_data1[] = $temp_array;
      }
      $version_mode = 'long';
      $iid = 0;
      $params = $this->_environment->getCurrentParameterArray();
      if (isset($params['iid'])){
         $iid = $params['iid'];
      }
      $params = array();
      $params = array();
      $params = $this->_environment->getCurrentParameterArray();
      $show_versions = 'false';
      if (isset($params[$iid.'version_mode']) and $params[$iid.'version_mode']=='long'){
          $show_versions = 'true';
      }
      $params[$iid.'version_mode']='long';

      // Versions
      $versions = array();
      if ( !$this->_version_list->isEmpty() ) {
         $version = $this->_version_list->getFirst();
         if ( $version->getVersionID() == $this->_item->getVersionID() ) {
            $title = '&nbsp;&nbsp;'.$this->_translator->getMessage('MATERIAL_CURRENT_VERSION_DATE').' '.getDateTimeInLang($version->getModificationDate());
         } else {
           $params = array();
           $params[$iid.'version_mode'] = 'long';
           $params['iid'] = $version->getItemID();
           $title = '&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(), 'material', 'detail', $params,$this->_translator->getMessage('MATERIAL_CURRENT_VERSION_DATE').' '.getDateTimeInLang($version->getModificationDate()));
           unset($params);
         }
         $version = $this->_version_list->getNext();
         $current_user = $this->_environment->getCurrentUserItem();
         $is_user = $current_user->isUser();
         while ( $version ) {
            if ( !$with_links
                 or ( !$is_user
                      and $this->_environment->inCommunityRoom()
                      and !$version->isWorldPublic()
                    )
                 or $item->getVersionID() == $version->getVersionID()
               ) {
               $versions[] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$this->_translator->getMessage('MATERIAL_VERSION_DATE').' '.getDateTimeInLang($version->getModificationDate());
            } else {
               $params = array();
               $params[$iid.'version_mode'] = 'long';
               $params['iid'] = $version->getItemID();
               $params['version_id'] = $version->getVersionID();
               $versions[] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(), 'material', 'detail', $params,$this->_translator->getMessage('MATERIAL_VERSION_DATE').' '.getDateTimeInLang($version->getModificationDate()));
               unset($params);
            }
            $version = $this->_version_list->getNext();
         }
         $count = $this->_version_list->getCount();
         if ( !empty($versions) and $count > 1 ) {
            $temp_array = array();
            $temp_array[] = $this->_translator->getMessage('MATERIAL_VERSION');
            $html_string ='&nbsp;<img id="toggle'.$item->getItemID().$item->getVersionID().'" src="images/more.gif"/>';
            $html_string .= $title;
            $html_string .= '<div id="creator_information'.$item->getItemID().$item->getVersionID().'">'.LF;
            $html_string .= '<div class="creator_information_panel">     '.LF;
            $html_string .= '<div>'.LF;
            if ($show_versions == 'true'){
               $html_script ='<script type="text/javascript">initCreatorInformations("'.$item->getItemID().$item->getVersionID().'",true)</script>';
            }else{
               $html_script ='<script type="text/javascript">initCreatorInformations("'.$item->getItemID().$item->getVersionID().'",false)</script>';
            }
            if($with_links) {
               $html_string .= implode(BRLF, $versions);
            } else {
               $version_count = count ($versions);
               $html_string .= "$version_count. ".$versions[0];
            }
            $html_string .= '</div>'.LF;
            $html_string .= '</div>'.LF;
            $html_string .= '</div>'.LF;
            $temp_array[] = $html_string;
            $formal_data1[] = $temp_array;
         }
      }
      if ( !empty($formal_data1) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data1);
         if ( isset($html_script) and !empty($html_script) ) {
            $html .= $html_script;
         }
      }

      if ( $this->_section_list->isEmpty() ) {
         // Description
         $desc = $item->getDescription();
         if ( !empty($desc) ) {
            $temp_string = $this->_text_as_html_long($this->_compareWithSearchText($this->_cleanDataFromTextArea($desc)));
            $html .= $this->getScrollableContent($temp_string,$item,'',$with_links);
         }
      }

      $html  .= '<!-- END OF material ITEM DETAIL -->'.LF.LF;
      return $html;
 */
		
		private function getSections() {
			$return = array();
			$converter = $this->_environment->getTextConverter();
			
			$section_list = $this->_item->getSectionList();
			if(!$section_list->isEmpty()) {
				$section = $section_list->getFirst();
				
				while($section) {
					/*
					// files
            $fileicons = $this->_getItemFiles( $section,true);
            if ( !empty($fileicons) ) {
               $fileicons = '&nbsp;'.$fileicons;
            }

            $section_title = $this->_text_as_html_short($this->_compareWithSearchText($section->getTitle()));
            if( $with_links and !(isset($_GET['mode']) and $_GET['mode']=='print') ) {
               $section_title = '<a href="#anchor'.$section->getItemID().'">'.$section_title.'</a>'.$fileicons.LF;
            }
            $sections[] = $section_title;
            */		
					// prepare description
		            $description = $section->getDescription();
					$description = $converter->cleanDataFromTextArea($description);
					$description = $converter->text_as_html_long($description);
					$description = $converter->showImages($description, $section, true);
					
					/*
					 * 
					 // files
      $formal_data = array();
      $files = $this->_getFilesForFormalData($item);
      if ( !empty($files) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('MATERIAL_FILES');
         $temp_array[] = implode(BRLF, $files);
         $formal_data[] = $temp_array;
      }

      if ( !empty($formal_data) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data);
      }

      return $html;
					 */
					
					$return[] = array(
						'title'			=> $converter->text_as_html_short($section->getTitle()),
						'description'	=> $description
					);
					
					$section = $section_list->getNext();
				}
				
				/*

         $temp_array[] = $this->_translator->getMessage('MATERIAL_SECTIONS');
         $temp_array[] = implode(BRLF, $sections).'<br/><br/>';
         $formal_data1[] = $temp_array;
				 */
			}
			
			return $return;
		}
	}