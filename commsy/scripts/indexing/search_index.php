<?php
	chdir('../../');
	include_once('etc/cs_constants.php');
	include_once('etc/cs_stopwords.php');

	class Indexer {
		private $_index_items = array();
		private $_indexing = array();
		private $_regex = array();
		
		private $quiet = false;

		public function __construct() {
			mb_internal_encoding('UTF-8');

			include_once('etc/cs_config.php');
			$DB_Name     = $db['normal']['database'];
			$DB_Hostname = $db['normal']['host'];
			$DB_Username = $db['normal']['user'];
			$DB_Password = $db['normal']['password'];

			ini_set('max_execution_time', 0);
			ini_set('memory_limit', -1);
			ini_set('mysql.connect_timeout', -1);
			ini_set('default_socket_timeout', -1);

			error_reporting(E_ALL | E_STRICT);

			mysql_connect($DB_Hostname, $DB_Username, $DB_Password);
			mysql_select_db($DB_Name);
			mysql_set_charset('utf8');
			mysql_query("SET NAMES 'utf8'");

			// setup regex
			$this->setupRegex();
		}
		
		public function setQuiet($quiet)
		{
			$this->quiet = $quiet;
		}
		
		public function out($string)
		{
			if ($this->quiet == false) {
				echo $string;
			}
		}

		public function createDatabaseTables() {
			// add database tables
			$this->out("creating database tables, if not existing...");
			$sql = "
			CREATE TABLE IF NOT EXISTS `search_index` (
			`si_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`si_sw_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
			`si_item_id` int(11) NOT NULL DEFAULT '0',
			`si_item_type` varchar(15) NOT NULL,
			`si_count` smallint(5) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY (`si_id`),
			KEY `si_item_id` (`si_item_id`),
			KEY `si_sw_id` (`si_sw_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
			";
			mysql_query($sql);

			$sql = "
			CREATE TABLE IF NOT EXISTS `search_time` (
			`st_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
			`st_item_id` int(11) NOT NULL DEFAULT '0',
			`st_version_id` int(11) NULL DEFAULT NULL,
			`st_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`st_id`),
			UNIQUE KEY `st_item_version_id` ( `st_item_id` , `st_version_id` )
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
			";
			mysql_query($sql);

			$sql = "
			CREATE TABLE IF NOT EXISTS `search_word` (
			`sw_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
			`sw_word` varchar(32) CHARSET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
			PRIMARY KEY (`sw_id`),
			UNIQUE KEY `sw_word` (`sw_word`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
			";
			mysql_query($sql);
			$this->out("done\n");
		}

		public function truncateTables() {
			$this->out("truncating tables...");
			$sql = "TRUNCATE `search_index`;";
			mysql_query($sql);

			$sql = "TRUNCATE `search_time`;";
			mysql_query($sql);

			$sql = "TRUNCATE `search_word`;";
			mysql_query($sql);
			$this->out("done\n");
		}

		public function dropTables() {
			$this->out("dropping tables...");
			$sql = "DROP TABLE IF EXISTS `search_index`;";
			mysql_query($sql);

			$sql = "DROP TABLE IF EXISTS `search_time`;";
			mysql_query($sql);

			$sql = "DROP TABLE IF EXISTS `search_word`;";
			mysql_query($sql);
			$this->out("done\n");
		}

		public function generateIndex() {
			$this->out("getting information about already indexed items...");
			$search_time = array();
			$query = '
				SELECT
					search_time.st_item_id,
					search_time.st_version_id,
					search_time.st_date
				FROM
					search_time
			';
			$res = mysql_query($query);
			while($row = mysql_fetch_assoc($res)) {
				$search_time[$row['st_item_id']][$row['st_version_id']] = $row['st_date'];
			}
			$this->out("done\n");

			foreach($this->_index_items as $detail) {
				if($detail['callback'] !== null) {
					call_user_func_array($detail['callback'], array($this, &$this->_indexing, &$search_time));
				} else {
					$type = $detail['type'];
					$query = $detail['query'];
					
					$this->out("collection " . $type . " data..");
					$res = mysql_query($query);
					while($row = mysql_fetch_assoc($res)) {
						if(	!isset($search_time[$row['item_id']][$row['version_id']])
								|| $search_time[$row['item_id']][$row['version_id']] < $row['modification_date']) {

							$this->_indexing[] = array('db' => $row, 'type' => $type);
						}
					}
					$this->out("done\n");
				}

				$this->buildIndex();
				$this->_indexing = array();
			}
		}

		public function add($type, $query, $callback = null) {
			$this->_index_items[] = array(
				'type'		=> $type,
				'query'		=> $query,
				'callback'	=> $callback
			);
		}

		private function setupRegex() {
			$search = array();
			$replace = array();

			/* define some search/replace rules */
			// remove hyperlinks
			$search[] = '/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i'; $replace[] = " ";

			// remove all kind of special chars
			$search[] = '=[^a-zöäüß ]=u'; $replace[] = "  ";

			// remove all words with length below 3
			$search[] = '=\s[a-zöäüß]{1,2}\s=u'; $replace[] = " ";

			// remove stopwords
			global $stopwords;
			foreach($stopwords as $language => $word_list) {
				$search[] = "= " . implode(" | ", $word_list) . " =iu"; $replace[] = " ";
			}

			// remove multiple whitespaces
			$search[] = "=\s+=u"; $replace[] = " ";

			$this->_regex = array(
				'search'	=> $search,
				'replace'	=> $replace
			);
		}

		private function buildIndex() {
			$indexing_size = sizeof($this->_indexing);
			$this->out("found " . $indexing_size . " items\n");
			$this->out("\n");

			if( $indexing_size > 0 )
			{
				$this->out("getting word list...");
				$word_result = array();
				$running_new_id = 1;
				$query = '
					SELECT
						search_word.sw_id,
						search_word.sw_word
					FROM
						search_word
					ORDER BY
						search_word.sw_id
					DESC
				';
				$res = mysql_query($query);
				while($row = mysql_fetch_assoc($res)) {
					$word_result[md5($row['sw_word'])] = array(
						'sw_id'		=> $row['sw_id'],
						'sw_word'	=> $row['sw_word']
					);
				}
				
				if ( !empty($word_result) )
				{
					$lastEntry = current($word_result);
					$running_new_id = ((int) $lastEntry['sw_id']) + 1;
				}
				
				$this->out("done\n");

				$this->out("indexing...\n");
				$words = array();
				$index_structure = array();
				$word_update = array();
				$word_new = array();
				
				$this->out("building needed information...\n");
				$count = 1;
				$word_result_size = sizeof($word_result);

				foreach($this->_indexing as $result_row) {
					$this->out("processing item " . $count . "/" . $indexing_size . " ");

					$item_id = $result_row['db']['item_id'];
					$index_id = $result_row['db']['index_id'];
					$version_id = $result_row['db']['version_id'];
					$modification_date = $result_row['db']['modification_date'];
					$search_data = $result_row['db']['search_data'];

					$item_type_tmp = $result_row['type'];

					if(empty($item_type_tmp)) {
						$this->out("type is empty\n");
						continue;
					}

					// decode html entities
					$search_data = html_entity_decode($search_data, ENT_QUOTES, "UTF-8");

					// when decoding &nbsp; - it will be decoded to 160(0xa0) and not to 32, so it is not affected by trim()
					// see http://de3.php.net/manual/de/function.html-entity-decode.php
					if(mb_stristr($search_data, "\xc2\xa0", true, "UTF-8") !== false) {
						$search_data = str_replace("\xc2\xa0", "", $search_data);
					}

					// compress data
					$search_data = strip_tags($search_data);					// remove html tags
					$search_data = stripslashes($search_data);					// remove slashes
					$search_data = trim($search_data);							// trim
					$search_data = mb_strtolower($search_data, 'UTF-8');		// make lower case

					// replace
					$search_data = ' ' . str_replace(' ', '  ', $search_data) . ' ';
					$search_data = trim(preg_replace($this->_regex['search'], $this->_regex['replace'], $search_data));

					// put string of words into array
					$words = array();
					if(empty($search_data) === false) {
						$words = explode(' ', $search_data);
					}

					if(!isset($index_structure[$item_id][$version_id]['sw_ids'])) {
						$index_structure[$item_id][$version_id]['sw_ids'] = array();
					}

					// go through all words of item
					foreach($words as $word) {
						// trim word length to 32
						if(mb_strlen($word) > 32) {
							$word = mb_substr($word, 0, 32);
						}

						$md5 = md5($word);

						if(isset($word_result[$md5])) {
							$sw_id = $word_result[$md5]['sw_id'];

							// increase update or set 1 if not set - search_index table
							if(!isset($word_update[$sw_id]['item_ids'][$item_id])) {
								$word_update[$sw_id]['item_ids'][$item_id] = 1;
							} else {
								$word_update[$sw_id]['item_ids'][$item_id]++;
							}

							if(!in_array($sw_id, $index_structure[$item_id][$version_id]['sw_ids'])) {
								$index_structure[$item_id][$version_id]['sw_ids'][] = $sw_id;
							}
						} else {
							// append this word to the list of words in db
							$word_result[$md5] = array('sw_id' => $running_new_id, 'sw_word' => $word);
							$word_new[] = $md5;
							$word_result_size++;
							$index_structure[$item_id][$version_id]['sw_ids'][] = $running_new_id;

							$running_new_id++;
						}
					}

					if($item_type_tmp === 'inherit') {
						/*
						 * SEEMS TO BE OUTDATED
						 * this point is reached, when processing annotations
						* because annotations always belong to other items, there must be already an entry and the item type stays unchanged
						*/
						//$index_structure[$item_id]['type'] = $index_structure[$item_id]['type']
					} else {
						$index_structure[$item_id][$version_id]['type'] = $item_type_tmp;
					}

					$index_structure[$item_id][$version_id]['modification_date'] = $modification_date;
					$index_structure[$item_id][$version_id]['index_id'] = $index_id;
					
					$this->out($word_result_size . " words found\n");

					$count++;
				}

				// insert new words
				$this->out("writing words in database");
				$size = sizeof($word_new);
				$progress = 1;

				$count_index = 0;
				$query = '
				select count(*) from search_word;
				';
				if(mysql_query($query)) {
   			    	$count_index = mysql_result(mysql_query($query),0);
				}


				foreach($word_new as $word) {
					// perform insertion of new words
					$query = '
					INSERT INTO
					search_word(sw_word)
					VALUES
					';

					$query .= '("' . mysql_real_escape_string($word_result[$word]['sw_word']) . '")';

					if(!mysql_query($query)) {
						$this->out($query . "\n");
						$this->out(mysql_error()); exit;
					}



					$this->displayProgress($progress, $size);
					$progress++;

				}
				$this->out("done\n");
				
				// write index entries
				$this->out("writing index entries");
				$progress = 1;
				foreach($index_structure as $item_id => $version)
				{
					foreach($version as $version_id => $detail)
					{
						/*
						 * Delete all references to the current item.
						 * This also ensures, that only the latest version - if existing - is written to the index table.
						 * Indexing times are not affected!
						 */
						$query = '
							DELETE FROM
								search_index
							WHERE
								search_index.si_item_id = ' . mysql_real_escape_string($item_id) . '
						';
						if(!mysql_query($query)) {
							$this->out($query . "\n");
							$this->out(mysql_error()); exit;
						}
						
						$size = sizeof($detail['sw_ids']);

						if($size > 0) {
							$query = '
								INSERT INTO
									search_index(si_sw_id, si_item_id, si_item_type, si_count)
								VALUES
							';
							$empty = true;

							$count = 1;
							foreach($detail['sw_ids'] as $sw_id) {
								if($empty === false || ($count < $size && $count > 1)) $query .= ', ';
								$query .= '(' . mysql_real_escape_string(/*$count_index+*/$sw_id) . ', ' . mysql_real_escape_string($detail['index_id']) . ', "' . mysql_real_escape_string($detail['type']) . '", 1)';

								if($empty === true) $empty = false;

								$count++;
							}

							if(!mysql_query($query)) {
								$this->out($query . "\n");
								$this->out(mysql_error()); exit;
							}

							$this->displayProgress($progress, $indexing_size);
							$progress++;
						}
					}
				}
				$this->out("done\n");

				// update search_index
				$this->out("updating index entries");
				$progress = 1;
				$word_update_size = sizeof($word_update);
				foreach($word_update as $id => $detail) {
					foreach($detail['item_ids'] as $item_id => $inc) {
						$query = '
						UPDATE
						search_index
						SET
						si_count = si_count + ' . mysql_real_escape_string($inc) . '
						WHERE
						si_sw_id = ' . mysql_real_escape_string($id) . ' AND
						si_item_id = ' . mysql_real_escape_string($item_id) . '
						';
						if(!mysql_query($query)) {
							$this->out($query . "\n");
							$this->out(mysql_error()); exit;
						}
					}

					$this->displayProgress($progress, $word_update_size);
					$progress++;
				}
				$this->out("done\n");

				// write search time
				$this->out("writing search times");
				$progress = 1;
				foreach($index_structure as $item_id => $version) {
					foreach($version as $version_id => $detail) {
						if($version_id === NULL || $version_id === '') {
							$version_id = 'NULL';
						} else {
							$version_id = mysql_real_escape_string($version_id);
						}

						$query = '
						REPLACE INTO
						search_time(st_item_id, st_version_id, st_date)
						VALUES(
						' . mysql_real_escape_string($item_id) . ',
						' . $version_id . ',
						"' . mysql_real_escape_string($detail['modification_date']) . '"
						)
						';

						if(!mysql_query($query)) {
							$this->out($query . "\n");
							$this->out(mysql_error()); exit;
						}

						$this->displayProgress($progress, $indexing_size);
						$progress++;
					}
				}
				$this->out("done\n");
			}
			
			$this->out("\n\n");
		}

		private function displayProgress($count, $total) {
			if(phpversion() > '5.3.0') {
				$items_per_step = round($total / 80, 0, PHP_ROUND_HALF_DOWN);
			} else {
				$items_per_step = round($total / 80, 0);
			}

			if($items_per_step == 0) $items_per_step = 1;
			if($count % $items_per_step === 0) {
				$this->out('.');
			}
		}
	}

	$indexer = new Indexer();
	
	if(isset($argv)) {
		if (in_array('-q', $argv)) {
			$indexer->setQuiet(true);
		}
	}
	
	$indexer->createDatabaseTables();

	if(isset($argv)) {
		if(in_array('-t', $argv)) {
			$indexer->truncateTables();
		}

		if(in_array('-d', $argv)) {
			$indexer->dropTables();
			$indexer->createDatabaseTables();
		}
	}
	
	////////////////////////////
	////// Announcement ////////
	////////////////////////////
	$query = '
		SELECT
			announcement.item_id AS item_id,
			announcement.item_id AS index_id,
			NULL AS version_id,
			announcement.modification_date,
			CONCAT(announcement.title, " ", announcement.description, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			announcement
		LEFT JOIN
			user
		ON
			user.item_id = announcement.creator_id
		WHERE
			announcement.deletion_date IS NULL
	';
	$indexer->add(CS_ANNOUNCEMENT_TYPE, $query);
	
	////////////////////////////
	////// Sections ////////////
	////////////////////////////
	$query = '
		SELECT
			section.item_id AS item_id,
			section.material_item_id AS index_id,
			section.version_id AS version_id,
			section.modification_date,
			CONCAT(section.title, " ", section.description, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			section
		LEFT JOIN
			user
		ON
			user.item_id = section.creator_id
		WHERE
			section.deletion_date IS NULL
	';
	/*
	 * section.version_id = (
	 		SELECT
	 		MAX(s2.version_id)
	 		FROM
	 		section as s2
	 		WHERE
	 		s2.item_id = section.item_id
	 )
	*/
	$indexer->add(CS_SECTION_TYPE, $query);
	
	////////////////////////////
	////// Materials ///////////
	////////////////////////////
	$query = '
		SELECT
			materials.item_id AS item_id,
			materials.item_id AS index_id,
			materials.version_id AS version_id,
			materials.modification_date,
			CONCAT(materials.title, " ", materials.description, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			materials
		LEFT JOIN
			user
		ON
			user.item_id = materials.creator_id
		WHERE
			materials.deletion_date IS NULL
	';
	/*
	 * materials.version_id = (
		SELECT
		MAX(m2.version_id)
		FROM
		materials as m2
		WHERE
		m2.item_id = materials.item_id
		)
	 */
	$indexer->add(CS_MATERIAL_TYPE, $query);

	////////////////////////////
	////// Institutions ////////
	////////////////////////////
	$query = '
		SELECT
			labels.item_id AS item_id,
			labels.item_id AS index_id,
			NULL AS version_id,
			labels.modification_date,
			CONCAT(labels.name, " ", labels.description, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			labels
		LEFT JOIN
			user
		ON
			user.item_id = labels.creator_id
		WHERE
			labels.type = "institution" AND
			labels.deletion_date IS NULL
	';
	$indexer->add(CS_INSTITUTION_TYPE, $query);
	
	////////////////////////////
	////// Topics //////////////
	////////////////////////////
	$query = '
		SELECT
			labels.item_id AS item_id,
			labels.item_id AS index_id,
			labels.modification_date,
			NULL AS version_id,
			CONCAT(labels.name, " ", labels.description, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			labels
		LEFT JOIN
			user
		ON
			user.item_id = labels.creator_id
		WHERE
			labels.type = "topic" AND
			labels.deletion_date IS NULL
	';
	$indexer->add(CS_TOPIC_TYPE, $query);
	
	////////////////////////////
	////// User ////////////////
	////////////////////////////
	$query = '
		SELECT
			user.item_id AS item_id,
			user.item_id AS index_id,
			NULL AS version_id,
			user.modification_date,
			CONCAT(user.user_id, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			user
		WHERE
			user.deletion_date IS NULL
	';
	$indexer->add(CS_USER_TYPE, $query);
	
	////////////////////////////
	////// Todos ///////////////
	////////////////////////////
	$query = '
		SELECT
			todos.item_id AS item_id,
			todos.item_id AS index_id,
			NULL AS version_id,
			todos.modification_date,
			CONCAT(todos.title, " ", todos.description, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			todos
		LEFT JOIN
			user
		ON
			user.item_id = todos.creator_id
		WHERE
			todos.deletion_date IS NULL
	';
	$indexer->add(CS_TODO_TYPE, $query);
	
	////////////////////////////
	////// Steps ///////////////
	////////////////////////////
	$query = '
		SELECT
			step.item_id AS item_id,
			step.todo_item_id AS index_id,
			NULL AS version_id,
			step.modification_date,
			CONCAT(step.title, " ", step.description, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			step
		LEFT JOIN
			user
		ON
			user.item_id = step.creator_id
		WHERE
			step.deletion_date IS NULL
	';
	$indexer->add(CS_STEP_TYPE, $query);
	
	////////////////////////////
	////// Dates ///////////////
	////////////////////////////
	$query = '
		SELECT
			dates.item_id AS item_id,
			dates.item_id AS index_id,
			NULL AS version_id,
			dates.modification_date,
			CONCAT(dates.title, " ", dates.description, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			dates
		LEFT JOIN
			user
		ON
			user.item_id = dates.creator_id
		WHERE
			dates.deletion_date IS NULL
	';
	$indexer->add(CS_DATE_TYPE, $query);
	
	////////////////////////////
	////// Discussions /////////
	////////////////////////////
	$query = '
		SELECT
			discussions.item_id AS item_id,
			discussions.item_id AS index_id,
			NULL AS version_id,
			discussions.modification_date,
			CONCAT(discussions.title, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			discussions
		LEFT JOIN
			user
		ON
			user.item_id = discussions.creator_id
		WHERE
			discussions.deletion_date IS NULL
	';
	$indexer->add(CS_DISCUSSION_TYPE, $query);
	
	////////////////////////////
	////// Discussion Articles /
	////////////////////////////
	$query = '
		SELECT
			discussionarticles.item_id AS item_id,
			discussionarticles.discussion_id AS index_id,
			NULL AS version_id,
			discussionarticles.modification_date,
			CONCAT(discussionarticles.subject, " ", discussionarticles.description, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			discussionarticles
		LEFT JOIN
			user
		ON
			user.item_id = discussionarticles.creator_id
		WHERE
			discussionarticles.deletion_date IS NULL
	';
	$indexer->add(CS_DISCARTICLE_TYPE, $query);
	
	////////////////////////////
	////// Groups //////////////
	////////////////////////////
	$indexer->add(CS_GROUP_TYPE, '', 'updateGroupIndex');
	
	function updateGroupIndex($indexer, $indexing, $search_time) {
		$indexer->out("collecting " . CS_GROUP_TYPE . " data..");
	
		// process the group itself
		$query = '
			SELECT
				labels.item_id AS item_id,
				labels.item_id AS index_id,
				NULL AS version_id,
				labels.modification_date,
				CONCAT(labels.name, " ", labels.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				labels
			LEFT JOIN
				user
			ON
				user.item_id = labels.creator_id
			WHERE
				labels.type = "group" AND
				labels.deletion_date IS NULL
		';
		$group_data = array();
		$res = mysql_query($query);
		while($row = mysql_fetch_assoc($res)) {
			$group_data[] = $row;
		}
	
		// process members of groups
		$user_data = array();
		$query = '
			SELECT
				labels.item_id AS item_id,
				CONCAT(user.firstname, " ", user.lastname) AS search_data
			FROM
				labels
			LEFT JOIN
				link_items AS l1
			ON
				l1.first_item_id = labels.item_id AND
				l1.second_item_type = "user"
			LEFT JOIN
				user
			ON
				l1.second_item_id = user.item_id
			WHERE
				labels.type = "group" AND
				labels.deletion_date IS NULL AND
				user.item_id IS NOT NULL
		';
		$res = mysql_query($query);
		while($row = mysql_fetch_assoc($res)) {
			$user_data[$row['item_id']][] = $row['search_data'];
		}
	
		$query = '
			SELECT
				labels.item_id AS item_id,
				CONCAT(user.firstname, " ", user.lastname) AS search_data
			FROM
				labels
			LEFT JOIN
				link_items AS l2
			ON
				l2.second_item_id = labels.item_id AND
				l2.first_item_type = "user"
			LEFT JOIN
				user
			ON
				l2.first_item_id = user.item_id
			WHERE
				labels.type = "group" AND
				labels.deletion_date IS NULL AND
				user.item_id IS NOT NULL
		';
		$res = mysql_query($query);
		while($row = mysql_fetch_assoc($res)) {
			$user_data[$row['item_id']][] = $row['search_data'];
		}
	
		// merge together
		foreach($group_data as $group) {
			if(	!isset($search_time[$group['item_id']][$group['version_id']])
					|| $search_time[$group['item_id']][$group['version_id']] < $group['modification_date']) {
	
				if(isset($user_data[$group['item_id']])) {
					$group['search_data'] .= " " . implode(" ", $user_data[$group['item_id']]);
	
					$indexing[] = array('db' => $group, 'type' => CS_GROUP_TYPE);
				}
			}
		}
	
		unset($group_data);
		unset($user_data);
		
		$indexer->out("done\n");
	}
	
	////////////////////////////
	////// Tasks ///////////////
	////////////////////////////
	/*
	 $query = '
	SELECT
	tasks.item_id AS item_id,
	tasks.item_id AS index_id,
	NULL AS version_id,
	tasks.modification_date,
	CONCAT(tasks.title, " ", user.firstname, " ", user.lastname) AS search_data
	FROM
	tasks
	LEFT JOIN
	user
	ON
	user.item_id = tasks.creator_id
	WHERE
	tasks.deletion_date IS NULL
	';
	$indexer->add(CS_TASK_TYPE, $query);
	*/
	
	////////////////////////////
	////// Buzzwords ///////////
	////////////////////////////
	$query = '
		SELECT
			labels.item_id AS item_id,
			labels.item_id AS index_id,
			NULL AS version_id,
			labels.modification_date,
			CONCAT(labels.name, " ", labels.description, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			labels
		LEFT JOIN
			user
		ON
			user.item_id = labels.creator_id
		WHERE
			labels.type = "buzzword" AND
			labels.deletion_date IS NULL
	';
	$indexer->add(CS_BUZZWORD_TYPE, $query);
	
	////////////////////////////
	////// Tags ////////////////
	////////////////////////////
	$query = '
		SELECT
			tag.item_id AS item_id,
			tag.item_id AS index_id,
			NULL AS version_id,
			tag.modification_date,
			CONCAT(tag.title, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			tag
		LEFT JOIN
			user
		ON
			user.item_id = tag.creator_id
		WHERE
			tag.deletion_date IS NULL
	';
	$indexer->add(CS_TAG_TYPE, $query);
	
	////////////////////////////
	////// Group Rooms /////////
	////////////////////////////
	$query = '
		SELECT
			room.item_id AS item_id,
			room.item_id AS index_id,
			NULL AS version_id,
			room.modification_date,
			CONCAT(room.title, " ", room.room_description, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			room
		LEFT JOIN
			user
		ON
			user.item_id = room.creator_id
		WHERE
			room.type = "grouproom" AND
			room.deletion_date IS NULL
	';
	$indexer->add(CS_GROUPROOM_TYPE, $query);
	
	////////////////////////////
	////// Annotations /////////
	////////////////////////////
	$query = '
		SELECT
			annotations.item_id AS item_id,
			annotations.linked_item_id AS index_id,
			NULL AS version_id,
			annotations.modification_date,
			CONCAT(annotations.title, " ", annotations.description, " ", user.firstname, " ", user.lastname) AS search_data
		FROM
			annotations
		LEFT JOIN
			user
		ON
			user.item_id = annotations.creator_id
		WHERE
			annotations.deletion_date IS NULL
	';
	$indexer->add(CS_ANNOTATION_TYPE, $query);
	
	$indexer->generateIndex();