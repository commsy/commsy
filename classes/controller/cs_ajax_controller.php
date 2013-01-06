<?php
	require_once('classes/controller/cs_base_controller.php');

	abstract class cs_ajax_controller extends cs_base_controller {
		protected $_data = null;
		protected $_return = '';

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			// deativate debug information
			global $c_show_debug_infos;
			$c_show_debug_infos = false;

			// set output mode
			$this->_environment->setOutputMode('JSON');
		}

		public function sanitize (&$item, $key){
			$item = $this->getUtils()->sanitize($item);
			#$item = $item.' abc';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		protected function process() {
			// decode json into array
			// TODO: sanitize

			$this->_data = json_decode(file_get_contents('php://input'), true);

			if(empty($this->_data)) {
				$this->_data = $_POST;
				array_walk_recursive($_POST, array($this, 'sanitize'));
			} else {
				// get content from ckeditor
				if(isset($this->_data['form_data']['2']['value'])
					and !empty($this->_data['form_data']['2']['value'])){
					$tempCont = $this->_data['form_data']['2']['value'];
				}
				array_walk_recursive($this->_data, array($this, 'sanitize'));
				if(isset($this->_data['form_data']['2']['value'])
					and !empty($this->_data['form_data']['2']['value'])){
					$this->_data['form_data']['2']['value'] = $tempCont;
				}
			}


			// the actual function determes the method to call
			$function = 'action' . ucfirst($_GET['action']);

			if(!method_exists($this, $function)) die('Method ' . $function . ' does not exists!');

			// call
			call_user_func_array(array($this, $function), array());
		}

		public function setErrorReturn($code, $reason, $detail) {
			// setup return
			$return = array(
				'status'	=> 'error',
				'code'		=> (string) $code,
				'reason'	=> $reason,
				'detail'	=> $detail
			);

			$this->_return = json_encode($return);
		}

		public function setSuccessfullDataReturn($data = array()) {
			// setup return
			$return = array(
				'status'	=> 'success',
				'data'		=> $data
			);

			$this->_return = json_encode($return);
		}

		public function rawDataReturn($data = array()) {
			echo json_encode($data);
			exit;
		}
	}