<?php
	require_once('classes/controller/cs_ajax_controller.php');
	
	class cs_ajax_search_controller extends cs_ajax_controller {
		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);
		}

		public function actiongetAutocompleteSuggestions() {
			$db = $this->_environment->getDBConnector();
			
			list($search_word) = explode(' ', $this->_data['search_text']);
			
			/************************************************************************************
			 * Instead of joining with the index table and taking the matching count as reference,
			 * we just read from the search_word table
			************************************************************************************/
			/*
			$query = '
				SELECT
					sw_word
				FROM
					search_word
				LEFT JOIN
					search_index
				ON
					search_word.sw_id = search_index.si_sw_id
				WHERE
					sw_word LIKE "' . encode(AS_DB, $search_word) . '%"
				ORDER BY
					si_count
				LIMIT
					0, 20
				';
			*/
			$query = "
				SELECT
					sw_word
				FROM
					search_word
				WHERE
					sw_word LIKE '" . encode(AS_DB, $search_word) . "%';
			";
			$result = $db->performQuery($query);
			
			$words = array();
			foreach($result as $word) {
				$words[] = $word['sw_word'];
			}
			
			$this->setSuccessfullDataReturn($words);
			echo $this->_return;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function process() {
			// call parent
			parent::process();
		}
	}