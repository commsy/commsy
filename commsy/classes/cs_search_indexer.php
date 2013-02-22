<?php
class cs_search_indexer {
	private $_index_items = array();
	private $_indexing = array();
	private $_regex = array();

	public function __construct() {
		mb_internal_encoding('UTF-8');
			
		// setup regex
		$this->setupRegex();
	}

	public function generateIndex() {
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
			
		foreach($this->_index_items as $detail) {
			if($detail['callback'] !== null) {
				call_user_func_array($detail['callback'], array(&$this->_indexing, &$search_time));
			} else {
				$type = $detail['type'];
				$query = $detail['query'];
					
				$res = mysql_query($query);
				while($row = mysql_fetch_assoc($res)) {
					if(	!isset($search_time[$row['item_id']][$row['version_id']])
							|| $search_time[$row['item_id']][$row['version_id']] < $row['modification_date']) {
							
						$this->_indexing[] = array('db' => $row, 'type' => $type);
					}
				}
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
			
		// remove all words with length below 4
		$search[] = '=\s[a-zöäüß]{1,3}\s=u'; $replace[] = " ";
			
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
		if($indexing_size > 0) {
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
			if(isset($row)) $running_new_id = $row['sw_id'] + 1;
			$words = array();
			$index_structure = array();
			$word_update = array();
			$word_new = array();
			
			$count = 1;
			$word_result_size = sizeof($word_result);
				
			foreach($this->_indexing as $result_row) {		
				$item_id = $result_row['db']['item_id'];
				$index_id = $result_row['db']['index_id'];
				$version_id = $result_row['db']['version_id'];
				$modification_date = $result_row['db']['modification_date'];
				$search_data = $result_row['db']['search_data'];
					
				$item_type_tmp = $result_row['type'];

				if(empty($item_type_tmp)) {
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
				
				$count++;
			}
				
			// insert new words
			$size = sizeof($word_new);
			$progress = 1;
				
			foreach($word_new as $word) {
				// perform insertion of new words
				$query = '
				INSERT INTO
				search_word(sw_word)
				VALUES
				';

				$query .= '("' . mysql_real_escape_string($word_result[$word]['sw_word']) . '")';

				if(!mysql_query($query)) {
					echo $query . "\n";
					echo mysql_error(); exit;
				}

				$this->displayProgress($progress, $size);
				$progress++;

			}
				
			// write index entries
			$progress = 1;
			foreach($index_structure as $item_id => $version) {
				foreach($version as $version_id => $detail) {
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
							$query .= '(' . mysql_real_escape_string($sw_id) . ', ' . mysql_real_escape_string($detail['index_id']) . ', "' . mysql_real_escape_string($detail['type']) . '", 1)';

							if($empty === true) $empty = false;

							$count++;
						}

						if(!mysql_query($query)) {
							echo $query . "\n";
							echo mysql_error(); exit;
						}

						$this->displayProgress($progress, $indexing_size);
						$progress++;
					}
				}
			}
				
			// update search_index
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
						echo mysql_error(); exit;
					}
				}

				$this->displayProgress($progress, $word_update_size);
				$progress++;
			}
				
			// write search time
			$progress = 1;
			foreach($index_structure as $item_id => $version) {
				foreach($version as $version_id => $detail) {
					if($version_id === NULL || $version_id === '') {
						$version_id = 'NULL';
					} else {
						$version_id = mysql_real_escape_string($version_id);
					}
						
					$query = '
					INSERT INTO
					search_time(st_item_id, st_version_id, st_date)
					VALUES(
					' . mysql_real_escape_string($item_id) . ',
					' . $version_id . ',
					"' . mysql_real_escape_string($detail['modification_date']) . '"
					)
					';

					if(!mysql_query($query)) {
						echo mysql_error(); exit;
					}

					$this->displayProgress($progress, $indexing_size);
					$progress++;
				}
			}
		}
	}
}