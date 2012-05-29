<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_preconditions_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}

		public function actionGetInfo() {
			$return = array();
			
			if(isset($this->_data['template'])) {
				$return['template'] = $this->getTemplatePreconditions($this->_data['template']);
			}
			if(isset($this->_data['environment'])) {
				$return['environment'] = $this->getEnvironmentPreconditions($this->_data['environment']);
			}
			if(isset($this->_data['global'])) {
				$return['global'] = $this->getGlobalPreconditions($this->_data['global']);
			}
			if(isset($this->_data['security'])) {
				$return['security'] = $this->getSecurityPreconditions($this->_data['security']);
			}
			if(isset($this->_data['i18n'])) {
				$return['i18n'] = $this->getI18NPreconditions($this->_data['i18n']);
			}
			
			echo json_encode($return);
		}
		
		private function getI18NPreconditions($data) {
			$return = array();
			
			$translator = $this->_environment->getTranslationObject();
			
			foreach($data as $value) {
				$return[$value] = $translator->getMessage($value);
			}
			
			return $return;
		}
		
		private function getTemplatePreconditions($data) {
			$return = array();
			
			foreach($data as $value) {
				if($value === 'tpl_path') {
					$return['tpl_path'] = $this->_tpl_path;
				}
			}
			
			return $return;
		}
		
		private function getEnvironmentPreconditions($data) {
			$return = array();
			
			foreach($data as $value) {
				if($value === 'lang') {
					$return['lang'] = $this->_environment->getSelectedLanguage();
				} elseif($value === 'single_entry_point') {
					$return['single_entry_point'] = $this->_environment->getConfiguration('c_single_entry_point');
				} elseif($value === 'max_upload_size') {
					$return['max_upload_size'] = $this->_environment->getCurrentContextItem()->getMaxUploadSizeInBytes();
				}
			}
			
			return $return;
		}
		
		private function getGlobalPreconditions($data) {
			$return = array();
			
			foreach($data as $value) {
				if($value === 'virus_scan') {
					global $c_virus_scan;
					$return['virus_scan'] = (!isset($c_virus_scan) || $c_virus_scan === false) ? false : true;
				} elseif($value === 'virus_scan_cron') {
					global $c_virus_scan_cron;
					$return['virus_scan_cron'] = (!isset($c_virus_scan_cron) || $c_virus_scan_cron === false) ? false : true;
				}
			}
			
			return $return;
		}
		
		private function getSecurityPreconditions($data) {
			$return = array();
			
			foreach($data as $value) {
				if($value === 'token') {
					$return['token'] = getToken();
				}
			}
			
			return $return;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
	}