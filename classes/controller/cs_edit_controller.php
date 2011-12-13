<?php
	require_once('classes/controller/cs_room_controller.php');

	abstract class cs_edit_controller extends cs_room_controller {
		protected $_item_id = null;
		protected $_command = null;
		/*
		protected $_browse_ids = array();
		protected $_position = -1;
		protected $_manager = null;
		*/

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			// init variables
			/*
			$this->getViewMode() = 'browse';
			$this->_filter = array();
			$this->_paging = array(
				'offset'	=> 0,
				'limit'		=> 20
			);
			*/
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		protected function processTemplate() {
			// call parent
			parent::processTemplate();
		}
		
		protected function getPostData() {
			// get item from url
			if(!empty($_GET['iid'])) {
				$this->_item_id = $_GET['iid'];
			} elseif(!empty($_POST['iid'])) {
				$this->_item_id = $_POST['iid'];
			}
			
			// get command
			if(isset($_POST['form_data']['option'])) {
				foreach($_POST['form_data']['option'] as $option => $value) {
					$this->_command = $option;
					break;
				}
			}
		}
	}