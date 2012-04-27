<?php
	interface cs_popup_controller {
		public function initPopup();
		
		public function save($form_data);
		
		public function getReturn();
	}