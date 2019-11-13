<?php
	require_once('classes/controller/cs_detail_controller.php');

	class cs_discussion_detail_controller extends cs_detail_controller {
		const MAX_DEEP_THREADED = 12;
		static private $threadedIndex = 0;

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

			// set tpl file, if threaded
			if($this->_item->getDiscussionType() === 'threaded') {
				$this->_tpl_file = 'discussion_detail_threaded';
			}


			/*
			 * include_once('include/inc_delete_entry.php');

// Get the translator object
$translator = $environment->getTranslationObject();

$item_manager = $environment->getItemManager();
$type = $item_manager->getItemType($_GET['iid']);
if ($type != CS_DISCUSSION_TYPE) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ERROR_ILLEGAL_IID'));
   $page->add($errorbox);
} else {
//used to signal which "creator infos" of annotations are expanded...
   $creatorInfoStatus = array();
   if (!empty($_GET['creator_info_max'])) {
     $creatorInfoStatus = explode('-',$_GET['creator_info_max']);
   }
   // Load the shown item
   $discussion_manager = $environment->getDiscussionManager();
   $discussion_item = $discussion_manager->getItem($current_item_id);
   $current_user = $environment->getCurrentUser();

   if ( !isset($discussion_item) ) {
      include_once('functions/error_functions.php');
       trigger_error('Item '.$current_item_id.' does not exist!', E_USER_ERROR);
   } elseif ( $discussion_item->isDeleted() ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ITEM_NOT_AVAILABLE'));
      $page->add($errorbox);
   } elseif ( !$discussion_item->maySee($current_user) ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
      $page->add($errorbox);
   } else {
			 */

			$session = $this->_environment->getSessionItem();
			if(isset($_GET['export_to_wiki'])){
				$wiki_manager = $this->_environment->getWikiManager();
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
			if ( isset($_GET['add_to_discussion_clipboard']) && !in_array($current_item_id, $clipboard_id_array) ) {
				$clipboard_id_array[] = $current_item_id;
				$session->setValue('discussion_clipboard', $clipboard_id_array);
			}

			// set clipboard ids
			$this->setClipboardIDArray($clipboard_id_array);

			// mark as read and noticed
			$this->markRead();
			$this->markNoticed();

			// assessment
			$this->assign('detail', 'assessment', $this->getAssessmentInformation($this->_item));

			$this->assign('detail', 'content', $this->getDetailContent());

			// wiki export
			global $c_pmwiki;
			$context_item = $this->_environment->getCurrentContextItem();
			if($c_pmwiki and $context_item->isWikiActive() and !isset($_GET['version_id'])){
			   $this->assign('detail', 'export_to_wiki', true);
			}
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
		    if ($this->_item->isNotActivated()){
		        $activating_date = $this->_item->getActivatingDate();
		        $text = '';
		        if (strstr($activating_date,'9999-00-00')){
		           $activating_text = $this->_environment->getTranslationObject()->getMessage('COMMON_NOT_ACTIVATED');
		        }else{
		           $activating_text = $this->_environment->getTranslationObject()->getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($this->_item->getActivatingDate());
		        }
				$temp_array = array();
				$temp_array[] = $this->_environment->getTranslationObject()->getMessage('COMMON_RIGHTS');
				$temp_array[] = $activating_text;
				$formal[] = $temp_array;
		    }
		    $temp_array = array();

		    $discussion_type = $this->_item->getDiscussionType();

			$return = array(
				'formal'			=> $formal,
				'item_id'			=> $this->_item->getItemID(),
				'discussion'		=> $this->getDiscussionContent(),
				'disc_articles'		=> $disc_articles,
				'new_num'			=> count($disc_articles) + 1,
				'moredetails'		=> $this->getCreatorInformationAsArray($this->_item),
				'numArticles'		=> ($discussion_type === 'threaded') ? self::$threadedIndex : sizeof($disc_articles)
			);

			return $return;
		}

		protected function getAdditionalActions(&$perms) {
			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();

			$perms['wiki'] = false;

			if($this->_item->mayEdit($current_user) && $current_context->isWikiActive() && $this->_with_modifying_actions && (!$this->_item->isA(CS_DISCUSSION_TYPE) || $this->_item->getDiscussionType() === 'simple')) {
				$perms['wiki'] = true;

				/*
				 * $params = array();
         $params['iid'] = $item->getItemID();
         $params['export_to_wiki'] = 'true';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/export_wiki.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_EXPORT_TO_WIKI').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/export_wiki.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_EXPORT_TO_WIKI').'"/>';
         }
         $html .= ahref_curl( $this->_environment->getCurrentContextID(),
                                   $this->_environment->getCurrentModule(),
                                   'detail',
                                   $params,
                                   $image,
                                   $this->_translator->getMessage('ITEM_EXPORT_TO_WIKI')).LF;
         unset($params);
				 */
			} elseif($current_context->isWikiActive()) {
				/*
				 * if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/export_wiki_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ITEM_EXPORT_TO_WIKI').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/export_wiki_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('ITEM_EXPORT_TO_WIKI').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('ITEM_EXPORT_TO_WIKI')).' "class="disabled">'.$image.'</a>'.LF;
				 */
			}
		}

		protected function markArticlesReadedAndNoticed($article_list) {
			$reader_manager = $this->_environment->getReaderManager();
			$noticed_manager = $this->_environment->getNoticedManager();

			// collect an array of all ids and precach
			$id_array = array();
			$article = $article_list->getFirst();
			while($article) {
				$id_array[] = $article->getItemID();

				$article = $article_list->getNext();
			}

			$reader_manager->getLatestReaderByIDArray($id_array);
			$noticed_manager->getLatestNoticedByIDArray($id_array);

			// mark if needed
			$article = $article_list->getFirst();
			while($article) {
				$reader = $reader_manager->getLatestReader($article->getItemID());
				if(empty($reader) || $reader['read_date'] < $article->getModificationDate()) {
					$reader_manager->markRead($article->getItemID(), 0);
				}

				$noticed = $noticed_manager->getLatestNoticed($article->getItemID());
				if(empty($noticed) || $noticed['read_date'] < $article->getModificationDate()) {
					$noticed_manager->markNoticed($article->getItemID(), 0);
				}

				$article = $article_list->getNext();
			}
		}


		private function getDiscussionContent() {
			$return = array();

			// append return
			$return = array(
				'title'				=> $this->_item->getTitle(),
				'item_id'			=> $this->_item->getItemID(),
				'assessments'		=> $this->getAssessmentInformation()
			);

			return $return;
		}

		protected function getEditActions($item, $user, $module = '') {
			$return = array(
				'edit'		=> false,
				'delete'	=> false,
				'answer'	=> false
			);

			$current_context = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();
			$discussion_type = $this->_item->getDiscussionType();

			if($discussion_type === 'threaded') {
				// TODO: check this
				$return = parent::getEditActions($item, $current_user, 'discarticle');


				/*
					if ( $subitem->mayEdit($user) and $this->_with_modifying_actions ) {
		            $params = array();
		            $params['iid'] = $item->getItemID();
		            $params['discarticle_action'] = 'edit';
		            $params['discarticle_iid'] = $subitem->getItemID();
		            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
		               $image = '<img src="images/commsyicons_msie6/22x22/edit.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
		            } else {
		               $image = '<img src="images/commsyicons/22x22/edit.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
		            }
		            $html .= ahref_curl(   $this->_environment->getCurrentContextID(),
		            $this->_environment->getCurrentModule(),
			                                'detail',
		            $params,
		            $image,
		            $this->_translator->getMessage('COMMON_EDIT_ITEM'),
			                                '',
		                                	'discarticle_form') . LF;
		            unset($params);
		         } else {
		            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
		               $image = '<img src="images/commsyicons_msie6/22x22/edit_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
		            } else {
		               $image = '<img src="images/commsyicons/22x22/edit_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_EDIT_ITEM').'"/>';
		            }
		            $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_EDIT_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
		         }
		         */
			} else {
				$return = parent::getEditActions($item, $current_user, 'discarticle');
			}

			if($user->isUser() && $discussion_type === 'threaded' && $this->_with_modifying_actions) {

				/*
				$params = array();
		         //$params['iid'] = 'NEW';
		         $params['iid'] = $item->GetItemID();
		         //$params['discussion_id'] = $item->getItemID();

		         $params['ref_position'] = 1;
		         $ref_position = $subitem->getPosition();
		         if(!empty($ref_position)){
		            $params['ref_position'] = $subitem->getPosition();
		         }
		         //$params['ref_did'] = $subitem->getItemID();
		         $params['answer_to'] = $subitem->getItemID();
		         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
		            $image = '<img src="images/commsyicons_msie6/22x22/new_section.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCARTICLE_ANSWER_NEW').'"/>';
		         } else {
		            $image = '<img src="images/commsyicons/22x22/new_section.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCARTICLE_ANSWER_NEW').'"/>';
		         }

		         // in threaded view, we want to put the form directly into the detail view and not on a single page

		         $html .= ahref_curl(   $this->_environment->getCurrentContextID(),
		                                'discussion',
		                                'detail',
		         $params,
		         $image,
		         $this->_translator->getMessage('DISCARTICLE_ANSWER_NEW'),
		                                '',
		                                'discarticle_form').LF;
		         unset($params);
         */
			} elseif($discussion_type === 'threaded') {
				/*
		         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
		            $image = '<img src="images/commsyicons_msie6/22x22/new_section_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCARTICLE_ANSWER_NEW').'"/>';
		         } else {
		            $image = '<img src="images/commsyicons/22x22/new_section_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DISCARTICLE_ANSWER_NEW').'"/>';
		         }
		         $html .= $this->_translator->getMessage('DISCARTICLE_ANSWER_NEW').LF;
		         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('DISCARTICLE_ANSWER_NEW')).' "class="disabled">'.$image.'</a>'.LF;
		         */
			}

			if($item->mayEdit($user) && $this->_with_modifying_actions) {
				$return['delete'] = true;


				/*
				$params = $this->_environment->getCurrentParameterArray();
         $params['action'] = 'delete';
         $params['discarticle_iid'] = $subitem->getItemID();
         $params['iid'] = $item->getItemID();
         $params['discarticle_action'] = 'delete';
         if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= ahref_curl( 		   $this->_environment->getCurrentContextID(),
                                       $this->_environment->getCurrentModule(),
                                       'detail',
                                       $params,
                                       $image,
                                       '',
                                       '',
                                       '',//anchor'.$subitem->getItemID(),
        							   '',
       								   '',
       								   '',
        							   '',
       								   '',
        							   'delete_confirm_disarc'.$subitem->getItemID()).LF;
         unset($params);
				 */
			} else {
				/*
				 * if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
            $image = '<img src="images/commsyicons_msie6/22x22/delete_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         } else {
            $image = '<img src="images/commsyicons/22x22/delete_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_DELETE_ITEM').'"/>';
         }
         $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_DELETE_ITEM')).' "class="disabled">'.$image.'</a>'.LF;
				 */
			}

			if ($user->isUser() && !$user->isOnlyReadUser()) $return["answer"] = true;

			return $return;
		}

		private function getDiscArticleContent() {
			$context_item = $this->_environment->getCurrentContextItem();
			$session = $this->_environment->getSessionItem();

			$creatorInfoStatus = array();
			if(!empty($_GET['creator_info_max'])) {
				$creatorInfoStatus = explode('-', $_GET['creator_info_max']);
			}

			// load discussion articles
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
			$this->markArticlesReadedAndNoticed($articles_list);
			// set rubric connections
			$current_room_modules = $context_item->getHomeConf();
			/*
			 * if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  $default_room_modules;
      }
			 */
			$room_modules = explode(',', $current_room_modules);

			$first = '';
			foreach($room_modules as $module) {
				list($name, $view) = explode('_', $module);

				if($view !== 'none') {
					switch($name) {
						case 'group':
							if(empty($first)) {
								$first = 'group';
							}
							break;
						case CS_TOPIC_TYPE:
							if(empty($first)) {
								$first = CS_TOPIC_TYPE;
							}
							break;
					}
				}
			}

			// set up ids of linked items
			if($context_item->withRubric(CS_TOPIC_TYPE)) {
				$ids = $this->_item->getLinkedItemIDArray(CS_TOPIC_TYPE);
				$session->setValue('cid' . $this->_environment->getCurrentContextID() . '_topics_index_ids', $ids);
			}
			if($context_item->withRubric(CS_GROUP_TYPE)) {
				$ids = $this->_item->getLinkedItemIDArray(CS_GROUP_TYPE);
				$session->setValue('cid' . $this->_environment->getCurrentContextID() . '_group_index_ids', $ids);
			}

			/* seems to be unused
			$rubric_connections = array();
			if($first === CS_TOPIC_TYPE) {
				$rubric_connections = array(CS_TOPIC_TYPE);
				if($context_item->withRubric(CS_GROUP_TYPE)) {
					$rubric_connections[] = CS_GROUP_TYPE;
				}
				if($context_item->withRubric(CS_INSTITUTION_TYPE)) {
					$rubric_connections[] = CS_INSTITUTION_TYPE;
				}
			} elseif($first === 'group') {
				$rubric_connections = array(CS_GROUP_TYPE);
				if($context_item->withRubric(CS_TOPIC_TYPE)) {
					$rubric_connections[] = CS_TOPIC_TYPE;
				}
			} elseif($first == CS_INSTITUTION_TYPE) {
				$rubric_connections = array(CS_INSTITUTION_TYPE);
				if($context_item->withRubric(CS_TOPIC_TYPE)) {
					$rubric_connections[] = CS_TOPIC_TYPE;
				}
			}
			$rubric_connections[] = CS_MATERIAL_TYPE;

			*/
			// seems to be not needed
			//$this->setRubricConnections($rubric_connections);


			/* TODO
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

			if($this->_item->getDiscussionType() === 'threaded') {
					return $this->getDiscArticleContentThreaded($articles_list);
			} else {
					return $this->getDiscArticleContentLinear($articles_list);
			}
		}

		/*
		 * TODO: Algorithm could be optimized
		 */
		private function buildThreadedTree($node_list, $root) {
			$return = array();

			$noticed_manager = $this->_environment->getNoticedManager();
			$reader_manager = $this->_environment->getReaderManager();
			$translator = $this->_environment->getTranslationObject();
			$current_user = $this->_environment->getCurrentUserItem();
			$disc_manager = $this->_environment->getDiscManager();
			$converter = $this->_environment->getTextConverter();

			$root_position = $root->getPosition();
			if (empty($root_position)) {
				$root_position = "1";
			}
			$root_level = sizeof(explode('.', $root_position)) - 1;

			// get through
			$item = $node_list->getFirst();
			while($item) {
				$item_position = $item->getPosition();
				$item_level = sizeof(explode('.', $item_position)) - 1;

				// skip if item is not a direct child of root
				if($item_level === $root_level + 1 && $root_position === mb_substr($item_position, 0, sizeof($item_position) - 6)) {
					// files
					$files = $item->getFileList();

					// creator
					$creator = $item->getCreatorItem();
					$creator_fullname = '';
					$modificator_image = '';
					$image = '';
					// TODO: implement over general detail_controller.php
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
					$description = $item->getDescription();
					//description = $converter->cleanDataFromTextArea($description);
					$converter->setFileArray($this->getItemFileList());
					//$description = $converter->text_as_html_long($description);
					$description = $converter->_activate_urls($description);
					$description = $converter->showImages($description, $item, true);
					//$retour .= $this->getScrollableContent($desc,$item,'',true).LF;

					// parse position string
					$position = $item->getPosition();
					$numberArray = explode(".", $position);
					$number = "";
					foreach ($numberArray as $num) {
						if (empty($number)) {
							$number = "1";
						} else {
							$len = mb_strlen($num);
							$tmpNum = mb_substr($num, 1, $len);
							$first = mb_substr($tmpNum, 0, 1);

							while ($first == "0") {
								$tmpNum = mb_substr($tmpNum, 1, mb_strlen($tmpNum));
								$first = mb_substr($tmpNum, 0, 1);
							}
							$number .= "." . $tmpNum;
						}
					}

					$position = $number;

					$potentialChildList = clone $node_list;
					$potentialChildList->removeElement($item);

					// files
					$files = $item->getFileList();
					// files
					$attachment_infos = array();
					if(!$files->isEmpty()) {
						$file = $files->getFirst();
						while($file) {
							if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
									$file_string = '<a class="lightbox_'.$this->_item->getItemID().'" href="' . $file->getUrl() . '" target="blank">';
									$name = $file->getDisplayName();
									//TODO:
									//$name = $converter->compareWithSearchText($name);
									$name = $converter->text_as_html_short($name);

									$file_string .= $name.' '.$file->getFileIcon() . ' ' . '</a> (' . $file->getFileSize() . ' KB)';
							} else {
								$file_string = '<a href="' . $file->getUrl() . '" target="blank">';
								$name = $file->getDisplayName();
								//TODO:
								//$name = $converter->compareWithSearchText($name);
								$name = $converter->text_as_html_short($name);
								//$file_string = $file->getFileIcon() . ' ' . $name;
								$file_string .= $name.' '.$file->getFileIcon() . ' ' . '</a> (' . $file->getFileSize() . ' KB)';
							}
							$tmp_array = array();
							$tmp_array['name'] = $file_string;
							if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
								$tmp_array['icon'] = '<a class="lightbox_'.$this->_item->getItemID().'" href="' . $file->getUrl() . '" target="blank">'.$file->getFileIcon(). '</a>';
							}else{
								$tmp_array['icon'] = '<a href="' . $file->getUrl() . '" target="blank">'.$file->getFileIcon(). '</a>';
							}


							$file_array[] = $tmp_array;

							$lightbox = false;
							if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) $lightbox = true;

							$info = array();
							$info['file_name']	= $converter->text_as_html_short($file->getDisplayName());
							$info['file_icon']	= $file->getFileIcon();
							$info['file_url']	= $file->getURL();
							$info['file_size']	= $file->getFileSize();
							$info['lightbox']	= $lightbox;

							$attachment_infos[] = $info;

							$file = $files->getNext();
						}

						$entry['files'] = $file_array;
						$file_array = array();
					}


					// append return and recursive call
					$return[] = array(
						'item_id'			=> $item->getItemID(),
						'position'			=> substr($number,2,strlen($number)),
						'subject'			=> $item->getSubject(),
						'description'		=> $description,
						'creator'			=> $creator_fullname,
						'modification_date'	=> getDateTimeInLang($item->getModificationDate(), false),
						'num_attachments'	=> $files->getCount(),
						'noticed'			=> $noticed,
						'formal'			=> $entry,
						'modificator_image'	=> $modificator_image,
						'custom_image'		=> !empty($image),
						'actions'			=> $this->getEditActions($item, $current_user),
						'moredetails'		=> $this->getCreatorInformationAsArray($item),
						'index'				=> self::$threadedIndex++,
						'children'			=> $this->buildThreadedTree($potentialChildList, $item)
					);
					// unset files
					$entry['files'] = '';
				}

				$item = $node_list->getNext();
			}

			return $return;
		}

		private function getDiscArticleContentThreaded($articles_list) {
			$return = array();

			$noticed_manager = $this->_environment->getNoticedManager();
			$reader_manager = $this->_environment->getReaderManager();
			$translator = $this->_environment->getTranslationObject();
			$current_user = $this->_environment->getCurrentUserItem();
			$disc_manager = $this->_environment->getDiscManager();
			$converter = $this->_environment->getTextConverter();

			// first is always root
			$root = $articles_list->getFirst();

			// files
			$files = $root->getFileList();
			// files
			$attachment_infos = array();
			if(!$files->isEmpty()) {
				$file = $files->getFirst();
				while($file) {
					if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
							$file_string = '<a class="lightbox_'.$this->_item->getItemID().'" href="' . $file->getUrl() . '" target="blank">';
							$name = $file->getDisplayName();
							//TODO:
							//$name = $converter->compareWithSearchText($name);
							$name = $converter->text_as_html_short($name);

							$file_string .= $name.' '.$file->getFileIcon() . ' ' . '</a> (' . $file->getFileSize() . ' KB)';
					} else {
						$file_string = '<a href="' . $file->getUrl() . '" target="blank">';
						$name = $file->getDisplayName();
						//TODO:
						//$name = $converter->compareWithSearchText($name);
						$name = $converter->text_as_html_short($name);
						//$file_string = $file->getFileIcon() . ' ' . $name;
						$file_string .= $name.' '.$file->getFileIcon() . ' ' . '</a> (' . $file->getFileSize() . ' KB)';
					}
					$tmp_array = array();
					$tmp_array['name'] = $file_string;
					if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
						$tmp_array['icon'] = '<a class="lightbox_'.$this->_item->getItemID().'" href="' . $file->getUrl() . '" target="blank">'.$file->getFileIcon(). '</a>';
					}else{
						$tmp_array['icon'] = '<a href="' . $file->getUrl() . '" target="blank">'.$file->getFileIcon(). '</a>';
					}

					$file_array[] = $tmp_array;

					$lightbox = false;
					if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) $lightbox = true;

					$info = array();
					$info['file_name']	= $converter->text_as_html_short($file->getDisplayName());
					$info['file_icon']	= $file->getFileIcon();
					$info['file_url']	= $file->getURL();
					$info['file_size']	= $file->getFileSize();
					$info['lightbox']	= $lightbox;

					$attachment_infos[] = $info;

					$file = $files->getNext();
				}

				$entry['files'] = $file_array;
				$file_array = array();
			}

			// creator
			$creator = $root->getCreatorItem();
			$creator_fullname = '';
			$modificator_image = '';
			$image = '';
			// TODO: implement over general detail_controller.php
			if(isset($creator)) {
				$current_user_item = $this->_environment->getCurrentUserItem();
				if($current_user_item->isGuest() && $creator->isVisibleForLoggedIn()) {
					$creator_fullname = $translator->getMessage('COMMON_USER_NOT_VISIBLE');
				} else {
					$creator_fullname = $creator->getFullName();
					$modificator_item = $root->getModificatorItem();
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
				$noticed = $noticed_manager->getLatestNoticed($root->getItemID());
				if(empty($noticed)) {
					// new
					$noticed = 'new';
				} elseif($noticed['read_date'] < $root->getModificationDate()) {
					// changed
					$noticed = 'changed';
				}
			}

			// description
			$description = $root->getDescription();
			//$description = $converter->cleanDataFromTextArea($description);
			$converter->setFileArray($this->getItemFileList());
         if ( $this->_with_old_text_formating ) {
      		$description = $converter->textFullHTMLFormatting($description);
      	} else {
			   $description = $converter->textFullHTMLFormatting($description);
			   $description = $converter->showImages($description, $root, true);
      	}
      	//$retour .= $this->getScrollableContent($desc,$root,'',true).LF;
      	    
			$return[] = array(
				'item_id'			=> $root->getItemID(),
				'position'			=> '',
				'subject'			=> $root->getSubject(),
				'description'		=> $description,
				'creator'			=> $creator_fullname,
				'modification_date'	=> getDateTimeInLang($root->getModificationDate(), false),
				'num_attachments'	=> $files->getCount(),
				'attachment_infos'	=> $attachment_infos,
				'noticed'			=> $noticed,
				'modificator_image'	=> $modificator_image,
				'custom_image'		=> !empty($image),
				'actions'			=> $this->getEditActions($root, $current_user),
				'moredetails'		=> $this->getCreatorInformationAsArray($root),
				'formal'			=> $entry,
				'index'				=> self::$threadedIndex++
			);

			$return[0]['children'] = $this->buildThreadedTree($articles_list, $root);

			return $return;
		}

		private function getDiscArticleContentLinear($articles_list) {
			$noticed_manager = $this->_environment->getNoticedManager();
			$reader_manager = $this->_environment->getReaderManager();

			$return = array();

			// go through list
			$item = $articles_list->getFirst();
			$converter = $this->_environment->getTextConverter();
			$translator = $this->_environment->getTranslationObject();
			$current_user = $this->_environment->getCurrentUserItem();
			$disc_manager = $this->_environment->getDiscManager();
			$position = 0;

			while($item) {
				$attachment_infos = array();
				// files
				$files = $item->getFileList();
				if(!$files->isEmpty()) {
					$file = $files->getFirst();
					while($file) {
						if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
								$file_string = '<a class="lightbox_'.$this->_item->getItemID().'" href="' . $file->getUrl() . '" target="blank">';
								$name = $file->getDisplayName();
								//TODO:
								//$name = $converter->compareWithSearchText($name);
								$name = $converter->text_as_html_short($name);

								$file_string .= $name.' '.$file->getFileIcon() . ' ' . '</a> (' . $file->getFileSize() . ' KB)';
						} else {
							$file_string = '<a href="' . $file->getUrl() . '" target="blank">' . $file->getFileIcon() . ' ' . ($converter->text_as_html_short($file->getDisplayName())) . '</a> (' . $file->getFileSize() . ' KB)';
						}
						$tmp_array = array();
						$tmp_array['name'] = $file_string;
						if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
							$tmp_array['icon'] = '<a class="lightbox_'.$this->_item->getItemID().'" href="' . $file->getUrl() . '" target="blank">'.$file->getFileIcon(). '</a>';
						}else{
							$tmp_array['icon'] = '<a href="' . $file->getUrl() . '" target="blank">'.$file->getFileIcon(). '</a>';
						}

						$file_array[] = $tmp_array;

						$lightbox = false;
						if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) $lightbox = true;

						$info = array();
						$info['file_name']	= $converter->text_as_html_short($file->getDisplayName());
						$info['file_icon']	= $file->getFileIcon();
						$info['file_url']	= $file->getURL();
						$info['file_size']	= $file->getFileSize();
						$info['lightbox']	= $lightbox;

						$attachment_infos[] = $info;

						$file = $files->getNext();
					}

					$entry['files'] = $file_array;
					$file_array = array();
				}

				// creator
				$creator = $item->getCreatorItem();
				$creator_fullname = '';
				$modificator_image = '';
				$image = '';
				// TODO: implement over general detail_controller.php
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
				//$description = $converter->cleanDataFromTextArea($description);
				$converter->setFileArray($this->getItemFileList());
      		if ( $this->_with_old_text_formating ) {
      			$description = $converter->textFullHTMLFormatting($description);
      			
      		} else {
				   $description = $converter->textFullHTMLFormatting($description);
				   $description = $converter->showImages($description, $item, true);
      		}
      		
				//$retour .= $this->getScrollableContent($desc,$item,'',true).LF;

				// append return
				$return[] = array(
					'item_id'			=> $item->getItemID(),
					'subject'			=> $item->getSubject(),
					'description'		=> $description,
					'creator'			=> $creator_fullname,
					'position'			=> $position,
					'modification_date'	=> getDateTimeInLang($item->getModificationDate(), false),
					'num_attachments'	=> $files->getCount(),
					'attachment_infos'	=> $attachment_infos,
					'noticed'			=> $noticed,
					'modificator_image'	=> $modificator_image,
					'custom_image'		=> !empty($image),
					'actions'			=> $this->getEditActions($item, $current_user),
					'moredetails'		=> $this->getCreatorInformationAsArray($item),
					'formal'			=> $entry
				);
				$position++;
				$entry = array();
				$item = $articles_list->getNext();
			}
			return $return;
		}
	}