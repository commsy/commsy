<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_threaded_discussion_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionGetTreeData() {
			// get submitted data
			$discussionId = $this->_data["discussionId"];
			
			// get the discussion item
			$discussionManager = $this->_environment->getDiscussionManager();
			$discussionItem = $discussionManager->getItem($discussionId);
			
			if ($discussionItem->getDiscussionType() == "threaded") {
				// get all discussion articles
				$discussionArticlesManager = $this->_environment->getDiscussionArticleManager();
				$discussionArticlesList = $discussionArticlesManager->getAllArticlesForItem($discussionItem);
				
				// iterate
				$discussionArticle = $discussionArticlesList->getFirst();
				while ($discussionArticle) {
					
					var_dump($discussionArticle->getSubject());
					
					$discussionArticle = $discussionArticlesList->getNext();
				}
			}
			
			
			exit;
			
			echo $this->_return;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// TODO: check access rights
			
			// call parent
			parent::process();
		}
	}
?>