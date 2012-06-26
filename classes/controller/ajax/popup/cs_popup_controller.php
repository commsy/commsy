<?php
	interface cs_popup_controller {
		public function initPopup($data);
		
		public function save($form_data, $additional = array());
	}