<?php
	require_once('classes/controller/cs_room_controller.php');
	
	abstract class cs_custom_room_controller extends cs_room_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
	}