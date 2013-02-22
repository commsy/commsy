<?php
	require_once('classes/controller/cs_room_controller.php');

	abstract class cs_edit_controller extends cs_room_controller {
		protected $_item_id = null;
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
		
		protected function checkFormData() {
			try {
				$this->checkForm();
				
				return true;
			} catch(cs_form_mandatory_exception $e) {
				// TODO: implement in edit form
				echo "mandatory catched";
				
				return false;
			} catch(cs_form_value_exception $e) {
				// TODO: implement in edit form
				echo "value catched";
				
				return false;
			}
		}
		
		private function checkForm() {
			foreach($this->getFieldInformation() as $field) {
				
				// check mandatory
				if(isset($field['mandatory']) && $field['mandatory'] === true) {
					if(!isset($_POST['form_data'][$field['name']]) || trim($_POST['form_data'][$field['name']]) === '') {
						throw new cs_form_mandatory_exception('missing mandatory field');
					}
				}
				
				// check values
				// TODO:
				//throw new cs_form_value_exception('value exception');
			}
		}
		
		abstract protected function getFieldInformation();
		
		protected function setFilesForItem(cs_item $item, $post_file_ids) {
			$this->getUtils()->setFilesForItem($item, $post_file_ids);
		}
	}