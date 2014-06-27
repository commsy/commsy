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

// 		public function sanitize (&$item, $key){
// 			$item = $this->getUtils()->sanitize($item);
// 			#$item = $item.' abc';
// 		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		protected function process() {
			// decode json into array
			// TODO: sanitize

			$this->_data = json_decode(file_get_contents('php://input'), true);
			
			if(empty($this->_data)) {
				$this->_data = $_POST;
			}

			// use converter->sanitize ;
// 			if(empty($this->_data)) {
// 				$this->_data = $_POST;
// 				array_walk_recursive($_POST, array($this, 'sanitize'));
// 			} else {
// 				if ( isset($this->_data["form_data"]) && !empty($this->_data["form_data"]) )
// 				{
// 					foreach ( $this->_data["form_data"] as $key => $data )
// 					{
// 						$filterHTML = true;
// 						$name = $this->_data['form_data'][$key]['name'];
						
// 						if ( $name == "description" || mb_substr($name, 0, 22) == "moderation_description" || mb_substr($name, 0, 8) == "agb_text" ) {
// 							$filterHTML = false;
// 						}
						
// 						if ( $filterHTML === true && isset($this->_data["form_data"][$key]["value"]) )
// 						{
// 							//$this->sanitize($this->_data["form_data"][$key]["value"]);
// 							array_walk_recursive($this->_data["form_data"][$key]["value"], array($this, 'sanitize'));
// 						}
// 					}
// 				}
// 			}
			
			if(empty($this->_data)) {
				$this->_data = $_GET;
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
			
			if (isset($this->_data['callback'])) {
				$functionName = $this->_data['callback'];
				$this->_return = $functionName . '(' . $this->_return . ');';
			}
		}

		public function rawDataReturn($data = array()) {
			echo json_encode($data);
			exit;
		}
	}