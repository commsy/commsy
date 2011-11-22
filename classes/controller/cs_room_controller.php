<?php
	require_once('classes/controller/cs_base_controller.php');
	
	abstract class cs_room_controller extends cs_base_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
	}