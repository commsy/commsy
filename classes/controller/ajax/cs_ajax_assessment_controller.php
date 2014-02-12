<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_assessment_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionVote() {
			$item_id = $this->_data['item_id'];
			$vote = $this->_data['vote'];
			
			$assessment_manager = $this->_environment->getAssessmentManager();
			$item_manager = $this->_environment->getItemManager();
			$item = $item_manager->getItem($item_id);
			
			// TODO: check if user is allowed to vote
			
			// check if user has already voted
			$voted = $assessment_manager->hasCurrentUserAlreadyVoted($item);
			if(!$voted) {
				$assessment_manager->addAssessmentForItem($item, $vote);
			}
			
			unset($assessment_manager);
			
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
		}
		
		public function actionDeleteOwn() {
			$item_link_id = $this->_data['item_id'];
			
			$assessment_manager = $this->_environment->getAssessmentManager();
			$item_id = $assessment_manager->getItemIDForOwn($item_link_id);
			$assessment_manager->delete($item_id);
			
			$this->setSuccessfullDataReturn(array());
			echo $this->_return;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
	}