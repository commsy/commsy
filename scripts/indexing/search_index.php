<?php
	function displayProgress($count, $total) {
		$items_per_step = round($total / 80, 0, PHP_ROUND_HALF_DOWN);
		if($count % $items_per_step === 0) {
			echo '.';
		}
	}
	
	chdir('../../');
	
	include_once('etc/cs_config.php');
	$DB_Name     = $db['normal']['database'];
	$DB_Hostname = $db['normal']['host'];
	$DB_Username = $db['normal']['user'];
	$DB_Password = $db['normal']['password'];
	
	ini_set('max_execution_time', 0);
	error_reporting(E_ALL | E_STRICT);
	
	mysql_connect($DB_Hostname, $DB_Username, $DB_Password);
	mysql_select_db($DB_Name);
	
	include_once('etc/cs_constants.php');
	
	// setup commsy-environment
	include_once('classes/cs_environment.php');
	$environment = new cs_environment();
	
	// add database tables
	echo "creating database tables, if not existing...";
	$sql = "
		CREATE TABLE IF NOT EXISTS `search_index` (
		  `si_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
		  `si_sw_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
		  `si_item_id` int(11) NOT NULL DEFAULT '0',
		  `si_item_type` varchar(15) NOT NULL,
		  `si_count` smallint(5) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`si_id`),
		  UNIQUE KEY `un_si_sw_id` (`si_item_id`,`si_sw_id`,`si_item_type`),
		  KEY `si_sw_id` (`si_sw_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
	";
	mysql_query($sql);
	
	$sql = "
		CREATE TABLE IF NOT EXISTS `search_time` (
		  `st_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
		  `st_item_id` int(11) NOT NULL DEFAULT '0',
		  `st_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  PRIMARY KEY (`st_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
	";
	mysql_query($sql);
	
	$sql = "
		CREATE TABLE IF NOT EXISTS `search_word` (
		  `sw_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
		  `sw_word` varchar(32) NOT NULL DEFAULT '',
		  PRIMARY KEY (`sw_id`),
		  KEY `sw_word` (`sw_word`),
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
	";
	mysql_query($sql);
	echo "done\n";
	
	if(in_array('-t', $argv)) {
		echo "truncating tables...";
		$sql = "TRUNCATE `search_index`;";
		mysql_query($sql);
		
		$sql = "TRUNCATE `search_time`;";
		mysql_query($sql);
		
		$sql = "TRUNCATE `search_word`;";
		mysql_query($sql);
		echo "done\n";
	}
	
	//////////////////////////////
	////// INDEXING //////////////
	//////////////////////////////
	$indexing = array();
	
	// announcement
	echo "collecting announcement data...";
	$query = '
			SELECT
				announcement.item_id,
				search_time.st_id,
				CONCAT(announcement.title, " ", announcement.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				announcement
			LEFT JOIN
				user
			ON
				user.item_id = announcement.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = announcement.item_id
			WHERE
				(
					search_time.st_id IS NULL OR
					announcement.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_ANNOUNCEMENT_TYPE);
	}
	echo "done\n";
	
	// sections
	echo "collecting section data...";
	$query = '
			SELECT
				section.material_item_id AS item_id,
				items.type AS item_type,
				search_time.st_id,
				CONCAT(section.title, " ", section.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				section
			LEFT JOIN
				user
			ON
				user.item_id = section.creator_id
			LEFT JOIN
				items
			ON
				section.material_item_id = items.item_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = section.material_item_id
			WHERE
				(
					search_time.st_id IS NULL OR
					section.modification_date > search_time.st_date
				) AND
				section.version_id = (
					SELECT
						MAX(s2.version_id)
					FROM
						section as s2
					WHERE
						s2.item_id = section.item_id
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_SECTION_TYPE);
	}
	echo "done\n";
	
	// materials
	echo "collecting material data...";
	$query = '
			SELECT
				materials.item_id,
				search_time.st_id,
				CONCAT(materials.title, " ", materials.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				materials
			LEFT JOIN
				user
			ON
				user.item_id = materials.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = materials.item_id
			WHERE
				(
					search_time.st_id IS NULL OR
					materials.modification_date > search_time.st_date
				) AND
				materials.version_id = (
					SELECT
						MAX(m2.version_id)
					FROM
						materials as m2
					WHERE
						m2.item_id = materials.item_id
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_MATERIAL_TYPE);
	}
	echo "done\n";
	
	// institutions
	echo "collecting institution data...";
	$query = '
			SELECT
				labels.item_id,
				search_time.st_id,
				CONCAT(labels.name, " ", labels.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				labels
			LEFT JOIN
				user
			ON
				user.item_id = labels.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = labels.item_id

			WHERE
				labels.type = "institution" AND
				(
					search_time.st_id IS NULL OR
					labels.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_INSTITUTION_TYPE);
	}
	echo "done\n";
	
	// topics
	echo "collecting topic data...";
	$query = '
			SELECT
				labels.item_id,
				search_time.st_id,
				CONCAT(labels.name, " ", labels.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				labels
			LEFT JOIN
				user
			ON
				user.item_id = labels.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = labels.item_id

			WHERE
				labels.type = "topic" AND
				(
					search_time.st_id IS NULL OR
					labels.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_TOPIC_TYPE);
	}
	echo "done\n";
	
	// user
	echo "collecting user data...";
	$query = '
			SELECT
				user.item_id,
				search_time.st_id,
				CONCAT(user.user_id, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				user
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = user.item_id

			WHERE
				(
					search_time.st_id IS NULL OR
					user.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_USER_TYPE);
	}
	echo "done\n";
	
	// todos
	echo "collecting todo data...";
	$query = '
			SELECT
				todos.item_id,
				search_time.st_id,
				CONCAT(todos.title, " ", todos.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				todos
			LEFT JOIN
				user
			ON
				user.item_id = todos.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = todos.item_id
			WHERE
				(
					search_time.st_id IS NULL OR
					todos.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_TODO_TYPE);
	}
	echo "done\n";
	
	// steps
	echo "collecting step data...";
	$query = '
			SELECT
				step.item_id,
				search_time.st_id,
				CONCAT(step.title, " ", step.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				step
			LEFT JOIN
				user
			ON
				user.item_id = step.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = step.item_id
			WHERE
				(
					search_time.st_id IS NULL OR
					step.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_STEP_TYPE);
	}
	echo "done\n";
	
	// dates
	echo "collecting date data...";
	$query = '
			SELECT
				dates.item_id,
				search_time.st_id,
				CONCAT(dates.title, " ", dates.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				dates
			LEFT JOIN
				user
			ON
				user.item_id = dates.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = dates.item_id
			WHERE
				(
					search_time.st_id IS NULL OR
					dates.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_DATE_TYPE);
	}
	echo "done\n";
	
	// discussion
	echo "collecting discussion data...";
	$query = '
			SELECT
				discussions.item_id,
				search_time.st_id,
				CONCAT(discussions.title, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				discussions
			LEFT JOIN
				user
			ON
				user.item_id = discussions.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = discussions.item_id
			WHERE
				(
					search_time.st_id IS NULL OR
					discussions.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_DISCUSSION_TYPE);
	}
	echo "done\n";
	
	// groups
	echo "collecting group data...";
	$query = '
			SELECT
				labels.item_id,
				search_time.st_id,
				CONCAT(labels.name, " ", labels.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				labels
			LEFT JOIN
				user
			ON
				user.item_id = labels.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = labels.item_id

			WHERE
				labels.type = "group" AND
				(
					search_time.st_id IS NULL OR
					labels.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_GROUP_TYPE);
	}
	echo "done\n";
	
	// groups <=> user
	echo "collecting group <=> user relationships...";
	$user_manager = $environment->getUserManager();
	foreach($indexing as &$index) {
		if($index['type'] !== CS_GROUP_TYPE) continue;
		
		$group_id = $index['db']['item_id'];
		
		$query = '
			SELECT DISTINCT
				CONCAT(user.firstname, " ", user.lastname) AS search_data
			FROM
				user
			LEFT JOIN
				link_items AS linkA
			ON
				linkA.first_item_id = user.item_id AND linkA.second_item_type = "group"
			LEFT JOIN
				link_items AS linkB
			ON
				linkB.second_item_id = user.item_id AND linkB.first_item_type = "group"
			WHERE
				(
					linkA.first_item_id = ' . mysql_real_escape_string($group_id) . ' OR
					linkA.second_item_id = ' . mysql_real_escape_string($group_id) . '
				) OR (
					linkB.first_item_id = ' . mysql_real_escape_string($group_id) . ' OR
					linkB.second_item_id = ' . mysql_real_escape_string($group_id) . '
				)
		';
		$res = mysql_query($query);
		while($row = mysql_fetch_assoc($res)) {
			$index['db']['search_data'] .= ' ' . $row['search_data'];
		}
	}
	echo "done\n";
	
	// discussion articles
	echo "collecting discussion articles data...";
	$query = '
			SELECT
				discussionarticles.discussion_id AS item_id,
				search_time.st_id,
				CONCAT(discussionarticles.subject, " ", discussionarticles.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				discussionarticles
			LEFT JOIN
				user
			ON
				user.item_id = discussionarticles.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = discussionarticles.discussion_id
			WHERE
				(
					search_time.st_id IS NULL OR
					discussionarticles.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_DISCUSSION_TYPE);
	}
	echo "done\n";
	
	// tasks
	echo "collecting tasks data...";
	$query = '
			SELECT
				tasks.item_id,
				search_time.st_id,
				CONCAT(tasks.title, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				tasks
			LEFT JOIN
				user
			ON
				user.item_id = tasks.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = tasks.item_id
			WHERE
				(
					search_time.st_id IS NULL OR
					tasks.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_TASK_TYPE);
	}
	echo "done\n";
	
	// buzzwords
	echo "collecting buzzword data...";
	$query = '
			SELECT
				labels.item_id,
				search_time.st_id,
				CONCAT(labels.name, " ", labels.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				labels
			LEFT JOIN
				user
			ON
				user.item_id = labels.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = labels.item_id

			WHERE
				labels.type = "buzzword" AND
				(
					search_time.st_id IS NULL OR
					labels.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_BUZZWORD_TYPE);
	}
	echo "done\n";
	
	// tags
	echo "collecting tag data...";
	$query = '
			SELECT
				tag.item_id,
				search_time.st_id,
				CONCAT(tag.title, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				tag
			LEFT JOIN
				user
			ON
				user.item_id = tag.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = tag.item_id
			WHERE
				(
					search_time.st_id IS NULL OR
					tag.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_TAG_TYPE);
	}
	echo "done\n";
	
	// group rooms
	echo "collecting group room data...";
	$query = '
			SELECT
				room.item_id,
				search_time.st_id,
				CONCAT(room.title, " ", room.room_description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				room
			LEFT JOIN
				user
			ON
				user.item_id = room.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = room.item_id
			WHERE
				room.type = "grouproom" AND
				(
					search_time.st_id IS NULL OR
					room.modification_date > search_time.st_date
				)
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => CS_GROUPROOM_TYPE);
	}
	echo "done\n";
	
	// annotations
	echo "collecting annotation data...";
	$query = '
			SELECT
				annotations.linked_item_id AS item_id,
				search_time.st_id,
				CONCAT(annotations.title, " ", annotations.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				annotations
			LEFT JOIN
				user
			ON
				user.item_id = annotations.creator_id
			LEFT JOIN
				search_time
			ON
				search_time.st_item_id = annotations.linked_item_id
			WHERE
				search_time.st_id IS NULL OR
				annotations.modification_date > search_time.st_date
		';
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)) {
		$indexing[] = array('db' => $row, 'type' => 'inherit');
	}
	echo "done\n";
	
	echo "found " . sizeof($indexing) . " items\n";
	echo "\n";
	echo "indexing...\n";
	
	// every entry in result will be a single item
   	
   	$search = array();
	$replace = array();
	
	// define some search/replace rules
	include('etc/cs_stopwords.php');
   	foreach($stopwords as $language => $word_list) {
   		$search[] = "= " . implode(" | ", $word_list) . " =i";			$replace[] = " ";	// remove stopwords
   	}
	$search[] = "=&(.*?);=";											$replace[] = " ";	// remove special html codings
	$search[] = "=\\(:(.*?):\\)=";										$replace[] = " ";	// remove commsy tags
	$search[] = '=([\W0-9_]*\s[\W0-9_]*)=';								$replace[] = " ";	// remove words of special chars and numbers
	$search[] = '=\s(\w*["()!:0-9/.,-]+\w*)\s=';						$replace[] = " ";	// remove words containing special chars and numbers
	$search[] = "=(\s[a-zäöüß]{1,2})\s=";								$replace[] = " ";	// remove words with length below 3
	$search[] = "=(\s[a-zäöüß]{1,2})\s=";								$replace[] = " ";	// remove words with length below 3
	$search[] = "=\s+=";												$replace[] = " ";	// remove multiple whitespaces
	
	$words = array();
	$index_structure = array();
	$word_result = array();
	$word_update = array();
	$running_new_id = 1;
	
	echo "building needed information...\n";
	$count = 1;
	foreach($indexing as $result_row) {
		echo "processing item " . $count . "/" . sizeof($indexing) . " ";
		
		$item_id = $result_row['db']['item_id'];
		$searchtime_id = $result_row['db']['st_id'];
		$search_data = $result_row['db']['search_data'];
		
		$item_type_tmp = $result_row['type'];
		
		if(empty($item_type_tmp)) {
			echo "type is empty\n";
			continue;
		}
		
		$special = array(
			'&auml;' => utf8_decode('ä'),
			'&Auml;' => utf8_decode('Ä'),
			'&szlig;' => utf8_decode('ß'),
			'&ouml;' => utf8_decode('ö'),
			'&Ouml;' => utf8_decode('Ö'),
			'&Uuml;' => utf8_decode('Ü'),
			'&uuml;' => utf8_decode('ü')
		);
		$search_data = str_replace(array_keys($special), array_values($special), $search_data);
		
		// compress data
		$search_data = strip_tags($search_data);							// remove html tags
		$search_search_data = stripslashes($search_data);					// remove slashes
		$search_data = trim($search_data);									// trim
		$search_data = mb_strtolower($search_data);							// make lower case
		
		$before = $search_data;
		
		// replace
		$search_data = ' ' . str_replace(' ', '  ', $search_data) . ' ';
		$search_data = trim(preg_replace($search, $replace, $search_data));
		
		// put string of words into array
		$words = explode(' ', $search_data);
		
			/*
			foreach($words as $word) {
				if(mb_strstr($word, 'ganzadsfasdfasdfst')) {
					echo "\n\n\n\n";
					echo "ausgangssatz: ->" . $before . "<-\n\n";
					echo "aus datenbank: ->" . $result_row['db']['search_data'] . "<-\n\n";
					echo "\n";
					echo "wende regeln an: \n";
					
					$search = array_splice($search, -5, 5);
					
					foreach($search as $s) {
						$before = trim(preg_replace($s, ' ', $before));
						echo $s . " => \n" . $before . "\n\n\n";
						
					}
					
					echo "\n\n\n\n";
					exit;
				}
			}
			*/
		
		if(!isset($index_structure[$item_id]['sw_ids'])) {
			$index_structure[$item_id]['sw_ids'] = array();
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
				
				if(!in_array($sw_id, $index_structure[$item_id]['sw_ids'])) {
					$index_structure[$item_id]['sw_ids'][] = $sw_id;
				}
			} else {
				// append this word to the list of words in db
				$word_result[$md5] = array('sw_id' => $running_new_id, 'sw_word' => $word);
				$index_structure[$item_id]['sw_ids'][] = $running_new_id;
				
				$running_new_id++;
			}
		}
		
		if($item_type_tmp === 'inherit') {
			/*
			 * this point is reached, when processing annotations
			 * because annotations always belong to other items, there must be alreandy an entry and the item type stays unchanged
			 */
			//$index_structure[$item_id]['type'] = $index_structure[$item_id]['type']
		} else {
			$index_structure[$item_id]['type'] = $item_type_tmp;
		}
		
		echo sizeof($word_result) . " words found\n";
		
		$count++;
		
		//if($count === 100000) break;
	}
		
		// insert new words
		echo "writing words in database";
		$size = sizeof($word_result);
		$progress = 1;
		foreach($word_result as $word) {
			// perform insertion of new words
			$query = '
				INSERT INTO
					search_word(sw_word)
				VALUES
			';
			
			$query .= '("' . mysql_real_escape_string($word['sw_word']) . '")';
			
			if(!mysql_query($query)) {
				echo $query . "\n";
				echo mysql_error(); exit;
			}
			
			displayProgress($progress, $size);
			$progress++;
			
		}
		echo "done\n";
		
		// write index entries
		echo "writing index entries";
		$progress = 1;
		foreach($index_structure as $item_id => $detail) {			
			$size = sizeof($detail['sw_ids']);
			
			$query = '
				INSERT INTO
					search_index(si_sw_id, si_item_id, si_item_type, si_count)
				VALUES
			';
			$empty = true;
			
			$count = 1;
			foreach($detail['sw_ids'] as $sw_id) {
				if($empty === false || ($count < $size && $count > 1)) $query .= ', ';
				$query .= '(' . mysql_real_escape_string($sw_id) . ', ' . mysql_real_escape_string($item_id) . ', "' . mysql_real_escape_string($detail['type']) . '", 1)';
				
				if($empty === true) $empty = false;
				
				$count++;
			}
			
			if(!mysql_query($query)) {
				echo $query . "\n";
				echo mysql_error(); exit;
			}
			
			displayProgress($progress, sizeof($index_structure));
			$progress++;
		}
		echo "done\n";
		
		// update search_index
		echo "updating index entries";
		$progress = 1;
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
					echo mysql_error(); exit;
				}
			}
			
			displayProgress($progress, sizeof($word_update));
			$progress++;
		}
		echo "done\n";
		
		// write search time
		echo "writing search times";
		$progress = 1;
		foreach($index_structure as $item_id => $detail) {
			$query = '
				INSERT INTO
					search_time(st_item_id, st_date)
				VALUES(
					' . mysql_real_escape_string($item_id) . ',
					"' . mysql_real_escape_string(date('Y-m-d H:i:s', time())) . '"
				)
			';
			if(!mysql_query($query)) {
				echo mysql_error(); exit;
			}
			
			displayProgress($progress, sizeof($index_structure));
			$progress++;
		}
		echo "done\n";