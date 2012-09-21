<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_myCalendar_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}
		
		public function actionQuery() {
			$query = $this->_data["query"];
			
			$dates = array();
			$dates[] = array(
				"id"		=> 0,
				"summary"	=> "Event 1",
				"startTime"	=> "2012-09-20T10:00",
				"endTime"	=> "2012-09-20T12:00"
			);
			
			$this->rawDataReturn($dates);
		}
		
		public function actionGet() {
			$id = $this->_data["id"];
			
			$date = array(
					"id"		=> 0,
					"summary"	=> "Event 1",
					"startTime"	=> "2012-09-20T10:00",
					"endTime"	=> "2012-09-20T12:00"
			);
			
			$this->rawDataReturn($date);
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
	}