<?php
	require_once('classes/controller/cs_detail_controller.php');

	class cs_discussion_detail_controller extends cs_detail_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
			
			$this->_tpl_file = 'discussion_detail';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();
			
			// assign rubric to template
			$this->assign('room', 'rubric', CS_DISCUSSION_TYPE);
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
		         //$wiki_manager->exportItemToWiki($current_item_iid,CS_DISCUSSION_TYPE);
		         global $c_use_soap_for_wiki;
		         if(!$c_use_soap_for_wiki){
		            $wiki_manager->exportItemToWiki($current_item_iid,CS_DISCUSSION_TYPE);
		         } else {
		            $wiki_manager->exportItemToWiki_soap($current_item_iid,CS_DISCUSSION_TYPE);
		         }
		         $params = $this->_environment->getCurrentParameterArray();
		         unset($params['export_to_wiki']);
		         redirect($this->_environment->getCurrentContextID(),CS_DISCUSSION_TYPE, 'detail', $params);
		      }
		
		      if(isset($_GET['remove_from_wiki'])){
		         $wiki_manager = $this->_environment->getWikiManager();
		         global $c_use_soap_for_wiki;
		         if($c_use_soap_for_wiki){
		            $wiki_manager->removeItemFromWiki_soap($current_item_iid,CS_DISCUSSION_TYPE);
		         }
		         $params = $this->_environment->getCurrentParameterArray();
		         unset($params['remove_from_wiki']);
		         redirect($this->_environment->getCurrentContextID(),CS_DISCUSSION_TYPE, 'detail', $params);
		      }
		
		      // Get clipboard
		      if ( $session->issetValue('discussion_clipboard') ) {
		         $clipboard_id_array = $session->getValue('discussion_clipboard');
		      } else {
		         $clipboard_id_array = array();
		      }
		
		      // Copy to clipboard
		      if ( isset($_GET['add_to_discussion_clipboard'])
		           and !in_array($current_item_id, $clipboard_id_array) ) {
		         $clipboard_id_array[] = $current_item_id;
		         $session->setValue('discussion_clipboard', $clipboard_id_array);
		      }
			
			$this->assign('detail', 'content', $this->getDetailContent());
		}
		
		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/
		
		protected function setBrowseIDs() {
			$session = $this->_environment->getSessionItem();
			
			if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_discussion_index_ids')) {
				$this->_browse_ids = array_values((array) $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_discussion_index_ids'));
			}
		}
		
		protected function getDetailContent() {
			$disc_articles = $this->getDiscArticleContent();
			
			$return = array(
				'discussion'		=> $this->getDiscussionContent(),
				'disc_articles'		=> $disc_articles,
				'new_num'			=> count($disc_articles) + 1
			);
			
			return $return;
		}
		
		private function getDiscussionContent() {
			$return = array();
			
			// append return
			$return = array(
				'title'			=> $this->_item->getTitle(),
				'creator'		=> $this->_item->getCreatorItem()->getFullName(),
				'creation_date'	=> getDateTimeInLang($this->_item->getCreationDate()),
				'assessments'	=> $this->getAssessmentInformation()
			);
			
			return $return;
		}
		
		private function getDiscArticleContent() {
			$return = array();
			
			$creatorInfoStatus = array();
			if(!empty($_GET['creator_info_max'])) {
				$creatorInfoStatus = explode('-', $_GET['creator_info_max']);
			}
			
			$disc_articles_manager = $this->_environment->getDiscussionArticlesManager();
			$disc_articles_manager->setDiscussionLimit($this->_item->getItemID(), $creatorInfoStatus);
			
			$discussion_type = $this->_item->getDiscussionType();
			if($discussion_type == 'threaded') {
				$disc_articles_manager->setSortPosition();
			}
			if(isset($_GET['status']) && $_GET['status'] == 'all_articles') {
				$disc_articles_manager->setDeleteLimit(false);
			}
			
			$disc_articles_manager->select();
			$articles_list = $disc_articles_manager->get();
			
			// for performance reasons, pre-fetch latest noticed and reader(for all files)
			$articles_id_array = array();
			$article = $articles_list->getFirst();
			while($article) {
				$articles_id_array[] = $article->getItemID();
				
				$article = $articles_list->getNext();
			}
			$noticed_manager = $this->_environment->getNoticedManager();
			$reader_manager = $this->_environment->getReaderManager();
			$noticed_manager->getLatestNoticedByIDArray($articles_id_array);
			$reader_manager->getLatestReaderByIDArray($articles_id_array);
			
			/*
			
		$current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  $default_room_modules;
      }
      $first = '';
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' ) {
            switch ($link_name[0]) {
               case 'group':
               if (empty($first)){
                  $first = 'group';
               }
               break;
               case CS_TOPIC_TYPE:
               if (empty($first)){
                  $first = CS_TOPIC_TYPE;
               }
               break;
               case CS_INSTITUTION_TYPE:
               if (empty($first)){
                  $first = CS_INSTITUTION_TYPE;
               }
               break;
            }
         }
      }
      if ($context_item->withRubric(CS_TOPIC_TYPE) ) {
         $ids = $discussion_item->getLinkedItemIDArray(CS_TOPIC_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_topics_index_ids', $ids);
      }
      if ( $context_item->withRubric(CS_GROUP_TYPE) ) {
         $ids = $discussion_item->getLinkedItemIDArray(CS_GROUP_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_group_index_ids', $ids);
      }
      if ( $context_item->withRubric(CS_INSTITUTION_TYPE) ) {
         $ids = $discussion_item->getLinkedItemIDArray(CS_INSTITUTION_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_institutions_index_ids', $ids);
      }
      $rubric_connections = array();
      if ($first == CS_TOPIC_TYPE){
         $rubric_connections = array(CS_TOPIC_TYPE);
         if ($context_item->withRubric(CS_GROUP_TYPE) ){
            $rubric_connections[] = CS_GROUP_TYPE;
         }
        if ($context_item->withRubric(CS_INSTITUTION_TYPE)) {
            $rubric_connections[] = CS_INSTITUTION_TYPE;
        }
      } elseif ($first == 'group'){
         $rubric_connections = array(CS_GROUP_TYPE);
         if ($context_item->withRubric(CS_TOPIC_TYPE) ){
            $rubric_connections[] = CS_TOPIC_TYPE;
         }
      } elseif ($first == CS_INSTITUTION_TYPE){
         $rubric_connections = array(CS_INSTITUTION_TYPE);
         if ($context_item->withRubric(CS_TOPIC_TYPE) ){
            $rubric_connections[] = CS_TOPIC_TYPE;
         }
      }
      $detail_view->setRubricConnections($rubric_connections);

      if ( $context_item->isPrivateRoom() ) {
         // add annotations to detail view
         $annotations = $discussion_item->getAnnotationList();
         $reader_manager = $environment->getReaderManager();
         $noticed_manager = $environment->getNoticedManager();
         $annotation = $annotations->getFirst();
         $id_array = array();
         while($annotation){
            $id_array[] = $annotation->getItemID();
            $annotation = $annotations->getNext();
         }
         $reader_manager->getLatestReaderByIDArray($id_array);
         $noticed_manager->getLatestNoticedByIDArray($id_array);
         $annotation = $annotations->getFirst();
         while($annotation ){
            $reader = $reader_manager->getLatestReader($annotation->getItemID());
            if ( empty($reader) or $reader['read_date'] < $annotation->getModificationDate() ) {
               $reader_manager->markRead($annotation->getItemID(),0);
            }
            $noticed = $noticed_manager->getLatestNoticed($annotation->getItemID());
            if ( empty($noticed) or $noticed['read_date'] < $annotation->getModificationDate() ) {
               $noticed_manager->markNoticed($annotation->getItemID(),0);
            }
            $annotation = $annotations->getNext();
         }
         $detail_view->setAnnotationList($annotations);
      }

      if ( $context_item->withRubric(CS_MATERIAL_TYPE) ) {
         $detail_view->setSubItemRubricConnections(array(CS_MATERIAL_TYPE));
      }

      if ( isset($_GET['status']) and $_GET['status'] == 'all_articles' ) {
         $detail_view->setShowAllArticles(true);
      } else {
          $detail_view->setShowAllArticles(false);
      }

      // highlight search words in detail views
      $session_item = $environment->getSessionItem();
      if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array') ) {
         $search_array = $session->getValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
         if ( !empty($search_array['search']) ) {
            $detail_view->setSearchText($search_array['search']);
         }
         unset($search_array);
      }*/
			
			
			
			// go through list
			$item = $articles_list->getFirst();
			$translator = $this->_environment->getTranslationObject();
			$current_user = $this->_environment->getCurrentUserItem();
			$disc_manager = $this->_environment->getDiscManager();
			$position = 1;
			while($item) {
				// files
				$files = $item->getFileList();
				
				// creator
				$creator = $item->getCreatorItem();
				$creator_fullname = '';
				$modificator_image = '';
				$image = '';
				if(isset($creator)) {
					$current_user_item = $this->_environment->getCurrentUserItem();
					if($current_user_item->isGuest() && $creator->isVisibleForLoggedIn()) {
						$creator_fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
					} else {
						$creator_fullname = $creator->getFullName();
						$modificator_item = $item->getModificatorItem();
						$image = $modificator_item->getPicture();
						if(!empty($image)) {
							if($disc_manager->existsFile($image)) {
								$modificator_image = $image;
							}
						}
					}
				}
				
				// noticed
				$noticed = '';
				if($current_user->isUser()) {
					$noticed = $noticed_manager->getLatestNoticed($item->getItemID());
					if(empty($noticed)) {
						// new
						$noticed = 'new';
					} elseif($noticed['read_date'] < $item->getModificationDate()) {
						// changed
						$noticed = 'changed';
					}
				}
				
				// description
				$converter = $this->_environment->getTextConverter();
				$description = $item->getDescription();
				// TODO: these sanitize functions seem not to work with actual ckeditor :/
				
				//$description = $converter->text_as_html_long($converter->cleanDataFromTextArea($description));
				//$description = $converter->showImages($description, $item, true);
				
				/*
				 * $this->_text_converter->setFileArray($this->_getItemFileListForView());
				 * 
				$desc = $this->_text_as_html_long($this->_compareWithSearchText($this->_cleanDataFromTextArea($desc)));
         $desc = $this->_show_images($desc,$item,true);
         $retour .= $this->getScrollableContent($desc,$item,'',true).LF;
         */
				
				// append return
				$return[] = array(
					'item_id'			=> $item->getItemID(),
					'subject'			=> $item->getSubject(),
					'description'		=> $description,
					'creator'			=> $creator_fullname,
					'position'			=> $position,
					'modification_date'	=> getDateTimeInLang($item->getModificationDate(), false),
					'num_attachments'	=> $files->getCount(),
					'noticed'			=> $noticed,
					'modificator_image'	=> $modificator_image,
					'custom_image'		=> !empty($image)
				);
				
				$position++;
				$item = $articles_list->getNext();
			}
			
			return $return;
		}
	}