<?php
	require_once('classes/controller/cs_ajax_controller.php');
	require_once('classes/controller/cs_room_controller.php');

	class cs_ajax_popup_controller extends cs_ajax_controller {
		protected $_popup_controller = null;

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}

		public function actiongetHTML() {
			// get module from submitted data
			$module = $this->_data['module'];
			$this->_tpl_file = 'popups/' . $module . '_popup';

			// include
			require_once('classes/controller/ajax/popup/cs_popup_' . $module . '_controller.php');
			$class_name = 'cs_popup_' . $module . '_controller';
			$this->_popup_controller = new $class_name($this->_environment, $this);
			
			// connection portal2portal
			if ( $module == 'connection' ) {
				if ( !empty($this->_data['id']) ) {
					$this->_popup_controller->setTabID($this->_data['id']);
				}
			}

			// initPopup
			$this->initPopup();

			global $c_smarty;
			if($c_smarty === true) {
				ob_start();

				$this->displayTemplate();

				// setup return
				$output = ob_get_clean();
				$this->setSuccessfullDataReturn($output);

				//echo preg_replace('/\s/', '', $this->_return);
				//echo str_replace(array('\n', '\t'), '', $this->_return);		// for some reasons, categories in popup will not work if active
				echo $this->_return;

			} else {
				echo json_encode('smarty not enabled');
			}
		}

		protected function initPopup() {
			$this->_popup_controller->initPopup($this->_data);
		}

		public function actionSave() {

			// include
			$module = $this->_data['module'];
			require_once('classes/controller/ajax/popup/cs_popup_' . $module . '_controller.php');
			$class_name = 'cs_popup_' . $module . '_controller';
			$this->_popup_controller = new $class_name($this->_environment, $this);

			// get form data
			$form_data = array();
			if (isset($this->_data['form_data']) && is_string($this->_data['form_data'])) {
				$this->_data['form_data'] = json_decode($this->_data['form_data'], true);
			}
			foreach($this->_data['form_data'] as $data) {
				$form_data[$data['name']] = $data['value'];
			}

			// additional data
			$additional = array();
			if (isset($this->_data['additional']) && is_string($this->_data['additional'])) {
				$this->_data['additional'] = json_decode($this->_data['additional'], true);
			}
			if(isset($this->_data['additional']) && !empty($this->_data['additional'])) {
				$additional = $this->_data['additional'];
			}

			$_POST = $form_data;
			$_POST['log'] = 'INSERT';
			// logging save
			include_once('include/inc_log.php');

			$this->_popup_controller->save($form_data, $additional);
			
			echo $this->_return;
		}

		public function checkFormData($sub = '') {
			try {
				$this->checkForm($sub);

				return true;
			} catch(cs_form_mandatory_exception $e) {
				// setup return array
				$this->setErrorReturn($e->getCode(), $e->getMessage(), $e->getMissingFields());

				echo $this->_return;
				exit;
			} catch(cs_form_value_exception $e) {
				// TODO: implement in edit form
				echo "value catched";
				exit;
			}
		}

		public function setSuccessfullItemIDReturn($item_id) {
			// setup return
			parent::setSuccessfullDataReturn($item_id);
			echo $this->_return;
			exit;
		}
		
		public function setSuccessfullDataReturn($data = array()) {
			// setup return
			parent::setSuccessfullDataReturn($data);
			echo $this->_return;
			exit;
		}
		
		public function setErrorReturn($code, $reason, $detail = array()) {
			// setup return
			parent::setErrorReturn($code, $reason, $detail);
			echo $this->_return;
			exit;
		}

		private function checkForm($sub) {
			// get form data
			$form_data = array();
			foreach($this->_data['form_data'] as $data) {
				$form_data[$data['name']] = $data['value'];
			}

			$missing_fields = array();
			foreach($this->_popup_controller->getFieldInformation($sub) as $field) {
				// check mandatory
				if(isset($field['mandatory']) && $field['mandatory'] === true) {
					if(!isset($form_data[$field['name']]) || trim($form_data[$field['name']]) === '') {
						// add to missing
						$missing_fields[] = $field['name'];
					}
				}

				// check values
				// TODO:
				//throw new cs_form_value_exception('value exception');
			}

			if(!empty($missing_fields)) {
				// setup new exception
				$exception = new cs_form_mandatory_exception('missing mandatory field', 101);
				$exception->setMissingFields($missing_fields);

				throw $exception;
			}
		}
		
		/** \brief	General form checks
		 * 
		 * This Method performs some general checks when saving.
		 * 
		 * @param	form_data	The form data
		 * @param	additional	Additinal given data to the save process
		 */
		public function performChecks($item, $form_data, $additional) {
			/**
			 * Check if buzzwords and tags are mandatory and given
			 */
			$utils = $this->getUtils();
			$currentContextItem = $this->_environment->getCurrentContextItem();
			
			try {
				if ($utils->showTags($this->_data["module"]) && $currentContextItem->isTagMandatory()) {
					// check
					if (	( $item === null && empty($form_data["tags"]) ) ||
							( $item !== null && $form_data["tags_tab"] == "true" && !isset($form_data["tags"]) ) )
					{
						$exception = new cs_form_general_exception("tags are mandatory", 113);
						throw $exception;
					}
				}
				
				if ($utils->showBuzzwords($this->_data["module"]) && $currentContextItem->isBuzzwordMandatory()) {
					$buzzwordsEmpty = false;
					
					if ( sizeof($form_data["buzzwords"]) === 1 )
					{
						// in this case, no buzzwords are checked, but the value (empty or not) of the new buzzword "on the fly" field is given
						list($onTheFlyBuzzword) = trim($form_data["buzzwords"][0]);
						
						if ( !isset($onTheFlyBuzzword) || empty($onTheFlyBuzzword) )
						{
							$buzzwordsEmpty = true;
						}
					}
					
					// check
					if (	( $item === null && $buzzwordsEmpty === true ) ||
							( $item !== null && $form_data["buzzwords_tab"] == "true" && $buzzwordsEmpty === true ) )
					{
						$exception = new cs_form_general_exception("buzzwords are mandatory", 114);
						throw $exception;
					}
				}
			} catch (cs_form_general_exception $e) {
				// setup return array
				$this->setErrorReturn($e->getCode(), $e->getMessage(), array());
				
				echo $this->_return;
				exit;
			}
		}

		public function getUtils() {
			return parent::getUtils();
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();

		}

		public function assign($categorie, $key, $assignment) {
			parent::assign($categorie, $key, $assignment);
		}
	}