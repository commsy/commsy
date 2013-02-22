<?php
	require_once('classes/exceptions/cs_base_exception.php');
	
	class cs_form_mandatory_exception extends cs_base_exception {
		private $_missing_fields = array();
		
		public function setMissingFields($missing_fields) {
			$this->_missing_fields = $missing_fields;
		}
		
		public function getMissingFields() {
			return $this->_missing_fields;
		}
	}
	
	class cs_form_value_exception extends cs_base_exception {
		
	}
	
	class cs_form_general_exception extends cs_base_exception {
		
	}