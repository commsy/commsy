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

				// sort list by position
				$discussionArticlesList->sortby('treePosition');

				// build the tree array
				$treeArray = array($this->buildTreeArray($discussionArticlesList, $discussionArticlesList->getFirst()));
			}

			$this->setSuccessfullDataReturn($treeArray);
			echo $this->_return;
		}

		private function buildTreeArray($discussionArticlesList, $root) {
			$disc_manager = $this->_environment->getDiscManager();
			$converter = $this->_environment->getTextConverter();

			$tree = array();

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

			// parse position string
			$position = $root->getPosition();
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

			// description
			$description = $root->getDescription();
			//$converter->setFileArray($this->getItemFileList());
			//$description = $converter->showImages($description, $root, true);

			// files
			$files = $root->getFileList();
			$file_string = '';
			if(!$files->isEmpty()) {
				$file_string = '';
				$file = $files->getFirst();
				while($file) {
					$file_string .= '<a rel="lightbox-gallery'.$root->getItemID().'" href="' . $file->getUrl() . '" target="blank">';
					//$name = $file->getDisplayName();
					//$name = $converter->compareWithSearchText($name);
					//$name = $converter->text_as_html_short($name);
					$file_string .= $file->getFileIcon() . '</a>';
					$file = $files->getNext();
				}
			}
			$articleLevelWidth = 350 - ((sizeof(explode('.', $root->getPosition())) - 1)*20);
			$chunkt_length = 45 - ((sizeof(explode('.', $root->getPosition())) - 1)*4);
			if ($chunkt_length < 10){
				$chunkt_length = 10;
			}
			if ($articleLevelWidth < 150){
				$chunkt_length = 150;
			}
			$label ='';
			$label .= "<span style=\" display:inline-block; width:".$articleLevelWidth."px;\">" . chunkText($root->getSubject(),$chunkt_length) . ' '.$file_string."</span>";
			$label .= "<span style=\" display:inline-block; width:180px;\">" . chunkText($creator_fullname,20) . "</span>";
			$label .= "<span style=\"width:".$articleLevelWidth."px;text-align:right;\">".getDateTimeInLang($root->getModificationDate(), false)."</span>";

			$tree = array(
					'item_id'			=> $root->getItemID(),
					'position'			=> $position,
					'subject'			=> $label,
					'description'		=> $description,
					'creator'			=> $creator_fullname,
					'modification_date'	=> getDateTimeInLang($root->getModificationDate(), false)
					//'attachment_infos'	=> $attachment_infos
			);

			$potentialChildList = clone $discussionArticlesList;
			$potentialChildList->removeElement($root);

			$rootPosition = $root->getPosition();
			if (empty($rootPosition)) {
				$rootPosition = "1";
			}
			$rootLevel = sizeof(explode('.', $rootPosition)) - 1;

			// iterate list - get children
			$article = $potentialChildList->getFirst();
			while($article) {
				$articlePosition = $article->getPosition();
				$articleLevel = sizeof(explode('.', $articlePosition)) - 1;

				// skip if item is not a direct child of root
				if($articleLevel === $rootLevel + 1 && $rootPosition === mb_substr($articlePosition, 0, sizeof($articlePosition) - 6)) {
					$tree["children"][] = $this->buildTreeArray($potentialChildList, $article);
				}

				$article = $potentialChildList->getNext();
			}

			return $tree;
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