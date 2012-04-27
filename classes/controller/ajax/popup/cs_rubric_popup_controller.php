<?php
	interface cs_rubric_popup_controller {
		public function initPopup($item);
		
		public function getFieldInformation();
		
		public function save($form_data);
		
		public function getReturn();
	}