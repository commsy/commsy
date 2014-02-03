<?php
	require_once('classes/exceptions/cs_base_exception.php');
	
	class cs_detail_item_type_exception extends cs_base_exception {
		public function getErrorMessageTag() {
			switch($this->getCode()) {
				case 0:
					return 'ERROR_ILLEGAL_IID';
				case 1:
					return 'ITEM_NOT_AVAILABLE';
			}
		}
	}