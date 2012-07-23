<?php
	require_once('classes/controller/cs_list_controller.php');

	class cs_date_index_controller extends cs_list_controller {
		private $_display_mode = '';
		private $_presentation_mode = '';
		private $_selected_status = '';
		private $_available_color_array = array('#999999','#CC0000','#FF6600','#FFCC00','#FFFF66','#33CC00','#00CCCC','#3366FF','#6633FF','#CC33CC');
		private $_calendar = array();
		
		const DATEDEFAULTHEIGHT = 16;
		const CELLDEFAULTHEIGHT = 41;

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			// set display mode
			$this->setDisplayMode();

			// set selected status
			$this->setSelectedStatus();

			$this->_tpl_file = "date_" . $this->_display_mode;
			if ($this->_display_mode == 'calendar_month'){
				$this->_tpl_file = "date_calendar";
			}

			// this will enable processing of additional restriction texts
			$this->_additional_selects = true;
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();

			// assign rubric to template
			$this->assign('room', 'rubric', CS_DATE_TYPE);
		}

		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/

		/**
		 * INDEX
		 */
		public function actionIndex() {
		    $current_context = $this->_environment->getCurrentContextItem();
		    $current_user = $this->_environment->getCurrentUserItem();
		    $hash_manager = $this->_environment->getHashManager();
		    $params = $this->_environment->getCurrentParameterArray();
			$translator = $this->_environment->getTranslationObject();
		    $ical_url = '';
		    $ical_url .= $_SERVER['HTTP_HOST'];
		    global $c_single_entry_point;
		    $ical_url .= str_replace($c_single_entry_point,'ical.php',$_SERVER['PHP_SELF']);
		    $ical_url .= '?cid='.$_GET['cid'].'&amp;hid='.$hash_manager->getICalHashForUser($current_user->getItemID()).LF;
			$this->assign('date','ical_adress', $ical_url);

			if($this->_display_mode === "list") {
				// init list params
				$this->initListParameters(CS_DATE_TYPE);

				// perform list options
				$this->performListOption(CS_DATE_TYPE);

				// get list content
				$list_content = $this->getListContent();



				// assign to template
				$this->assign('date','list_parameters', $this->_list_parameter_arrray);
				$this->assign('list','perspective_rubric_entries', $this->_perspective_rubric_array);
				$this->assign('list','page_text_fragments',$this->_page_text_fragment_array);
				$this->assign('list','browsing_parameters',$this->_browsing_icons_parameter_array);
				$this->assign('list','sorting_parameters',$this->getSortingParameterArray());
				$this->assign('list','list_entries_parameter',$this->getListEntriesParameterArray());
				$this->assign('list','restriction_buzzword_link_parameters',$this->getRestrictionBuzzwordLinkParameters());
				$this->assign('list','restriction_tag_link_parameters',$this->getRestrictionTagLinkParameters());
				$this->assign('list','restriction_text_parameters',$this->_getRestrictionTextAsHTML());
				$this->assign('date','list_content', $list_content);
			} elseif($this->_display_mode === "calendar" or $this->_display_mode === "calendar_month") {
				// set presentation mode
				if(isset($_GET['presentation_mode'])) {
					$this->_presentation_mode = $_GET['presentation_mode'];
				} else {
					if($this->_display_mode === "calendar" ){
						$this->_presentation_mode = 'week';
					}else{
						$this->_presentation_mode = 'month';
					}
				}

				// get calendar content
				$calendar_content = $this->getCalendarContent();

				// assign to template
				$this->assign("date", "calendar_content", $calendar_content);
			}

		}

		private function getWeekDayOfDate($day, $month, $year) {
			// 0 - Sonntag, 6 - Samstag
			$timestamp = mktime(0,0,0,$month,$day,$year);
			$date = getdate ($timestamp);
			$dayofweek = $date['wday'];
			return $dayofweek;
		}

		private function getHeaderContent() {
			$translator = $this->_environment->getTranslationObject();

			$return = array();

			if($this->_presentation_mode === "month") {
				$params = $this->_environment->getCurrentParameterArray();

				$month_array = array(
					$translator->getMessage('DATES_JANUARY_LONG'),
					$translator->getMessage('DATES_FEBRUARY_LONG'),
					$translator->getMessage('DATES_MARCH_LONG'),
					$translator->getMessage('DATES_APRIL_LONG'),
					$translator->getMessage('DATES_MAY_LONG'),
					$translator->getMessage('DATES_JUNE_LONG'),
					$translator->getMessage('DATES_JULY_LONG'),
					$translator->getMessage('DATES_AUGUST_LONG'),
					$translator->getMessage('DATES_SEPTEMBER_LONG'),
					$translator->getMessage('DATES_OCTOBER_LONG'),
					$translator->getMessage('DATES_NOVEMBER_LONG'),
					$translator->getMessage('DATES_DECEMBER_LONG')
				);

				// time calculations
				$month = mb_substr($this->_calendar["month"],4,2);
				$year = $this->_calendar["year"];
				$days = daysInMonth($month,$year);
				$first_day_week_day = $this->getWeekDayOfDate(1,$month,$year);

				// create array with correct daynumber/weekday relationship
				$format_array = array();
				$current_month = array();
				$current_year = array();
				//skip fields at beginning
				$empty_fields = (($first_day_week_day + 6) % 7);
				if($month != '01'){
					$prev_month = $month - 1;
					$prev_month_year = $year;
				} else {
					$prev_month = 12;
					$prev_month_year = $year - 1;
				}
				$prev_month_days = daysInMonth($prev_month,$prev_month_year);
				for ($i =0; $i < $empty_fields; $i++) {
					$format_array[]['day'] = $prev_month_days-($empty_fields - $i)+1;
					$current_month[] = $prev_month;
					$current_year[] = $prev_month_year;
				}

				// fill days
				for ($i =1; $i <= $days;$i++) {
					$format_array[]['day'] = $i;
					$current_month[] = $month;
					$current_year[] = $year;
				}

				// skip at ending
				$sum = $days + $empty_fields;
				$remaining = 42 - $sum;
				if($month != '12'){
					$next_month = $month + 1;
					$next_month_year = $year;
				} else {
					$next_month = 1;
					$next_month_year = $year + 1;
				}
				for ($i=0;$i<$remaining;$i++) {
					$format_array[]['day'] = $i + 1;
					$current_month[] = $next_month;
					$current_year[] = $next_month_year;
				}
				$calendar_week_first = date('W', mktime(3,0,0,$current_month[0],$format_array[0]['day'],$current_year[0]));
				if($calendar_week_first[0] == '0'){
					$calendar_week_first = $calendar_week_first[1];
				}
				$calendar_week_last = date('W', mktime(3,0,0,$current_month[35],$format_array[35]['day'],$current_year[35]));
				if($calendar_week_last[0] == '0'){
					$calendar_week_last = $calendar_week_last[1];
				}

				if (!isset($this->_calendar["month"]) or empty($this->_calendar["month"])){
					$month = date ("Ymd");
				}else{
					$month = $this->_calendar["month"];
				}
				$year = mb_substr($month,0,4);
				$month = mb_substr($month,4,2);
				if($month != 1 and $month != 12){
					$prev_month = $month-1;
					$next_month = $month+1;
					$prev_month_year = $year;
					$next_month_year = $year;
				} elseif ($month == 1){
					$prev_month = 12;
					$next_month = 2;
					$prev_month_year = $year-1;
					$next_month_year = $year;
				} elseif ($month == 12){
					$prev_month = 11;
					$next_month = 1;
					$prev_month_year = $year;
					$next_month_year = $year+1;
				}

				// navigation
				$return["prev"] = date("Ymd", mktime(3,0,0,$prev_month,1,$prev_month_year));
				$return["today"] = date("Ymd");
				$return["next"] = date("Ymd",mktime(3,0,0,$next_month,1,$next_month_year));

				// info
				$return["current_month"] = $month_array[$month -1];
				$return["current_year"] = $year;
				$return['current_calendarweek_first'] = $calendar_week_first;
				$return["current_calendarweek_last"] = $calendar_week_last;
			} elseif($this->_presentation_mode === "week") {
				$week_left = $this->_calendar["week"] - ( 3600 * 24 * 7);
				$week_right = $this->_calendar["week"] + ( 3600 * 24 * 7);

				$day = date('D');
				if($day == 'Mon'){
					$current_week = time();
				} elseif ($day == 'Tue'){
					$current_week = time() - (3600 * 24);
				} elseif ($day == 'Wed'){
					$current_week = time() - (3600 * 24 * 2);
				} elseif ($day == 'Thu'){
					$current_week = time() - (3600 * 24 * 3);
				} elseif ($day == 'Fri'){
					$current_week = time() - (3600 * 24 * 4);
				} elseif ($day == 'Sat'){
					$current_week = time() - (3600 * 24 * 5);
				} elseif ($day == 'Sun'){
					$current_week = time() - (3600 * 24 * 6);
				}

				// navigation
				$return["prev"] = $week_left;
				$return["today"] = $current_week;
				$return["next"] = $week_right;

				// info
				$return['current_week_start'] = date('d.m.Y', $this->_calendar["week"]);
				$return["current_week_last"] = date('d.m.Y', $this->_calendar["week"] + ( 3600 * 24 * 6));

				$calendar_week = date('W', $this->_calendar["week"]);
				$return["current_week"] = ($calendar_week[0] == "0") ? $calendar_week[1] : $calendar_week;
			}

			// presentation_mode
			$params = $this->_environment->getCurrentParameterArray();
			if($this->_presentation_mode === "week") {
				$day = date('D');
				if($day == 'Mon'){
					$params['week'] = time();
				} elseif ($day == 'Tue'){
					$params['week'] = time() - (3600 * 24);
				} elseif ($day == 'Wed'){
					$params['week'] = time() - (3600 * 24 * 2);
				} elseif ($day == 'Thu'){
					$params['week'] = time() - (3600 * 24 * 3);
				} elseif ($day == 'Fri'){
					$params['week'] = time() - (3600 * 24 * 4);
				} elseif ($day == 'Sat'){
					$params['week'] = time() - (3600 * 24 * 5);
				} elseif ($day == 'Sun'){
					$params['week'] = time() - (3600 * 24 * 6);
				}
			} elseif($this->_presentation_mode === "month") {
				$params['month'] = date("Ymd");
			}
			$return["change_presentation_params_today"] = $params;

			unset($params["week"]);
			unset($params["month"]);
			$params["presentation_mode"] = "week";
			$params["week"] = $this->_calendar["week"];
			$return["change_presentation_params_week"] = $params;

			unset($params["week"]);
			unset($params["month"]);
			$params["presentation_mode"] = "month";
			$params["month"] = $this->_calendar["month"];
			$return["change_presentation_params_month"] = $params;

			return $return;
		}
		
		private function hoursToSpace($hours) {
			return $hours * self::CELLDEFAULTHEIGHT;
		}
		
		private function spaceToHours($space) {
			return $space / self::CELLDEFAULTHEIGHT;
		}

		private function getWeekContent($list) {
			$translator = $this->_environment->getTranslationObject();

			$return = array();
			$weekStart = $this->_calendar['week'];
			
			/************************************************************************************
			 * First, build the needed information for the table head
			 * This will contain day of week, day of month and the month itself
			************************************************************************************/
			$monthArray = array(
				$translator->getMessage('DATES_JANUARY_SHORT'),
				$translator->getMessage('DATES_FEBRUARY_SHORT'),
				$translator->getMessage('DATES_MARCH_SHORT'),
				$translator->getMessage('DATES_APRIL_SHORT'),
				$translator->getMessage('DATES_MAY_SHORT'),
				$translator->getMessage('DATES_JUNE_SHORT'),
				$translator->getMessage('DATES_JULY_SHORT'),
				$translator->getMessage('DATES_AUGUST_SHORT'),
				$translator->getMessage('DATES_SEPTEMBER_SHORT'),
				$translator->getMessage('DATES_OCTOBER_SHORT'),
				$translator->getMessage('DATES_NOVEMBER_SHORT'),
				$translator->getMessage('DATES_DECEMBER_SHORT')
			);
			
			$tableHead = array();
			$startTime = $weekStart;			// this hold the weeks starting time
			
			for ($i = 0; $i < 7; $i++) {		// seven days of a week
				// get day, month and year of current week
				$startDay = date("j", $startTime);
				$startMonth = date("n", $startTime);
				$startYear = date("Y", $startTime);
				
				// translate month
				$translatedMonth = $monthArray[$startMonth - 1];
				
				// translate day of week
				switch ($i) {
					case 0:
						$translatedDayOfWeek = $translator->getMessage('COMMON_DATE_WEEKVIEW_MONDAY',    $startDay, $translatedMonth);
						break;
					case 1:
						$translatedDayOfWeek = $translator->getMessage('COMMON_DATE_WEEKVIEW_TUESDAY',   $startDay, $translatedMonth);
						break;
					case 2:
						$translatedDayOfWeek = $translator->getMessage('COMMON_DATE_WEEKVIEW_WEDNESDAY', $startDay, $translatedMonth);
						break;
					case 3:
						$translatedDayOfWeek = $translator->getMessage('COMMON_DATE_WEEKVIEW_THURSDAY',  $startDay, $translatedMonth);
						break;
					case 4:
						$translatedDayOfWeek = $translator->getMessage('COMMON_DATE_WEEKVIEW_FRIDAY',    $startDay, $translatedMonth);
						break;
					case 5:
						$translatedDayOfWeek = $translator->getMessage('COMMON_DATE_WEEKVIEW_SATURDAY',  $startDay, $translatedMonth);
						break;
					case 6:
						$translatedDayOfWeek = $translator->getMessage('COMMON_DATE_WEEKVIEW_SUNDAY',    $startDay, $translatedMonth);
						break;
					default:
						break;
				}
				
				$return["tablehead"][$i] = $translatedDayOfWeek;
				
				$startTime += 3600 * 24;		// go to next day
			}
			
			/************************************************************************************
			 * Build an array of all dates to present in the view with
			 * keys for year, month and day
			 * 
			 * Dates, that span over multiple days, month or years are added
			 * as new dates for each day
			************************************************************************************/
			$dateArray = array();
			
			// iterate the list
			$currentDate = $list->getFirst();
			while ($currentDate) {
				$startDate = array();				// with keys year, month, day
				$endDate = array();					// with keys year, month, day
				
				// converte dates from input and put values into array and ensure correct format by typecasting(no trailing zero)
				$startDateConvert = convertDateFromInput($currentDate->getStartingDay(), $this->_environment->getSelectedLanguage());
				if ($startDateConvert["conforms"] === true) {
					$startDateConvert = getDateFromString($startDateConvert["timestamp"]);
					
					$startDate["year"]	= (int) $startDateConvert["year"];
					$startDate["month"]	= (int) $startDateConvert["month"];
					$startDate["day"]	= (int) $startDateConvert["day"];
				}
				
				$endDateConvert = convertDateFromInput($currentDate->getEndingDay(), $this->_environment->getSelectedLanguage());
				if ($endDateConvert["conforms"] === true) {
					$endDateConvert = getDateFromString($endDateConvert["timestamp"]);
						
					$endDate["year"]	= (int) $endDateConvert["year"];
					$endDate["month"]	= (int) $endDateConvert["month"];
					$endDate["day"]		= (int) $endDateConvert["day"];
				}
				
				if (isset($startDate["day"]) && isset($startDate["month"]) && isset($startDate["year"])) {
					
					// dates in list are not only for this week - grmpf... - filter here
					$dateStartTimestamp = mktime(0, 0, 0, $startDate["month"], $startDate["day"], $startDate["year"]);
					$dateEndTimestamp = mktime(0, 0, 0, $endDate["month"], $endDate["day"], $endDate["year"]);
					if (	(!empty($endDate) && $dateEndTimestamp < $weekStart) ||
							($dateStartTimestamp > $weekStart + 3600 * 24 * 7) ) {
						
						$currentDate = $list->getNext();
						continue;
					}
					
					// add this date to our date array
					$dateArray[$startDate["year"]][$startDate["month"]][$startDate["day"]][] = $currentDate;
					
					// the rest of this code is to create dates, if the current date spans several days, etc...
					$start = $startDate;
					$end = $endDate;
					
					// this is done outside the while loop, becuase the tempDate only needs to be cloned once
					$tempDate = null;
					if ( !empty($end) && array_diff_assoc($start, $end)) {
						$tempDate = clone $currentDate;
						
						// set some temp date properties
						$tempDate->setShownStartingDay($currentDate->getStartingDay());
						$tempDate->setShownStartingTime($currentDate->getStartingTime());
						
						// if date has a starting time, set temp starting time to zero hour
						if ($currentDate->getStartingTime()) {
							$tempDate->setStartingTime("00:00:00");
						}
						
						//$count = 1;
						while (array_diff_assoc($start, $end)) {				// compare start end end date - empty array is bool(false)
						
							// update date with day + 1 and keep boundries
							$start["day"]++;
							if ($start["day"] > daysInMonth($start["month"], $start["year"])) {
								$start["day"] = 1;
								$start["month"]++;
									
								if ($start["month"] > 12) {
									$start["month"] = 1;
									$start["year"]++;
								}
							}
						
							// if we are outside the week to display, break here
							//if($count++ == 6) break;
						
							// set starting day
							$startingDayString = $start["year"] . "-" . sprintf("%02d", $start["month"]) . "-" . sprintf("%02d", $start["day"]);
							$tempDate->setStartingDay($start["year"]);
						
							// add to date array
							$dateArray[$start["year"]][$start["month"]][$start["day"]][] = $tempDate;
						}
					}
				}				
				
				$currentDate = $list->getNext();
			}
			
			/************************************************************************************
			 * Create information for full day events
			************************************************************************************/
			
			
			/************************************************************************************
			 * Create the display view array
			 * 
			 * two-dimensional array with [ row(hour) ][ column(day) ]
			************************************************************************************/
			$displayArray = array();
			
			$weekStartZeroHour = $weekStart - 3 * 60 * 60;// don't know why, but weekStart was set TO 03:00 am
			
			// go through our dates
			foreach ($dateArray as $year => $yearArray) {
				foreach ($yearArray as $month => $monthArray) {
					foreach ($monthArray as $day => $dates) {
						foreach($dates as $date) {
							$dateReturn = array();
							
							// calculate week start - date start offset
							$dateStartTimestamp = mktime(0, 0, 0, $month, $day, $year);								
							$timeStartDiff = $dateStartTimestamp - $weekStartZeroHour;
							
							// convert diff to table column(24x7)
							$viewColumn = (int) ($timeStartDiff / (3600 * 24));// + 1;
							
							// color
							if($date->getColor() != ''){
								$color = $date->getColor();
							} else {
								$color = '#FFFF66';
							}
								
							$colorStr = "";
							switch ($color){
								case '#CC0000': $colorStr = "red"; break;
								case '#FF6600': $colorStr = "orange"; break;
								case '#FFCC00': $colorStr = "yellow"; break;
								case '#FFFF66': $colorStr = "light_yellow"; break;
								case '#33CC00': $colorStr = "green"; break;
								case '#00CCCC': $colorStr = "turquoise"; break;
								case '#3366FF': $colorStr = "blue"; break;
								case '#6633FF': $colorStr = "dark_blue"; break;
								case '#CC33CC': $colorStr = "purple"; break;
								default: $colorStr = "grey"; break;
							}
							
							// table row is taken from dates start time
							$startTimeConvert = convertTimeFromInput($date->getStartingTime());
							if ($startTimeConvert["conforms"] === true) {			// start time is specified
								$viewRow = (int) (mb_substr($startTimeConvert["timestamp"], 0, 2));
								
								// take the end time and determ the height
								$topMargin = 0;											// this is the offset beginning hour offset
								$dateHeight = $dateDefaultHeight;						// this is also the height, when no ending time is given
								$endTimeConvert = convertTimeFromInput($date->getEndingTime());
								if ($endTimeConvert["conforms"] === true) {
									// calculate the exact top margin and height in px, maximum is the day border
									$startTime = mktime(
										(int) (mb_substr($startTimeConvert["timestamp"], 0, 2)),
										(int) (mb_substr($startTimeConvert["timestamp"], 2, 2)),
										0,
										$month,
										$day,
										$year
									);
									
									// get end day
									$endDateConvert = convertDateFromInput($date->getEndingDay(), $this->_environment->getSelectedLanguage());
									$endDateConvert = getDateFromString($endDateConvert["timestamp"]);
									$endDay = (int) $endDateConvert["day"];
									
									$endTime = mktime(
										(int) (mb_substr($endTimeConvert["timestamp"], 0, 2)),
										(int) (mb_substr($endTimeConvert["timestamp"], 2, 2)),
										0,
										$month,
										$day,
										$year
									);
									
									// top offset - when not starting at full hour boundries
									// extract minutes from start time
									$startTimeMinutes = (int) (date("i", $startTime));
									$topMargin = self::CELLDEFAULTHEIGHT * $startTimeMinutes / 60;
									$topMargin = ($topMargin + $dateDefaultHeight > self::CELLDEFAULTHEIGHT) ? self::CELLDEFAULTHEIGHT - self::DATEDEFAULTHEIGHT : $topMargin;		// limit to cell boundries
									
									// this is the time in hours, the date already displays
									$durationDoneInHours = self::DATEDEFAULTHEIGHT / self::CELLDEFAULTHEIGHT;
									
									// get the space and the hours left in the starting cell
									$startingCellSpaceLeft = self::CELLDEFAULTHEIGHT - self::DATEDEFAULTHEIGHT - $topMargin;
									$startingCellSpaceLeftInHours = $startingCellSpaceLeft / self::CELLDEFAULTHEIGHT;
									
									// the complete date duration
									if ($endDay == $day) {
										// date will end today
										$durationInHours = ($endTime - $startTime) / 3600;		// this is floating point
									} else {
										// date will end in future days
										$durationInHours = 60 * 24 - ((int) date("H", $startTime)) * 60 - ((int) date("i", $startTime));
									}
									
									// now fill the start cell and following cells until end
									$durationLeftInHours = $durationInHours - $durationDoneInHours;
									
									// starting cell
									if ($durationLeftInHours <= $startingCellSpaceLeftInHours) {
										// cell can take it all
										$dateHeight += self::CELLDEFAULTHEIGHT * $durationLeftInHours;
										$durationLeftInHours = 0;
									} else {
										// date is going over starting cell
										$dateHeight = self::CELLDEFAULTHEIGHT - $topMargin;
										$durationLeftInHours -= $this->spaceToHours($dateHeight - self::DATEDEFAULTHEIGHT);
										
										$actualRow = $viewRow;
										$spaceLeft = self::CELLDEFAULTHEIGHT;
										while ($durationLeftInHours > 0) {
											// determ the new cell
											$actualRow++;
											
											// determ the height to use and update duration left
											if ($durationLeftInHours > 1) {
												$insertDateHeight = self::CELLDEFAULTHEIGHT;
												$durationLeftInHours--;
											} else {
												$insertDateHeight = $this->hoursToSpace($durationLeftInHours);
												$durationLeftInHours = 0;
											}
											
											// create new view entries
											$displayArray[$actualRow][$viewColumn][] = array(
												"title"			=> $date->getTitle(),
												"color"			=> $colorStr,
												"dateHeight"	=> $insertDateHeight,
												"topMargin"		=> 0
											);
										}
									}
								}
								
								$dateReturn["dateHeight"] = $dateHeight;
								$dateReturn["topMargin"] = $topMargin;
							} else {
								$viewRow = -1;										// day event
							}
							
							// set display information
							$dateReturn["title"] = $date->getTitle();
							$dateReturn["color"] = $colorStr;
							
							$displayArray[$viewRow][$viewColumn][] = $dateReturn;
						}
					}
				}
			}
			
			//var_dump($displayArray);
			
			$return["display"] = $displayArray;
			
			
			
			/************************************************************************************
			 * Build an info array for manage displaying
			 * of dates, that occure at the same time
			************************************************************************************/
			$dateInfo = array();
			
			// iterate through our dates
			
			
			
			
			
			
			
			
			// top row - full day events
			for($index=0; $index <7; $index++){
				$week_start = $this->_week_start + ( 3600 * 24 * $index);
				$startday = date ( "d", $week_start);
				$first_char = mb_substr($startday,0,1);
				if ($first_char == '0'){
					$startday = mb_substr($startday,1,2);
				}
				$startmonth = date ( "Ymd", $week_start );
				$first_char = mb_substr($startmonth,0,1);
				if ($first_char == '0'){
					$startmonth = mb_substr($startmonth,1,2);
				}
				$startyear = date ( "Y", $week_start );
				$params = array();
				$params['iid'] = 'NEW';
				$params['day'] = $startday;
				$parameter_array = $this->_environment->getCurrentParameterArray();
				$params['month'] = $startmonth;
				$params['year'] = $startyear;
				$params['week'] = $this->_week_start;
				$params['presentation_mode'] = '1';
				$params['time'] = 0;
				$params['modus_from'] = 'calendar';
				$anAction ='';
				if ($i == 0){
					$image = '<img style="width:'.$width.'; height:1em;" src="images/spacer.gif" alt="" border="0"/>';
				}else{
					$image = '<img style="width:'.$width.'; height:2.2em;" src="images/spacer.gif" alt="" border="0"/>';
				}
				if ( $this->_with_modifying_actions ) {
					$anAction = ahref_curl( $this->_environment->getCurrentContextID(),
							CS_DATE_TYPE,
							'edit',
							$params,
							$image);
				}

				/*
				 * TODO: seems that this whole block just creates the links for adding new entries
				 *
				$return["day_events"][$index][] = array(
				);
				$html .= '<div class="calendar_entry_day" id="calendar_entry_' . $index . '"><div class="data_day" id="calendar_entry_date_div_' . $index . '">'.$anAction.'</div></div>'.LF;
				*/
			}

			$session = $this->_environment->getSession();
			
			$date_array_for_jQuery = array();
			$date_array_for_jQuery_temp = array();
			$date_array_for_jQuery_php = array();
			$date_index = 0;
			$tooltips = array();
			$tooltip_date = '';
			$tooltip_last_id = '';
			for ($day = 1; $day<9; $day++){
				$day_entries = $day-1;
				$left_position = 0;
				if ( isset($display_date_array[$day_entries]) ){
					foreach($display_date_array[$day_entries] as $date){
						$is_date_for_whole_day = false;
						$start_hour = mb_substr($date->getStartingTime(),0,2);
						if(mb_substr($start_hour,0,1) == '0'){
							$start_hour = mb_substr($start_hour,1,1);
						}
						$start_minutes = mb_substr($date->getStartingTime(),3,2);
						if(mb_substr($start_minutes,0,1) == '0'){
							$start_minutes = mb_substr($start_minutes,1,1);
						}
			
						if(($date->getStartingDay() != $date->getEndingDay()) and ($date->getEndingDay() != '')){
							if($date->getEndingTime() != ''){
								$end_hour = 23;
								$end_minutes = 60;
							} else {
								$end_hour = 0;
								$end_minutes = 0;
								$is_date_for_whole_day = true;
							}
						} else {
							if($date->getEndingTime() != ''){
								$end_hour = mb_substr($date->getEndingTime(),0,2);
								$end_minutes = mb_substr($date->getEndingTime(),3,2);
							} elseif($date->getStartingTime() != '' and $date->getEndingTime() == ''){
								$end_hour = $start_hour + 1;
								$end_minutes = $start_minutes;
							} else {
								$end_hour = $start_hour;
								$end_minutes = $start_minutes;
							}
			
						}
						if(mb_substr($end_hour,0,1) == '0'){
							$end_hour = mb_substr($end_hour,1,1);
						}
						if(mb_substr($end_minutes,0,1) == '0'){
							$end_minutes = mb_substr($end_minutes,1,1);
						}
			
						// umrechnen in Minuten, für jede viertelstunde 10 px drauf nach vier noch einen pixel drauf
						$start_minutes = $start_hour*60 + $start_minutes;
						$end_minutes = $end_hour*60 + $end_minutes;
			
						$start_quaters = mb_substr(($start_minutes / 15),0,2);
						$start_quaters_addon = mb_substr(($start_quaters / 4),0,2);
						$end_quaters = mb_substr(($end_minutes / 15),0,2);
						$end_quaters_addon = mb_substr(($end_quaters / 4),0,2);
			
						if($start_quaters == 0 and $end_quaters == 0){
							$is_date_for_whole_day = true;
						}
			
						$top = $start_quaters*10;
			
						$left = 19 + 129*($day_entries-1) + $left_position;
						$width = 129 / count($display_date_array[$day_entries]) - 4;
						$height = ($end_quaters - $start_quaters) * 10;
						if($date->getColor() != ''){
							$color = $date->getColor();
						} else {
							$color = '#FFFF66';
						}
						$color_border = '#CCCCCC';
						$link = $this->getDateItemLinkWithJavascript($date, $date->getTitle());
						$link_array = explode('"', $link);
						$href = $link_array[1];
			
						$overlap = 1;
						if(!$is_date_for_whole_day){
							$display_date = $date;
							foreach($display_date_array[$day_entries] as $display_date_compare){
								$compare_is_date_for_whole_day = false;
								if(($display_date_compare->getStartingDay() != $display_date_compare->getEndingDay()) and ($display_date_compare->getEndingDay() != '')){
									$compare_is_date_for_whole_day = true;
								}
								if(!$compare_is_date_for_whole_day and ($display_date->getItemID() != $display_date_compare->getItemID())){
									if($this->overlap($display_date, $display_date_compare)){
										$overlap++;
									}
								}
							}
						}

						$date_array_for_jQuery[] = 'new Array(' . $day_entries . ',\'' . $link . '\',' . $start_quaters . ',' . $end_quaters . ',' . count($display_date_array[$day_entries]) . ',\'' . $color . '\'' . ',\'' . $color_border . '\'' . ',\'' . $href . '\'' . ',\'sticky_' . $date_index . '\'' . ',\'' . $is_date_for_whole_day . '\')';
						$date_array_for_jQuery_php[] = array($day_entries, $link, $start_quaters, $end_quaters, count($display_date_array[$day_entries]), $color, $color_border, $href, 'sticky_' . $date_index, $is_date_for_whole_day);
						$tooltip = array();
						$tooltip['title'] = $date->getTitle();
						$tooltip['date'] = $date_tooltip_array[$date->getItemID()];
						$tooltip['place'] = $date->getPlace();
						$tooltip['participants'] = $date->getParticipantsItemList();
						#$tooltip['desc'] = $date->getDescription();
						$tooltip['color'] = $color;
						$tooltips['sticky_' . $date_index] = $tooltip;
						$date_index++;
						$left_position = $left_position + $width + 4;
					}
				}
			}
			
			/*
			foreach($tooltips as $id => $tooltip){
				
				$html .= '<div id="' . $id . '" class="atip" style="padding:5px; border:2px solid ' . $tooltip['color'] . '">'.LF;
				$html .= '<tr><td colspan="2"><b>' . encode(AS_HTML_SHORT,$tooltip['title']) . '</b></td></tr>'.LF;
				$html .= '<tr><td style="vertical-align:top;"><b>' . $this->_translator->getMessage('DATES_DATETIME') . ':</b></td><td>' .  $tooltip['date'][1] . '</td></tr>'.LF;
				if($tooltip['place'] != ''){
					$html .= '<tr><td style="vertical-align:top;"><b>' . $this->_translator->getMessage('DATES_PLACE') . ':</b></td><td>' . encode(AS_HTML_SHORT,$tooltip['place']) . '</td></tr>'.LF;
				}
				$html .= '<tr><td style="vertical-align:top;"><b>' . $this->_translator->getMessage('DATE_PARTICIPANTS') . ':</b></td><td>'.LF;
				
				if($tooltip['participants']->isEmpty()){
					$html .= $this->_translator->getMessage('TODO_NO_PROCESSOR');
				} else {
					$participant = $tooltip['participants']->getFirst();
					$count = $tooltip['participants']->getCount();
					$counter = 1;
					while ($participant) {
						$html .= $participant->getFullName();
						if ( $counter < $count) {
							$html .= ', ';
						}
						$participant = $tooltip['participants']->getNext();
						$counter++;
					}
				}
			}
			$html .= '</div></div>';
			$html .= '<script type="text/javascript">'.LF;
			$html .= '<!--'.LF;
			$html .= 'var calendar_dates = new Array(';
			*/
			
			// die maximale breite bei nebeneinander liegenden Termine
			$overlap_array = array();
			$max_overlap_array = array();
			$max_overlap_array_for_date = array();
			$date_array_for_jQuery_php_with_position = array();
			for ($int = 0; $int < 7; $int++) {
				$temp_quaters_array = array();
				for ($j = 0; $j < 96; $j++) {
					$temp_quaters_array[] = 0;
				}
				for ($i = 0; $i < sizeof($date_array_for_jQuery_php); $i++) {
					$day = $date_array_for_jQuery_php[$i][0]-1;
					if($day == $int){
						$start_quaters = $date_array_for_jQuery_php[$i][2];
						$end_quaters = $date_array_for_jQuery_php[$i][3];
						for ($j = $start_quaters; $j < $end_quaters; $j++) {
							$value = $temp_quaters_array[$j];
							$temp_quaters_array[$j] = $value + 1;
						}
					}
				}
				$overlap_array[] = $temp_quaters_array;
				$max_overlap = 0;
				for ($i = 0; $i < sizeof($temp_quaters_array); $i++) {
					if($max_overlap < $temp_quaters_array[$i]){
						$max_overlap = $temp_quaters_array[$i];
					}
				}
				$max_overlap_array[] = $max_overlap;
				for ($i = 0; $i < sizeof($date_array_for_jQuery_php); $i++) {
					$day = $date_array_for_jQuery_php[$i][0]-1;
					if($day == $int){
						$start_quaters = $date_array_for_jQuery_php[$i][2];
						$end_quaters = $date_array_for_jQuery_php[$i][3];
						$max_overlap_for_date = 0;
						for ($j = $start_quaters; $j < $end_quaters; $j++) {
							if($temp_quaters_array[$j] > $max_overlap_for_date){
								$max_overlap_for_date = $temp_quaters_array[$j];
							}
						}
						$max_overlap_array_for_date[] = $max_overlap_for_date;
					}
				}
			}
			
		      // Arrays zum Sortieren vorbereiten
		      $sort_dates_array = array();
		      $sort_dates_start_array = array();
		      for ($i = 0; $i < 7; $i++) {
		         $temp_sort_array = array();
		         for ($j = 0; $j < $max_overlap_array[$i]; $j++) {
		            $temp_part_array = array();
		            for ($k = 0; $k < 96; $k++) {
		               $temp_part_array[] = 0;
		            }
		            $temp_sort_array[] = $temp_part_array;
		         }
		         // Termine sortieren
		         $max_overlap_index = 0;
		         foreach($date_array_for_jQuery_php as $temp_date){
		            $found_position = false;
		            if($temp_date[0]-1 == $i){
		               $start_quaters = $temp_date[2];
		               $end_quaters = $temp_date[3];
		               $date_set = false;
		               for ($temp_part = 0; $temp_part < sizeof($temp_sort_array); $temp_part++) {
		               #foreach($temp_sort_array as $temp_part_array){
		                  if(!$date_set){
		                     $slot_free = true;
		                     for ($time = $start_quaters; $time < $end_quaters; $time++) {
		                        if($temp_sort_array[$temp_part][$time] != 0){
		                        #if($temp_part_array[$time] != 0){
		                           $slot_free = false;
		                        }
		                     }
		                     if($slot_free){
		                        for ($time = $start_quaters; $time < $end_quaters; $time++) {
		                           $temp_sort_array[$temp_part][$time] = 1;
		                           if(!$found_position){
		                              $temp_date[] = sizeof($temp_sort_array);
		                              $temp_date[] = $temp_part;
		                              $temp_date[] = $time;
		                              $found_position = true;
		                           }
		                           $temp_part_array[$time] = 1;
		                        }
		                        $date_set = true;
		                     }
		                  }
		               }
		               $temp_date[] = $max_overlap_array_for_date[$max_overlap_index];
		               $date_array_for_jQuery_php_with_position[] = $temp_date;
		            }
		            $max_overlap_index++;
		         }
		         $sort_dates_array[] = $temp_sort_array;
		      }
		
		      $last = count($date_array_for_jQuery)-1;
		      #for ($index = 0; $index < count($date_array_for_jQuery); $index++) {
		      #   $html .= $date_array_for_jQuery[$index];
		      #   #pr($date_array_for_jQuery[$index]);
		      #   if($index < $last){
		      #     $html .= ',';
		      #   }
		      #}
		      for ($index = 0; $index < count($date_array_for_jQuery_php_with_position); $index++) {
		         $day_entries = $date_array_for_jQuery_php_with_position[$index][0];
		         $link = $date_array_for_jQuery_php_with_position[$index][1];
		         $link = str_replace("'", "\'", $link);
		         $start_quaters = $date_array_for_jQuery_php_with_position[$index][2];
		         $end_quaters = $date_array_for_jQuery_php_with_position[$index][3];
		         $dates_on_day = $date_array_for_jQuery_php_with_position[$index][4];
		         $color = $date_array_for_jQuery_php_with_position[$index][5];
		         $color_border = $date_array_for_jQuery_php_with_position[$index][6];
		         $href = $date_array_for_jQuery_php_with_position[$index][7];
		         $date_index = $date_array_for_jQuery_php_with_position[$index][8];
		         $is_date_for_whole_day = $date_array_for_jQuery_php_with_position[$index][9];
		         if(isset($date_array_for_jQuery_php_with_position[$index][10])){
		            $max_overlap = $date_array_for_jQuery_php_with_position[$index][10];
		         } else {
		            $max_overlap = 0;
		         }
		         if(isset($date_array_for_jQuery_php_with_position[$index][11])){
		            $start_column = $date_array_for_jQuery_php_with_position[$index][11];
		         } else {
		            $start_column = 0;
		         }
		         if(isset($date_array_for_jQuery_php_with_position[$index][12])){
		            $start_quarter = $date_array_for_jQuery_php_with_position[$index][12];
		         } else {
		            $start_quarter = 0;
		         }
		         if(isset($date_array_for_jQuery_php_with_position[$index][13])){
		            $max_overlap_for_date = $date_array_for_jQuery_php_with_position[$index][13];
		         } else {
		            $max_overlap_for_date = 0;
		         }
		         var_dump($max_overlap);
		         var_dump($max_overlap_for_date);
		         
		         /*
		         $html .= 'new Array(' . $day_entries . ',\'' . $link . '\',' . $start_quaters . ',' . $end_quaters . ',' . $dates_on_day . ',\'' . $color . '\'' . ',\'' . $color_border . '\'' . ',\'' . $href . '\'' . ',\'' . $date_index . '\'' . ',\'' . $is_date_for_whole_day . '\'' . ',' . $max_overlap . '' . ',' . $start_column . '' . ',' . $start_quarter . '' . ',' . $max_overlap_for_date . ')'.LF;
		         if($index < $last){
		           $html .= ',';
		         }*/
		      }
		      
		      // main week content
		      for($index=0; $index < 24; $index++) {
		      	for($index_day=0; $index_day <7; $index_day++) {
		      		$week_start = $this->_calendar["week"] + ( 3600 * 24 * $index_day);
		      		$startday = date ( "d", $week_start);
		      		$first_char = mb_substr($startday,0,1);
		      
		      		if ($first_char == '0'){
		      			$startday = mb_substr($startday,1,2);
		      		}
		      
		      		$startmonth = date ( "Ymd", $week_start );
		      		$first_char = mb_substr($startmonth,0,1);
		      
		      		if ($first_char == '0'){
		      			$startmonth = mb_substr($startmonth,1,2);
		      		}
		      
		      		$startyear = date ( "Y", $week_start );
		      
		      		$params = array();
		      		$params['iid'] = 'NEW';
		      		$params['day'] = $startday;
		      		$parameter_array = $this->_environment->getCurrentParameterArray();
		      		$params['month'] = $startmonth;
		      		$params['year'] = $startyear;
		      		$params['week'] = $this->_week_start;
		      		$params['presentation_mode'] = '1';
		      
		      		if ($i != 0){
		      			$params['time'] = $index;
		      		} else {
		      			$params['time'] = 0;
		      		}
		      
		      		$params['modus_from'] = 'calendar';
		      		$anAction = '';
		      
		      		if ( $this->_with_modifying_actions ) {
		      			$anAction = ahref_curl( $this->_environment->getCurrentContextID(),
		      					CS_DATE_TYPE,
		      					'edit',
		      					$params,
		      					$image);
		      		}
		      
		      
		      		$state = "active_day";
		      		// check if day is today
		      		if(false) {//$todayCompressed === $format['day'].$current_month_temp.$current_year[$i]) {
		      			$state = "this_today";
		      		}
		      
		      		// check if day is not active(grey out)
		      		elseif(($index < 9) || ($index > 17)) {
		      			$state = "nonactive_day";
		      		}
		      
		      		//$index_day
		      		//$todayCompressed = date("jnY");
		      
		      		$return["days"][] = array(
		      				//"day"		=> $format["day"],
		      				"link"		=> $anAction,
		      				"state"		=> $state
		      				//"dates"		=> $dates
		      		);
		      
		      		/*
		      		 if ($i == 0){
		      		$image = '<img style="width:'.$width.'; height:1em;" src="images/spacer.gif" alt="" border="0"/>';
		      		} else {
		      		$image = '<img style="width:'.$width.'; height:2.2em;" src="images/spacer.gif" alt="" border="0"/>';
		      		}
		      
		      		if(($index < 8) or ($index > 15)){
		      		$html .= '<div class="calendar_entry" id="calendar_entry_' . $index . '_' . $index_day . '"><div class="data" id="calendar_entry_date_div_' . $index . '_' . $index_day . '"></div></div>'.LF;
		      		} else {
		      		$html .= '<div class="calendar_entry_work" id="calendar_entry_' . $index . '_' . $index_day . '"><div class="data" id="calendar_entry_date_div_' . $index . '_' . $index_day . '"></div></div>'.LF;
		      		}
		      			
		      
		      		$html_javascript .= 'new Array(\'#calendar_entry_date_div_' . $index . '_' . $index_day . '\',\'<div name="calendar_new_date" id="calendar_entry_background_div_' . $index . '_' . $index_day . '" style="position:absolute; top: 0px; left: 0px; height: 100%; width: 100%; z-index:900;"><div style="width:100%; text-align:left;">' . $anAction . '</div></div>\')';
		      
		      		if($current_element < (24*7)-1){
		      		$html_javascript .= ','.LF;
		      		} else {
		      		$html_javascript .= LF;
		      		}*/
		      
		      		$current_element++;
		      	}
		      }
		      
		      //var_dump($return);
		
		      /*
		      $html .= ');'.LF;
		      $html .= 'var today = "' . $today . '";' .LF;
		      $html .= '-->'.LF;
		      $html .= '</script>'.LF;
		      return $html;*/

			return $return;
		}

		private function getMonthContent($list) {
			$translator = $this->_environment->getTranslationObject();

			$current_time = localtime();

			// do some time calculations
			$month = mb_substr($this->_calendar["month"],4,2);
			$year = $this->_calendar["year"];
			$days = daysInMonth($month,$year);
			$first_day_week_day = $this->getWeekDayOfDate(1,$month,$year);

			// create array with correct daynumber/weekday relationship
			$format_array = array();
			$current_month = array();
			$current_year = array();

			//skip fields at beginning
			$empty_fields = (($first_day_week_day + 6) % 7);
			if($month != '01'){
				$prev_month = $month - 1;
				$prev_month_year = $year;
			} else {
				$prev_month = 12;
				$prev_month_year = $year - 1;
			}
			$prev_month_days = daysInMonth($prev_month,$prev_month_year);
			for ($i =0; $i < $empty_fields; $i++) {
				$format_array[]['day'] = $prev_month_days-($empty_fields - $i)+1;
				$current_month[] = (string) $prev_month;
				$current_year[] = $prev_month_year;
			}

			// fill days
			for ($i =1; $i <= $days;$i++) {
				$format_array[]['day'] = $i;
				$current_month[] = (string) $month;
				$current_year[] = $year;
			}

			// skip at ending
			$sum = $days + $empty_fields;
			$remaining = 42 - $sum;
			if($month != '12'){
				$next_month = $month + 1;
				$next_month_year = $year;
			} else {
				$next_month = 1;
				$next_month_year = $year + 1;
			}
			for ($i=0;$i<$remaining;$i++) {
				$format_array[]['day'] = $i + 1;
				$current_month[] = (string) $next_month;
				$current_year[] = $next_month_year;
			}

			// get Dates in month
			$current_date = $list->getFirst();
			$finish = false;
			$date_tooltip_array = array();
			while ($current_date) {
				$date_tooltip_array[$current_date->getItemID()] = $this->getTooltipDate($current_date);
				$start_date_month = '';
				$start_date_day = '';
				$start_date_year = '';
				$end_date_month = '';
				$end_date_day = '';
				$end_date_year = '';
				$start_date_array = convertDateFromInput($current_date->getStartingDay(),$this->_environment->getSelectedLanguage());

				if ($start_date_array['conforms'] == true) {
					$start_date_array = getDateFromString($start_date_array['timestamp']);
					$start_date_month = $start_date_array['month'];
					$start_date_day = $start_date_array['day'];
					$start_date_year = $start_date_array['year'];
				}

				$end_date_array = convertDateFromInput($current_date->getEndingDay(),$this->_environment->getSelectedLanguage());
				if ($end_date_array['conforms'] == true) {
					$end_date_array = getDateFromString($end_date_array['timestamp']);
					$end_date_month = $end_date_array['month'];
					$end_date_day =   $end_date_array['day'];
					$end_date_year = $end_date_array['year'];
				}

				if ($start_date_day != '') {

	            	//date begins at least one month before currently displayed month, ends in currently displayed month
	            	// OR date begins in a year before the current and ends in
	       			if ( ($start_date_month < $month OR $start_date_year < $year) AND $end_date_month == $month AND $end_date_year == $year){
	       				for ($i=0;$i < $end_date_day;$i++) {
	             		$format_array[$empty_fields+$i]['dates'][] = $current_date;
	          			}

			       //date begins in currently displayed month, ends aftet currently displayed month
			       //OR date begins in currently displayed year and ends after currently displayed year
			       } elseif ($start_date_month == $month AND $start_date_year == $year AND ($end_date_month > $month OR $end_date_year > $year ) ){
			          $rest_month = $days - $start_date_day;
			          for ($i=0;$i <= $rest_month;$i++) {
			             $format_array[$empty_fields+$start_date_day-1+$i]['dates'][] = $current_date;
			          }

			            //date begins before and ends after currently displayed month
			       } elseif ( ($start_date_month < $month OR ($start_date_year < $year)) AND ($end_date_month > $month OR ($end_date_year > $year))) {
			          for ($i=0;$i < $days;$i++) {
			             $format_array[$empty_fields+$i]['dates'][] = $current_date;
			          }
			       }

			       else { //Date spans in one month or is on a single day
			               $length = 0;
			          if ($end_date_day != '') {
			             $length = $end_date_day - $start_date_day;
			               }
			          for ($i=0; $i <= $length; $i++) {
			                  $format_array[$empty_fields+$start_date_day-1+$i]['dates'][] = $current_date;
			          }
			       }
				}

				$current_date = $list->getNext();
			}

			// setup tooltip's and actions
			$anAction_array = array();
			$date_index = 0;
			$tooltips = array();
			$tooltip_last_id = '';
			$tooltip_date = '';

			for ($i=0;$i<42;$i++) {

		      	if($format_array[$i]['day'].$current_month[$i].$current_year[$i] == date("dmY")){
		      		$today = $format_array[$i]['day'].$current_month[$i].$current_year[$i];
		      	}

		      	$params = array();
		      	$params['iid'] = 'NEW';
		      	$temp_day = $format_array[$i]['day'];

		      	if(mb_strlen($temp_day) == 1){
		      		$temp_day = '0'.$temp_day;
		      	}

		      	$params['day'] = $temp_day;
		      	$parameter_array = $this->_environment->getCurrentParameterArray();
		      	$temp_month = $current_month[$i];

		      	if(mb_strlen($temp_month) == 1){
			    	$temp_month = '0'.$temp_month;
			    }

	      		$params['month'] = $current_year[$i].$temp_month.'01';
	      		$params['year'] = $current_year[$i];
	      		$params['presentation_mode'] = $this->_presentation_mode;
	      		$params['modus_from'] = 'calendar';
	      		$anAction = ahref_curl( $this->_environment->getCurrentContextID(),
		      		CS_DATE_TYPE,
		      		'edit',
		      		$params,
		      		'<img style="width:100%; height:100%" src="images/spacer.gif" alt="" border="0"/>');
	      		$anAction_array[] = $anAction;
		      }


		      // get return data
		      $return = array();

		      $days = array();

		      $i = 0;
		      $todayCompressed = date("jnY");
		      foreach($format_array as $format) {
		      	$current_month_temp = $current_month[$i];
		      	if($current_month_temp[0] == 0){
		      		$current_month_temp = $current_month_temp[1];
		      	}

		      	$state = "active_day";
		      	// check if day is today
		      	if($todayCompressed === $format['day'].$current_month_temp.$current_year[$i]) {
		      		$state = "this_today";
		      	}

		      	// check if day is not active(grey out)
		      	elseif($current_month[$i] != mb_substr($this->_calendar["month"],4,2)) {
		      		$state = "nonactive_day";
		      	}

		      	// process dates for this day
		      	$dates = array();
		      	foreach($format["dates"] as $date) {
		      		// link
		      		$link = $this->getDateItemLinkWithJavascript($date, $date->getTitle());
		      		$link = str_replace("'", "\'", $link);
		      		$link_array = explode('"', $link);
		      		$href = $link_array[1];

		      		// color
		      		if($date->getColor() != ''){
		      			$color = $date->getColor();
		      		} else {
		      			$color = '#FFFF66';
		      		}

		      		$colorStr = "";
		      		switch ($color){
		      			case '#CC0000': $colorStr = "red"; break;
		      			case '#FF6600': $colorStr = "orange"; break;
		      			case '#FFCC00': $colorStr = "yellow"; break;
		      			case '#FFFF66': $colorStr = "light_yellow"; break;
		      			case '#33CC00': $colorStr = "green"; break;
		      			case '#00CCCC': $colorStr = "turquoise"; break;
		      			case '#3366FF': $colorStr = "blue"; break;
		      			case '#6633FF': $colorStr = "dark_blue"; break;
		      			case '#CC33CC': $colorStr = "purple"; break;
		      			default: $colorStr = "grey"; break;
		      		}

		      		// room
		      		$room_title = "";
		      		$date_context_item = $date->getContextItem();
		      		if ( isset($date_context_item) ) {
		      			$room_title = $date_context_item->getTitle();
		      		}

		      		// participants
		      		$participants = array();
		      		$participantsList = $date->getParticipantsItemList();
		      		if(!$participantsList->isEmpty()) {
		      			$participant = $participantsList->getFirst();

		      			while($participant) {
		      				$participants[] = array(
		      					"name"	=> $participant->getFullName()
		      				);

		      				$participant = $participantsList->getNext();
		      			}
		      		}

		      		$date = array(
		      			"title"			=> $date->getTitle(),
		      			"date"			=> $date_tooltip_array[$date->getItemID()],
		      			"place"			=> $date->getPlace(),
		      			"participants"	=> $participants,
		      			"color"			=> $colorStr,
		      			"context"		=> $room_title,
		      			"href"			=> $href
		      		);

		      		$dates[] = $date;
		      	}

		      	$days[] = array(
	      			"day"		=> $format["day"],
	      			"link"		=> $anAction_array[$i],
		      		"state"		=> $state,
		      		"dates"		=> $dates
		      	);

		      	$i++;
		      }

		      $return['days'] = $days;

			return $return;
		}
		
		private function overlap($display_date, $compare_date) {
			$result = false;
		
			$display_date_times = $this->getMktimeForDate($display_date);
			$display_date_starttime = $display_date_times['starttime'];
			$display_date_endtime = $display_date_times['endtime'];
		
			$display_date_compare_times = $this->getMktimeForDate($compare_date);
			$display_date_compare_starttime = $display_date_compare_times['starttime'];
			$display_date_compare_endtime = $display_date_compare_times['endtime'];
		
			if((($display_date_starttime < $display_date_compare_starttime) and ($display_date_endtime > $display_date_compare_starttime))
					or (($display_date_starttime == $display_date_compare_starttime) and ($display_date_endtime == $display_date_compare_endtime))
					or (($display_date_starttime < $display_date_compare_endtime) and ($display_date_endtime > $display_date_compare_endtime))
					or (($display_date_starttime > $display_date_compare_starttime) and ($display_date_starttime < $display_date_compare_endtime))
					or (($display_date_endtime > $display_date_compare_starttime) and ($display_date_endtime < $display_date_compare_endtime))){
				$result = true;
			}
		
			return $result;
		}
		
		function getMktimeForDate($display_date){
			#pr($display_date->getTitle() . ' ' . $display_date->getItemID());
			$result = array();
			if($display_date->getStartingTime() != ''){
				$display_date_starttime_hours = mb_substr($display_date->getStartingTime(),0,2);
				$display_date_starttime_minutes = mb_substr($display_date->getStartingTime(),3,2);
				$display_date_starttime_seconds = mb_substr($display_date->getStartingTime(),6,2);
			} else {
				$display_date_starttime_hours = 0;
				$display_date_starttime_minutes = 0;
				$display_date_starttime_seconds = 0;
			}
			$display_date_starttime_year = mb_substr($display_date->getStartingDay(),0,4);
			$display_date_starttime_month = mb_substr($display_date->getStartingDay(),5,2);
			$display_date_starttime_day = mb_substr($display_date->getStartingDay(),8,2);
			$result['starttime'] = mktime((int)$display_date_starttime_hours, (int)$display_date_starttime_minutes, (int)$display_date_starttime_seconds, (int)$display_date_starttime_month, (int)$display_date_starttime_day, (int)$display_date_starttime_year);
		
			if($display_date->getEndingTime() != ''){
				$display_date_endtime_hours = mb_substr($display_date->getEndingTime(),0,2);
				$display_date_endtime_minutes = mb_substr($display_date->getEndingTime(),3,2);
				$display_date_endtime_seconds = mb_substr($display_date->getEndingTime(),6,2);
			} else {
				$display_date_endtime_hours = 0;
				$display_date_endtime_minutes = 0;
				$display_date_endtime_seconds = 0;
			}
			if($display_date->getEndingDay() != ''){
				$display_date_endtime_year = mb_substr($display_date->getEndingDay(),0,4);
				$display_date_endtime_month = mb_substr($display_date->getEndingDay(),5,2);
				$display_date_endtime_day = mb_substr($display_date->getEndingDay(),8,2);
			} else {
				$display_date_endtime_year = $display_date_starttime_year;
				$display_date_endtime_month = $display_date_starttime_month;
				$display_date_endtime_day = $display_date_starttime_day;
			}
			$result['endtime'] = mktime((int)$display_date_endtime_hours, (int)$display_date_endtime_minutes, (int)$display_date_endtime_seconds, (int)$display_date_endtime_month, (int)$display_date_endtime_day, (int)$display_date_endtime_year);
			#pr('.');
			return $result;
		}

		private function getCalendarContent() {
			$current_context_item = $this->_environment->getCurrentContextItem();
			$session = $this->_environment->getSessionItem();

			$return = array();

			// init values
			$this->_calendar["day"] = date("d");
			$this->_calendar["year"] = date("Y");
			$this->_calendar["month"] = date("Ymd");
			$d_time = mktime(3,0,0,date("m"),date("d"),date("Y") );
			$wday  = date("w",$d_time );
			$this->_calendar["week"]  = mktime (3,0,0,date("m"),date("d") - ($wday - 1),date("Y"));
			$old_month ='';
			$old_year ='';
			$old_week ='';

			if (isset($_GET['year'])) {
				$this->_calendar["year"] = $_GET['year'];
			} elseif (isset($_POST['year'])) {
				$this->_calendar["year"] = $_POST['year'];
			}
			if (isset($_GET['month'])) {
				$this->_calendar["month"] = $_GET['month'];
			} elseif (isset($_POST['month'])) {
				$this->_calendar["month"] = $_POST['month'];
			}
			if (isset($_GET['week']) and !empty($_GET['week'])){
				$this->_calendar["week"] = $_GET['week'];
			}elseif (isset($_POST['week'])) {
				$this->_calendar["week"] = $_POST['week'];
			}

			// presentation mode
			$return['mode'] = $this->_presentation_mode;

			// get header content
			$return['header'] = $this->getHeaderContent();

			// get main content
			if($this->_presentation_mode === "week") {
				$return['content'] = $this->getWeekContent($this->getListContent());
			} elseif($this->_presentation_mode === "month") {
				$return['content'] = $this->getMonthContent($this->getListContent());
			}





			/*

			if(isset($_GET['presentation_mode']) and !empty($_GET['presentation_mode'])){
				$presentation_mode = $_GET['presentation_mode'];
				if ( $this->_environment->inPrivateRoom() ) {
					$current_context_item = $this->_environment->getCurrentContextItem();
					$saved_date_display_mode = $current_context_item->getDatesPresentationStatus();
					if ( $presentation_mode == 1 ) {
						$current_date_display_mode = 'calendar';
					} else {
						$current_date_display_mode = 'calendar_month';
					}
					if ( $saved_date_display_mode != $current_date_display_mode ) {
						$current_context_item->setDatesPresentationStatus($current_date_display_mode);
						$current_context_item->save();
					}
					unset($current_context_item);
				}
			}elseif($seldisplay_mode == 'calendar_month'){
				$presentation_mode = '2';
				if ( $this->_environment->inPrivateRoom() ) {
					$current_context_item = $this->_environment->getCurrentContextItem();
					$saved_date_display_mode = $current_context_item->getDatesPresentationStatus();
					if ( $saved_date_display_mode != 'calendar_month' ) {
						$current_context_item->setDatesPresentationStatus('calendar_month');
						$current_context_item->save();
					}
					unset($current_context_item);
				}
			}else{
				$presentation_mode = '1';
				if ( $this->_environment->inPrivateRoom() ) {
					$current_context_item = $this->_environment->getCurrentContextItem();
					$saved_date_display_mode = $current_context_item->getDatesPresentationStatus();
					if ( !empty($saved_date_display_mode)
							and $saved_date_display_mode == 'calendar'
					) {
						$presentation_mode = '1';
					} else {
						$presentation_mode = '2';
					}
					unset($current_context_item);
				}
			}
			if ($session->issetValue($this->_environment->getCurrentContextID().'_month')){
				$old_month = $session->getValue($this->_environment->getCurrentContextID().'_month');
			}else{
				$old_month = $month;
			}
			if ($session->issetValue($this->_environment->getCurrentContextID().'_year')){
				$old_year = $session->getValue($this->_environment->getCurrentContextID().'_year');
			}else{
				$old_year = $year;
			}
			if ($session->issetValue($this->_environment->getCurrentContextID().'_week')){
				$old_week = $session->getValue($this->_environment->getCurrentContextID().'_week');
			}else{
				$old_week = $week;
			}
			if ($session->issetValue($this->_environment->getCurrentContextID().'_presentation_mode')){
				$old_presentation_mode = $session->getValue($this->_environment->getCurrentContextID().'_presentation_mode');
			}else{
				$old_presentation_mode = $presentation_mode;
			}
			//Berechnung der neuen Werte
			//Beim Blättern der Einträge
			if (!isset($_GET['year']) or !isset($_GET['month']) or !isset($_GET['week'])){
				if(isset($_GET['week']) and $old_week != $week){
					$month = date("Ymd", $week);
					$year = date("Y", $week);
					$presentation_mode = '1';
				}
				if(isset($_GET['month']) and $old_month != $month){
					$year = mb_substr($month,0,4);
					$real_month = mb_substr($month,4,2);
					$d_time = mktime(3,0,0,$real_month,'1',$year);
					$wday = date("w",$d_time);
					$week = mktime(3,0,0,$real_month,1 - ($wday - 1),$year);
					$presentation_mode = '2';
				}
				if (isset($_GET['year']) and $old_year != $year){
					$real_month = mb_substr($old_month,4,2);
					$real_day = mb_substr($old_month,6,2);
					$d_time = mktime(3,0,0,$real_month,$real_day,$year);
					$month = date("Ymd",$d_time);
					$wday = date("w",$d_time);
					$week = mktime(3,0,0,$real_month,$real_day - ($wday - 1),$year);
				}
				// Beim Editieren oder der Auswahl der Selectboxen
			}elseif (isset($_GET['year']) and isset($_GET['month']) and isset($_GET['week'])){
				$history = $session->getValue('history');
				// Beim Editieren
				if (isset($history['0']['function']) and $history['0']['function'] =='edit'){
					$month = $_GET['month'];
					$year = $_GET['year'];
					$real_month = mb_substr($month,4,2);
					$day = mb_substr($month,6,2);
					$d_time = mktime(3,0,0,$real_month,$day,$year);
					$wday = date("w",$d_time);
					if (empty($wday)){
						$wday = 7;
					}
					$week = mktime(3,0,0,$real_month,$day - ($wday - 1),$year);
					if (isset($_GET['presentation_mode'])){
						$presentation_mode = $_GET['presentation_mode'];
					}
					// Bei der Auswahl aus Selectboxen
				}else{
					if (isset($_GET['presentation_mode'])){
						$presentation_mode = $_GET['presentation_mode'];
					}else{
						$presentation_mode = '1';
					}
					$temp_year = $year;
					$temp_month = $month;
					$temp_week = $week;
					if(isset($_GET['week']) and $old_week != $week){
						$temp_month = date("Ymd", $week);
						$temp_year = date("Y", $week);
						$presentation_mode = '1';
					}elseif(isset($_GET['month']) and $old_month != $month){
						$temp_year = mb_substr($month,0,4);
						$real_month = mb_substr($month,4,2);
						$d_time = mktime(3,0,0,$real_month,'1',$temp_year);
						$wday = date("w",$d_time);
						$temp_week = mktime(3,0,0,$real_month,1 - ($wday - 1),$temp_year);
						$presentation_mode = '2';
					}elseif (isset($_GET['year']) and $old_year != $year){
						$real_month = mb_substr($old_month,4,2);
						$real_day = mb_substr($old_month,6,2);
						$d_time = mktime(3,0,0,$real_month,$real_day,$year);
						$temp_month = date("Ymd",$d_time);
						$wday = date("w",$d_time);
						$temp_week = mktime(3,0,0,$real_month,$real_day - ($wday - 1),$year);
					}
					$month = $temp_month;
					$year = $temp_year;
					$week = $temp_week;
				}
			}
			if ($old_presentation_mode != $presentation_mode){
			}
			$session->setValue($this->_environment->getCurrentContextID().'_month', $month);
			$session->setValue($this->_environment->getCurrentContextID().'_year', $year);
			$session->setValue($this->_environment->getCurrentContextID().'_week', $week);
			$session->setValue($this->_environment->getCurrentContextID().'_presentation_mode', $presentation_mode);

			*/

			return $return;
		}

		public function getListContent() {
			include_once('classes/cs_list.php');
			include_once('classes/views/cs_view.php');
			$environment = $this->_environment;
			$context_item = $environment->getCurrentContextItem();
			$converter = $environment->getTextConverter();
			$translator = $this->_environment->getTranslationObject();
			$return = array();

			if ( isset($_GET['ref_iid']) ) {
			   $ref_iid = $_GET['ref_iid'];
			} elseif ( isset($_POST['ref_iid']) ) {
			   $ref_iid = $_POST['ref_iid'];
			}

			if ( isset($_GET['ref_user']) ) {
			   $ref_user = $_GET['ref_user'];
			} elseif ( isset($_POST['ref_user']) ) {
			   $ref_user = $_POST['ref_user'];
			} else{
			   $ref_user ='';
			}

			$last_selected_tag = '';
			$seltag_array = array();

			// Find current buzzword selection
			if(isset($_GET['selbuzzword']) && $_GET['selbuzzword'] != '-2') {
				$selbuzzword = $_GET['selbuzzword'];
			} else {
				$selbuzzword = 0;
			}
			if ( isset($_GET['sort']) ) {
   				$sort = $_GET['sort'];
			} else {
   				$sort = 'time_rev';
			}

			// Find current topic selection
			if(isset($_GET['seltag']) && $_GET['seltag'] == 'yes') {
				$i = 0;
				while(!isset($_GET['seltag_' . $i])) {
					$i++;
				}
				$seltag_array[] = $_GET['seltag_' . $i];
				$j = 0;
				while(isset($_GET['seltag_' . $i]) && $_GET['seltag_' . $i] != '-2') {
					if(!empty($_GET['seltag_' . $i])) {
						$seltag_array[$i] = $_GET['seltag_' . $i];
						$j++;
					}
					$i++;
				}
				$last_selected_tag = $seltag_array[$j-1];
			}

			// Find current status selection
			if ( isset($_GET['selstatus'])
					and $_GET['selstatus'] != '-2'
			) {
				$selstatus = $_GET['selstatus'];
				// save selection
				if ( $context_item->isPrivateRoom() ) {
					$date_sel_status = $context_item->getRubrikSelection(CS_DATE_TYPE,'status');
					if ( $date_sel_status != $selstatus ) {
						$context_item->setRubrikSelection(CS_DATE_TYPE,'status',$selstatus);
						$room_save_selection = true;
					}
				}
			} else {
				if ( $this->_display_mode === "calendar"
						or $mode == 'formattach'
						or $mode == 'detailattach'
						or $environment->inPrivateRoom()
				) {
					$selstatus = 2;
					if ( $environment->inPrivateRoom() ) {
						$date_sel_status = $context_item->getRubrikSelection(CS_DATE_TYPE,'status');
						if ( !empty($date_sel_status) ) {
							$selstatus = $date_sel_status;
						} else {
							$selstatus = 2;
						}
					}
				}else{
					$selstatus = 3;
				}
			}

			// Get data from database
			$dates_manager = $environment->getDatesManager();
			$only_show_array = '';
			if ( empty($only_show_array) ) {
			   $color_array = $dates_manager->getColorArray();
			   $current_context = $environment->getCurrentContextItem();

			   if($this->_display_mode == "calendar" || $this->_display_mode === "calendar_month") {
			   	$dates_manager->setContextLimit($this->_environment->getCurrentContextID());
			   	$dates_manager->setDateModeLimit(2);
			   	$dates_manager->setYearLimit($this->_calendar["year"]);

			   	if($this->_presentation_mode === "month") {
			   		$real_month = mb_substr($this->_calendar["month"],4,2);
			   		$first_char = mb_substr($real_month,0,1);
			   		if ($first_char == '0'){
			   			$real_month = mb_substr($real_month,1,2);
			   		}
			   		$dates_manager->setMonthLimit($real_month);
			   	} else {
			   		$real_month = mb_substr($this->_calendar["month"],4,2);
			   		$first_char = mb_substr($real_month,0,1);
			   		if ($first_char == '0'){
			   			$real_month = mb_substr($real_month,1,2);
			   		}
			   		$dates_manager->setMonthLimit2($real_month);
			   	}

			   	$count_all = $dates_manager->getCountAll();
			   	$dates_manager->resetLimits();
			   	$dates_manager->setSortOrder('time');

			   /*
			  elseif (($seldisplay_mode == 'calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
			      $dates_manager->setContextLimit($environment->getCurrentContextID());
			      $dates_manager->setDateModeLimit(2);
			      $dates_manager->setYearLimit($year);
			      if (!empty($presentation_mode) and $presentation_mode =='2'){
			         $real_month = mb_substr($month,4,2);
			         $first_char = mb_substr($real_month,0,1);
			         if ($first_char == '0'){
			            $real_month = mb_substr($real_month,1,2);
			         }
			         $dates_manager->setMonthLimit($real_month);
			      }else{
			         $real_month = mb_substr($month,4,2);
			         $first_char = mb_substr($real_month,0,1);
			         if ($first_char == '0'){
			            $real_month = mb_substr($real_month,1,2);
			         }
			         $dates_manager->setMonthLimit2($real_month);
			      }
			      $count_all = $dates_manager->getCountAll();
			      $dates_manager->resetLimits();
			      $dates_manager->setSortOrder('time');

			 			   	*/
			   } else {
			      $dates_manager->setContextLimit($environment->getCurrentContextID());
			      $dates_manager->setDateModeLimit(2);
			      $count_all = $dates_manager->getCountAll();
			   }

				// apply filter
				if ( $this->_list_parameter_arrray['sel_activating_status'] == 2 ) {
					$dates_manager->showNoNotActivatedEntries();
				}

				// TODO: should be handles via list parameters
				$selected_color = '';
				if(isset($_GET['selcolor']) && $_GET['selcolor'] != '-2') {
					$selected_color = $_GET['selcolor'];
				}

				if(!empty($selected_color) && $selected_color != 2) {
					$dates_manager->setColorLimit('#' . $selected_color);
				}

				if ( !empty($this->_list_parameter_arrray['ref_iid']) and $this->getViewMode() == 'attached' ){
					$dates_manager->setRefIDLimit($this->_list_parameter_arrray['ref_iid']);
				}

				if ( !empty($this->_list_parameter_arrray['ref_user']) and $this->getViewMode() == 'attached' ){
					$dates_manager->setRefUserLimit($this->_list_parameter_arrray['ref_user']);
				}

				if(	!empty($this->_list_parameter_arrray['sort']) &&
						($this->_display_mode !== 'calendar' || $this->_display_mode === 'calendar_month' || $this->getViewMode() === 'formattach' || $this->getViewMode() === 'detailattach')) {
					$dates_manager->setSortOrder($this->_list_parameter_arrray['sort']);
				}

				if ( !empty($this->_list_parameter_arrray['search']) ) {
					$dates_manager->setSearchLimit($this->_list_parameter_arrray['search']);
				}

				if ( !empty($this->_list_parameter_arrray['selbuzzword']) ) {
					$dates_manager->setBuzzwordLimit($this->_list_parameter_arrray['selbuzzword']);
				}

				if ( !empty($this->_list_parameter_arrray['last_selected_tag']) ){
					$dates_manager->setTagLimit($this->_list_parameter_arrray['last_selected_tag']);
				}

				if ( !empty($selstatus) ) {
					$dates_manager->setDateModeLimit($selstatus);
				}

				// TODO: not sure if this is correct here
				if ( $this->_list_parameter_arrray['interval'] > 0 ) {
					$dates_manager->setIntervalLimit($this->_list_parameter_arrray['from']-1,$this->_list_parameter_arrray['interval']);
				}

				if ( !empty($only_show_array) ) {
					$dates_manager->resetLimits();
					$dates_manager->setWithoutDateModeLimit();
					$dates_manager->setIDArrayLimit($only_show_array);
				}

				$ids = $dates_manager->getIDArray();       // returns an array of item ids
				$count_all_shown = count($ids);

				if(empty($only_show_array)) {
					if($this->_display_mode === "calendar" || $this->_display_mode == "calendar_month") {
						if(!empty($this->_calendar["year"])) $dates_manager->setYearLimit($this->_calendar["year"]);

						if(!empty($this->_calendar["month"])) {
							if($this->_presentation_mode === "month") {
								$real_month = mb_substr($this->_calendar["month"],4,2);
								$first_char = mb_substr($real_month,0,1);
								if ($first_char == '0'){
									$real_month = mb_substr($real_month,1,2);
								}
								$dates_manager->setMonthLimit($real_month);
							} else {
								$real_month = mb_substr($this->_calendar["month"],4,2);
								$first_char = mb_substr($real_month,0,1);
								if ($first_char == '0'){
									$real_month = mb_substr($real_month,1,2);
								}
								$dates_manager->setMonthLimit2($real_month);
							}
						}

						if ( !empty($selstatus) ) {
							$dates_manager->setDateModeLimit($selstatus);
						}
					}

					if ( $this->_list_parameter_arrray['interval'] > 0 ) {
						$dates_manager->setIntervalLimit($this->_list_parameter_arrray['from']-1,$this->_list_parameter_arrray['interval']);
					}
				}

				if($this->_display_mode === "calendar" || $this->_display_mode === "calendar_month") {
					$dates_manager->selectDistinct();
				} else {
					$dates_manager->select();
				}

				$list = $dates_manager->get();

				if($this->_display_mode === "calendar" || $this->_display_mode === "calendar_month") {
					$count_all_shown = $list->getCount();
				}

				$this->_page_text_fragment_array['count_entries'] = $this->getCountEntriesText($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all, $count_all_shown);
				$this->_browsing_icons_parameter_array = $this->getBrowsingIconsParameterArray($this->_list_parameter_arrray['from'],$this->_list_parameter_arrray['interval'], $count_all_shown);

				$session = $this->_environment->getSessionItem();
				$session->setValue('cid'.$environment->getCurrentContextID().'_date_index_ids', $ids);

				if($this->_display_mode == "calendar" || $this->_display_mode === "calendar_month" || $this->_display_mode === "calendar_week") {
					return $list;
				}

			   // prepare item array
			   $item = $list->getFirst();
			   $item_array = array();
				$params = array();
				$params['environment'] = $environment;
				$params['with_modifying_actions'] = false;
				$view = new cs_view($params);
			   while($item) {
			   $assessment_stars_text_array = array('non_active','non_active','non_active','non_active','non_active');
				$current_context = $environment->getCurrentContextItem();
				if($current_context->isAssessmentActive()) {
					$assessment_manager = $environment->getAssessmentManager();
					$assessment = $assessment_manager->getAssessmentForItemAverage($item);
					if(isset($assessment[0])) {
						$assessment = sprintf('%1.1f', (float) $assessment[0]);
					} else {
			 			$assessment = 0;
					}
		  			$php_version = explode('.', phpversion());
					if($php_version[0] >= 5 && $php_version[1] >= 3) {
						// if php version is equal to or above 5.3
						$assessment_count_stars = round($assessment, 0, PHP_ROUND_HALF_UP);
					} else {
						// if php version is below 5.3
						$assessment_count_stars = round($assessment);
					}
					for ($i=0; $i< $assessment_count_stars; $i++){
						$assessment_stars_text_array[$i] = 'active';
					}
				}
			   	$noticed_text = $this->_getItemChangeStatus($item);

				// files
				$attachment_infos = array();
				$file_count = $item->getFileList()->getCount();
				$file_list = $item->getFileList();

				$file = $file_list->getFirst();
				while($file) {
					$lightbox = false;
					if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) $lightbox = true;

					$info = array();
					$info['file_name']	= $converter->text_as_html_short($file->getDisplayName());
					$info['file_icon']	= $file->getFileIcon();
					$info['file_url']	= $file->getURL();
					$info['file_size']	= $file->getFileSize();
					$info['lightbox']	= $lightbox;

					$attachment_infos[] = $info;
					$file = $file_list->getNext();
				}

				$place = $item->getPlace();
				$place = $converter->text_as_html_short($place);

				$parse_time_start = convertTimeFromInput($item->getStartingTime());
				$conforms = $parse_time_start['conforms'];
				if($conforms === true) {
					$time = getTimeLanguage($parse_time_start['datetime']);
				} else {
					$time = $item->getStartingTime();
				}
				$time = $converter->text_as_html_short($time);

				$parse_day_start = convertDateFromInput($item->getStartingDay(), $this->_environment->getSelectedLanguage());
				$conforms = $parse_day_start['conforms'];
				if($conforms === true) {
					$date = $translator->getDateInLang($parse_day_start['datetime']);
				} else {
					$date = $item->getStartingDay();
				}
				$date = $converter->text_as_html_short($date);

				$moddate = $item->getModificationDate();
				if ( $item->getCreationDate() <> $item->getModificationDate() and !strstr($moddate,'9999-00-00')){
         			$mod_date = $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate());
      			} else {
         			$mod_date = $this->_environment->getTranslationObject()->getDateInLang($item->getCreationDate());
      			}
	            $activated_text =  '';
	            $activating_date = $item->getActivatingDate();
	            if (strstr($activating_date,'9999-00-00')){
	               $activated_text = $this->_environment->getTranslationObject()->getMessage('COMMON_NOT_ACTIVATED');
	            }else{
	               $activated_text = $this->_environment->getTranslationObject()->getMessage('COMMON_ACTIVATING_DATE').' '.$this->_environment->getTranslationObject()->getDateInLang($item->getActivatingDate());
	            }
			   	$item_array[] = array(
					'iid'				=> $item->getItemID(),
					'title'				=> $view->_text_as_html_short($item->getTitle()),
					'date'				=> $date,
					'time'				=> $time,
					'color'				=> $item->getColor(),
					'show_time'			=> $item->getStartingTime() !== '',
					'place'				=> $place,
					'assessment_array'  => $assessment_stars_text_array,
					'noticed'			=> $noticed_text,
					'attachment_count'	=> $file_count,
					'attachment_infos'	=> $attachment_infos,
					'activated_text'	=> $activated_text,
					'creator_id'		=> $item->getCreatorItem()->getItemID(),
					'activated'			=> !$item->isNotActivated()
				);

			   	$item = $list->getNext();
			   }


			}

			// append return
			$return = array(
				'items'		=> $item_array,
				'count_all'	=> $count_all_shown
			);
			return $return;
		}

		protected function getAdditionalActions(&$perms) {
			/*
			 * TODO
			 * $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $hash_manager = $this->_environment->getHashManager();
      $params = $this->_environment->getCurrentParameterArray();
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/abbo.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATES_ABBO').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/abbo.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATES_ABBO').'"/>';
      }
      $ical_url = '<a title="'.$this->_translator->getMessage('DATES_ABBO').'"  href="webcal://';
      $ical_url .= $_SERVER['HTTP_HOST'];
      global $c_single_entry_point;
      $ical_url .= str_replace($c_single_entry_point,'ical.php',$_SERVER['PHP_SELF']);
      $ical_url .= '?cid='.$_GET['cid'].'&amp;hid='.$hash_manager->getICalHashForUser($current_user->getItemID()).'">'.$image.'</a>'.LF;
      $html .= $ical_url;
      if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
         $image = '<img src="images/commsyicons_msie6/22x22/export.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATES_EXPORT').'"/>';
      } else {
         $image = '<img src="images/commsyicons/22x22/export.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('DATES_EXPORT').'"/>';
      }
      $html .= '<a title="'.$this->_translator->getMessage('DATES_EXPORT').'"  href="ical.php?cid='.$_GET['cid'].'&amp;hid='.$hash_manager->getICalHashForUser($current_user->getItemID()).'">'.$image.'</a>'.LF;
      unset($params);
      if ( $this->_environment->inPrivateRoom() ) {
         if ( $this->_with_modifying_actions ) {
            $params['import'] = 'yes';
            if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
               $image = '<img src="images/commsyicons_msie6/22x22/import.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_IMS_IMPORT').'"/>';
            } else {
               $image = '<img src="images/commsyicons/22x22/import.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('MATERIAL_IMS_IMPORT').'"/>';
            }
            $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                CS_DATE_TYPE,
                               'import',
                               $params,
                               $image,
                               $this->_translator->getMessage('COMMON_IMPORT_DATES')).LF;
            unset($params);
         } else {
           if(($this->_environment->getCurrentBrowser() == 'MSIE') && (mb_substr($this->_environment->getCurrentBrowserVersion(),0,1) == '6')){
              $image = '<img src="images/commsyicons_msie6/22x22/import_grey.gif" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_IMPORT_DATES').'"/>';
           } else {
              $image = '<img src="images/commsyicons/22x22/import_grey.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_IMPORT_DATES').'"/>';
           }
           $html .= '<a title="'.$this->_translator->getMessage('COMMON_NO_ACTION_NEW',$this->_translator->getMessage('COMMON_IMPORT_DATES')).' "class="disabled">'.$image.'</a>'.LF;
         }
      }
      return $html;
			 */
		}

		protected function getAdditionalListActions() {
			$return = array();
			$return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_COPY, 'display' => '___COMMON_LIST_ACTION_COPY___');
		   $return[] = array('selected' => false, 'disabled' => false, 'id' => '', 'value' => CS_LISTOPTION_DOWNLOAD, 'display' => '___COMMON_LIST_ACTION_DOWNLOAD___');
			return $return;
		}

		private function setSelectedStatus() {
			$current_context = $this->_environment->getCurrentContextItem();

			// find current status selection
			if(isset($_GET['selstatus']) && $_GET['selstatus'] != '-2') {
				$this->_selected_status = $_GET['selstatus'];

				// save selection
				if($current_context->isPrivateRoom()) {
					$date_sel_status = $current_context->getRubrikSelection(CS_DATE_TYPE, 'status');

					if($date_sel_status != $this->_selected_status) {
						$current_context->setRubrikSelection(CS_DATE_TYPE, 'status', $this->_selected_status);
					}
				}
			} else {
				if(	$this->_display_mode == 'calendar' ||
					// TODO?:
					$this->_display_mode == 'calendar_month' /* || $mode == 'formattach' || $mode == 'detailattach' */ ||
					$this->_environment->inPrivateRoom()) {

					$this->_selected_status = 2;

					if($this->_environment->inPrivateRoom()) {
						$date_sel_status = $current_context->getRubrikSelection(CS_DATE_TYPE, 'status');

						if(!empty($date_sel_status)) {
							$this->_selected_status = $date_sel_status;
						} else {
							$this->_selected_status = 2;
						}
					}
				} else {
					$this->_selected_status = 3;
				}
			}
		}

		private function setDisplayMode() {
			$current_user = $this->_environment->getCurrentUserItem();
			$current_context = $this->_environment->getCurrentContextItem();
			$seldisplay_mod = $current_context->getDatesPresentationStatus();
			$session = $this->_environment->getSessionItem();
			$session_manager = $this->_environment->getSessionManager();

			if(isset($_GET['mode'])) {
				$this->_display_mode = $_GET['mode'];
			} elseif(!empty($_GET['presentation_mode'])) {
				if ($_GET['presentation_mode'] == 'month'){
					$this->_display_mode = 'calendar_month';
				}else{
					$this->_display_mode = 'calendar';
				}
			} elseif(!empty($_POST['mode'])) {
				$this->_display_mode = $_POST['mode'];
			} elseif($session->issetValue($this->_environment->getCurrentContextID() . '_dates_seldisplay_mode')) {
				$this->_display_mode = $session->getValue($this->_environment->getCurrentContextID() . '_dates_seldisplay_mode');
			} else {
				$this->_display_mode = $current_context->getDatesPresentationStatus();
				if($this->_display_mode == 'normal'){
				  $this->_display_mode= 'list';
				}
			}
			$session->setValue($this->_environment->getCurrentContextID() . '_dates_seldisplay_mode', $this->_display_mode);
			$session_manager->save($session);
		}

		protected function getAdditionalRestrictionText(){
			$return = array();

			$params = $this->_environment->getCurrentParameterArray();
			$current_context = $this->_environment->getCurrentContextItem();

			if(!isset($params['selstatus']) || $params['selstatus'] === '4' || $params['selstatus'] === '3') {
				$restriction = array(
					'name'				=> '',
					'type'				=> '',
					'link_parameter'	=> ''
				);

				$translator = $this->_environment->getTranslationObject();

				// set name
				if(isset($params['selstatus']) && $params['selstatus'] === '4') {
					$restriction['name'] = $translator->getMessage('DATES_NON_PUBLIC');
				} elseif(!isset($params['selstatus']) || $params['selstatus'] === '3') {
					$restriction['name'] = $translator->getMessage('DATES_PUBLIC');
				}

				// set link parameter
				$params['selstatus'] = 2;
				$link_parameter_text = '';
				if ( count($params) > 0 ) {
					foreach ($params as $key => $parameter) {
						$link_parameter_text .= '&'.$key.'='.$parameter;
					}
				}
				$restriction['link_parameter'] = $link_parameter_text;

				$return[] = $restriction;
			}

			return $return;
		}

		protected function getAdditionalRestrictions() {
			$return = array();

			$restriction = array(
				'item'		=> array(),
				'action'	=> '',
				'hidden'	=> array(),
				'tag'		=> '',
				'name'		=> '',
				'custom'	=> true
			);

			$translator = $this->_environment->getTranslationObject();
			$dates_manager = $this->_environment->getDatesManager();

			// set tag and name
			$tag = $translator->getMessage('COMMON_DATE_STATUS');
			$restriction['tag'] = $tag;
			$restriction['name'] = 'status';

			// set action
			$params = $this->_environment->getCurrentParameterArray();

			if(!isset($params['selstatus'])) {
				unset($params['from']);
			}

			unset($params['selstatus']);
			$link_parameter_text = '';

			$hidden_array = array();
			if(count($params) > 0) {
				foreach($params as $key => $parameter) {
					$link_parameter_text .= '&'.$key.'='.$parameter;
					$hidden_array[] = array(
						'name'	=> $key,
						'value'	=> $parameter
					);
				}
			}
			$restriction['action'] = 'commsy.php?cid='.$this->_environment->getCurrentContextID().'&mod='.$this->_environment->getCurrentModule().'&fct='.$this->_environment->getCurrentFunction().'&'.$link_parameter_text;

			// set hidden
			$restriction['hidden'] = $hidden_array;

			// set items
			$items = array();

			// no selection
			$item = array(
				'id'		=> 2,
				'name'		=> $translator->getMessage('COMMON_NO_SELECTION'),
				'selected'	=> $this->_selected_status
			);
			$items[] = $item;

			// disabled
			$item = array(
				'id'		=> -2,
				'name'		=> '------------------------------',
				'selected'	=> $this->_selected_status,
				'disabled'	=> true
			);
			$items[] = $item;

			// public
			$item = array(
				'id'		=> 3,
				'name'		=> $translator->getMessage('DATES_PUBLIC'),
				'selected'	=> $this->_selected_status
			);
			$items[] = $item;

			// non public
			// public
			$item = array(
				'id'		=> 4,
				'name'		=> $translator->getMessage('DATES_NON_PUBLIC'),
				'selected'	=> $this->_selected_status
			);
			$items[] = $item;


			$restriction['items'] = $items;
			$return[] = $restriction;

			// colors
			$color_array = $dates_manager->getColorArray();
			if(isset($color_array[0])) {
				// find current selected color
				$selected_color = '';
				if(isset($_GET['selcolor']) && $_GET['selcolor'] != '-2') {
					$selected_color = $_GET['selcolor'];
				}

				$restriction = array(
					'item'		=> array(),
					'action'	=> '',
					'hidden'	=> array(),
					'tag'		=> '',
					'name'		=> '',
					'custom'	=> true
				);

				// set tag and name
				$tag = $translator->getMessage('COMMON_DATE_COLOR');
				$restriction['tag'] = $tag;
				$restriction['name'] = 'color';

				// set action
				$params = $this->_environment->getCurrentParameterArray();

				if(!isset($params['selcolor'])) {
					unset($params['from']);
				}

				unset($params['selcolor']);
				$link_parameter_text = '';

				$hidden_array = array();
				if(count($params) > 0) {
					foreach($params as $key => $parameter) {
						$link_parameter_text .= '&'.$key.'='.$parameter;
						$hidden_array[] = array(
							'name'	=> $key,
							'value'	=> $parameter
						);
					}
				}
				$restriction['action'] = 'commsy.php?cid='.$this->_environment->getCurrentContextID().'&mod='.$this->_environment->getCurrentModule().'&fct='.$this->_environment->getCurrentFunction().'&'.$link_parameter_text;

				// set hidden
				$restriction['hidden'] = $hidden_array;

				// set items
				$items = array();

				// no selection
				$item = array(
					'id'		=> 2,
					'name'		=> $translator->getMessage('COMMON_NO_SELECTION'),
					'selected'	=> $this->_selected_status
				);
				$items[] = $item;

				// disabled
				$item = array(
					'id'		=> -2,
					'name'		=> '------------------------------',
					'selected'	=> $this->_selected_status,
					'disabled'	=> true
				);
				$items[] = $item;

				$color_array = $this->_available_color_array;
				foreach($color_array as $color) {
					$color_text = '';
					switch ($color){
						case '#999999': $color_text = getMessage('DATE_COLOR_GREY');break;
						case '#CC0000': $color_text = getMessage('DATE_COLOR_RED');break;
						case '#FF6600': $color_text = getMessage('DATE_COLOR_ORANGE');break;
						case '#FFCC00': $color_text = getMessage('DATE_COLOR_DEFAULT_YELLOW');break;
						case '#FFFF66': $color_text = getMessage('DATE_COLOR_LIGHT_YELLOW');break;
						case '#33CC00': $color_text = getMessage('DATE_COLOR_GREEN');break;
						case '#00CCCC': $color_text = getMessage('DATE_COLOR_TURQUOISE');break;
						case '#3366FF': $color_text = getMessage('DATE_COLOR_BLUE');break;
						case '#6633FF': $color_text = getMessage('DATE_COLOR_DARK_BLUE');break;
						case '#CC33CC': $color_text = getMessage('DATE_COLOR_PURPLE');break;
						default: $color_text = getMessage('DATE_COLOR_UNKNOWN');
					}

					$item = array(
						'id'		=> str_replace('#', '', $color),
						'name'		=> $color_text,
						'selected'	=> str_replace('#', '', $selected_color)
					);
					$items[] = $item;
				}
			}

			$restriction['items'] = $items;

			$return[] = $restriction;

			return $return;
		}


		private function getTooltipDate($date) {
			$translator = $this->_environment->getTranslationObject();

			$parse_time_start = convertTimeFromInput($date->getStartingTime());
			$conforms = $parse_time_start['conforms'];
			if ($conforms == TRUE) {
				$start_time_print = getTimeLanguage($parse_time_start['datetime']);
			} else {
				$start_time_print = $date->getStartingTime();
			}

			$parse_time_end = convertTimeFromInput($date->getEndingTime());
			$conforms = $parse_time_end['conforms'];
			if ($conforms == TRUE) {
				$end_time_print = getTimeLanguage($parse_time_end['datetime']);
			} else {
				$end_time_print = $date->getEndingTime();
			}

			$parse_day_start = convertDateFromInput($date->getStartingDay(),$this->_environment->getSelectedLanguage());
			$conforms = $parse_day_start['conforms'];
			if ($conforms == TRUE) {
				$start_day_print = $date->getStartingDayName().', '.$translator->getDateInLang($parse_day_start['datetime']);
			} else {
				$start_day_print = $date->getStartingDay();
			}

			$parse_day_end = convertDateFromInput($date->getEndingDay(),$this->_environment->getSelectedLanguage());
			$conforms = $parse_day_end['conforms'];
			if ($conforms == TRUE) {
				$end_day_print =$date->getEndingDayName().', '.$translator->getDateInLang($parse_day_end['datetime']);
			} else {
				$end_day_print = $date->getEndingDay();
			}
			//formating dates and times for displaying
			$date_print ="";
			$time_print ="";

			if ($end_day_print != "") { //with ending day
				$date_print = $translator->getMessage('DATES_AS_OF').' '.$start_day_print.' '.$translator->getMessage('DATES_TILL').' '.$end_day_print;
				if ($parse_day_start['conforms']
						and $parse_day_end['conforms']) { //start and end are dates, not strings
					$date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$translator->getMessage('DATES_DAYS').')';
				}

				if ($start_time_print != "" and $end_time_print =="") { //starting time given
					$time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
					if ($parse_time_start['conforms'] == true) {
						$time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
				} elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
					$time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
					if ($parse_time_end['conforms'] == true) {
						$time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
				} elseif ($start_time_print != "" and $end_time_print !="") { //all times given
					if ($parse_time_end['conforms'] == true) {
						$end_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
					if ($parse_time_start['conforms'] == true) {
						$start_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
					$date_print = $translator->getMessage('DATES_AS_OF').' '.$start_day_print.', '.$start_time_print.'<br />'.
							$translator->getMessage('DATES_TILL').' '.$end_day_print.', '.$end_time_print;
					if ($parse_day_start['conforms']
							and $parse_day_end['conforms']) {
						$date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$translator->getMessage('DATES_DAYS').')';
					}
				}

			} else { //without ending day
				$date_print = $translator->getMessage('DATES_ON_DAY').' '.$start_day_print;
				if ($start_time_print != "" and $end_time_print =="") { //starting time given
					$time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
					if ($parse_time_start['conforms'] == true) {
						$time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
				} elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
					$time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
					if ($parse_time_end['conforms'] == true) {
						$time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
				} elseif ($start_time_print != "" and $end_time_print !="") { //all times given
					if ($parse_time_end['conforms'] == true) {
						$end_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
					if ($parse_time_start['conforms'] == true) {
						$start_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
					}
					$time_print = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$translator->getMessage('DATES_TILL').' '.$end_time_print;
				}
			}

			if ($parse_day_start['timestamp'] == $parse_day_end['timestamp'] and $parse_day_start['conforms'] and $parse_day_end['conforms']) {
				$date_print = $translator->getMessage('DATES_ON_DAY').' '.$start_day_print;
				if ($start_time_print != "" and $end_time_print =="") { //starting time given
					$time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
				} elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
					$time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
				} elseif ($start_time_print != "" and $end_time_print !="") { //all times given
					$time_print = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$translator->getMessage('DATES_TILL').' '.$end_time_print;
				}
			}

			// Date and time
			$temp_array = array();
			$temp_array[] = $translator->getMessage('DATES_DATETIME');
			if ($time_print != '') {
				$temp_array[] = $date_print.BRLF.$time_print;
			} else {
				$temp_array[] = $date_print;
			}
			$tooltip_date = $temp_array;
			return $tooltip_date;
		}

		private function getDateItemLinkWithJavascript($item, $text) {
			$title = $item->getTitle();
			$params = array();
			$params['iid'] = $item->getItemID();
			$params['mode'] = 'private';
			$parameter_array = $this->_environment->getCurrentParameterArray();
			if (isset ($parameter_array['year'])){
				$params['year'] = $parameter_array['year'];
			}
			if (isset ($parameter_array['month'])){
				$params['month'] = $parameter_array['month'];
			}
			if (isset ($parameter_array['week'])){
				$params['week'] = $parameter_array['week'];
			}
			if (isset ($parameter_array['presentation_mode'])){
				$params['presentation_mode'] = $parameter_array['presentation_mode'];
			}
			$link_color = '#000000';
			if ($item->getColor() != ''){
				if(($item->getColor() == '#3366FF')
						or ($item->getColor() == '#6633FF')
						or ($item->getColor() == '#CC33CC')
						or ($item->getColor() == '#CC0000')
						or ($item->getColor() == '#FF6600')
						or ($item->getColor() == '#00CCCC')
						or ($item->getColor() == '#999999')){
					$link_color = '#FFFFFF';
				}
			}
			if ( $item->issetPrivatDate() ){
				$title ='<i>'.$title.'</i>'; // ???
				$title = ahref_curl( $item->getContextID(),
						CS_DATE_TYPE,
						'detail',
						$params,
						$text,
						'',
						'',
						'',
						'',
						'',
						'calendar_link_' . $params['iid'],
						'style="color:' . $link_color .';"');
			}else{
				$title = ahref_curl( $item->getContextID(),
						CS_DATE_TYPE,
						'detail',
						$params,
						$text,
						'',
						'',
						'',
						'',
						'',
						'calendar_link_' . $params['iid'],
						'style="color:' . $link_color .';"');

			}
			unset($params);
			return $title;
		}
	}