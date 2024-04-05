<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

/* upper class of the dates item
 */

use App\Entity\Dates;
use App\Event\ItemDeletedEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

/** class for a dates
 * this class implements a dates item.
 */
class cs_dates_item extends cs_item
{
    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param object  environment            environment of commsy
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_type = CS_DATE_TYPE;
    }

    /** Checks and sets the data of the item.
     *
     * @param $data_array
     *
     * @author CommSy Development Group
     */
    public function _setItemData($data_array): void
    {
        $this->_data = $data_array;
    }

    /** get title of a dates
     * this method returns the title of the dates.
     *
     * @return string title of a dates
     *
     * @author CommSy Development Group
     */
    public function getTitle(): string
    {
        if ('-1' == $this->getPublic()) {
            $translator = $this->_environment->getTranslationObject();

            return $translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE');
        } else {
            return $this->_getValue('title');
        }
    }

    /** set title of a dates
     * this method sets the title of the dates.
     *
     * @param string value title of the dates
     *
     * @author CommSy Development Group
     */
    public function setTitle(string $value): void
    {
        // sanitize title
        $converter = $this->_environment->getTextConverter();
        $value = htmlentities($value);
        $value = $converter->sanitizeHTML($value);
        $this->_setValue('title', $value);
    }

    /** set date and time of start in the database time format
     * this method sets the starting datetime of the dates.
     *
     * @param string value starting datetime of the dates
     *
     * @author CommSy Development Group
     */
    public function setDateTime_start($value)
    {
        $this->_setValue('datetime_start', $value);
    }

    /** get date and time of start in the database time format
     * this method returns the starting datetime of the dates.
     *
     * @return string starting datetime of the dates
     *
     * @author CommSy Development Group
     */
    public function getDateTime_start()
    {
        return $this->_getValue('datetime_start');
    }

    /** get date and time of start as a proper \DateTime object
     * this method returns the starting datetime of the dates.
     *
     * @return \DateTime starting datetime of the dates
     *
     * @author CommSy Development Group
     */
    public function getDateTimeObject_start()
    {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $this->_getValue('datetime_start'));
    }

    /** set date and time of end in the database time format
     * this method sets the ending datetime of the dates.
     *
     * @param string value ending datetime of the dates
     *
     * @author CommSy Development Group
     */
    public function setDateTime_end($value)
    {
        $this->_setValue('datetime_end', $value);
    }

    /** get date and time of end in the database time format
     * this method returns the ending datetime of the dates.
     *
     * @return string ending datetime of the dates
     *
     * @author CommSy Development Group
     */
    public function getDateTime_end()
    {
        return $this->_getValue('datetime_end');
    }

     /** get date and time of end as a proper \DateTime object
      * this method returns the ending datetime of the dates.
      *
      * @return \DateTime ending datetime of the dates
      *
      * @author CommSy Development Group
      */
     public function getDateTimeObject_end()
     {
         return \DateTime::createFromFormat('Y-m-d H:i:s', $this->_getValue('datetime_end'));
     }

    /** get description of a dates
     * this method returns the description of the dates.
     *
     * @return string description of a dates
     */
    public function getDescription()
    {
        if ('-1' == $this->getPublic()) {
            $translator = $this->_environment->getTranslationObject();

            return $translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION');
        } else {
            return $this->_getValue('description');
        }
    }

    /** set description of a dates
     * this method sets the description of the dates.
     *
     * @param string value description of the dates
     *
     * @author CommSy Development Group
     */
    public function setDescription($value)
    {
        // sanitize description
        $converter = $this->_environment->getTextConverter();
        $value = $converter->sanitizeFullHTML($value);
        $this->_setValue('description', $value);
    }

    /** set ending  day of a dates
     * this method sets the starting day of the dates.
     *
     * @param string value starting day of the dates
     *
     * @author CommSy Development Group
     */
    public function setEndingDay($value)
    {
        $this->_setValue('end_day', $value);
    }

    /** get ending day of a dates
     * this method returns the ending day of the dates.
     *
     * @return string ending day of a dates
     *
     * @author CommSy Development Group
     */
    public function getEndingDay()
    {
        return $this->_getValue('end_day');
    }

    /** set ending time of a dates
     * this method sets the ending time of the dates.
     *
     * @param string value ending time of the dates
     *
     * @author CommSy Development Group
     */
    public function setEndingTime($value)
    {
        $this->_setValue('end_time', $value);
    }

    /** get ending time of a dates
     * this method returns the ending time of the dates.
     *
     * @return string ending time of a dates
     *
     * @author CommSy Development Group
     */
    public function getEndingTime()
    {
        return $this->_getValue('end_time');
    }

    /** set starting day of a dates
     * this method sets the starting day of the dates.
     *
     * @param string value starting day of the dates
     *
     * @author CommSy Development Group
     */
    public function setStartingDay($value)
    {
        $this->_setValue('start_day', $value);
    }

    /** get starting day of a dates
     * this method returns the starting day of the dates.
     *
     * @return string starting day of a dates
     *
     * @author CommSy Development Group
     */
    public function getStartingDay()
    {
        return $this->_getValue('start_day');
    }

    public function setShownStartingDay($value)
    {
        $this->_setValue('shown_start_day', $value);
    }

    public function getShownStartingDay()
    {
        return $this->_getValue('shown_start_day');
    }

    public function setShownStartingTime($value)
    {
        $this->_setValue('shown_start_time', $value);
    }

    public function getShownStartingTime()
    {
        return $this->_getValue('shown_start_time');
    }

    public function getStartingDayName()
    {
        return getDayNameFromInt(date('w', strtotime($this->getStartingDay())));
    }

    public function getEndingDayName()
    {
        return getDayNameFromInt(date('w', strtotime($this->getEndingDay())));
    }

    /** set starting time of a dates
     * this method sets the starting time of the dates.
     *
     * @param string value starting time of the dates
     *
     * @author CommSy Development Group
     */
    public function setStartingTime($value)
    {
        $this->_setValue('start_time', $value);
    }

    /** get starting time of a dates
     * this method returns the starting time of the dates.
     *
     * @return string starting time of a dates
     *
     * @author CommSy Development Group
     */
    public function getStartingTime()
    {
        return $this->_getValue('start_time');
    }

    /** set place of a dates
     * this method sets the place of the dates.
     *
     * @param string value place of the dates
     *
     * @author CommSy Development Group
     */
    public function setPlace($value)
    {
        $this->_setValue('place', $value);
    }

    /** get place of a dates
     * this method returns the place of the dates.
     *
     * @return string place of a dates
     *
     * @author CommSy Development Group
     */
    public function getPlace()
    {
        if ('-1' == $this->getPublic()) {
            return '';
        } else {
            return $this->_getValue('place');
        }
    }

    /** set date_mode status of a dates
     * this method sets the date_mode status of the dates.
     *
     * @param string value date_mode status of the dates
     *
     * @author CommSy Development Group
     */
    public function setDateMode($value)
    {
        $this->_setValue('date_mode', $value);
    }

    /** get date_mode status of a dates
     * this method returns the date_mode status of the dates.
     *
     * @return string date_mode status of a dates
     *
     * @author CommSy Development Group
     */
    public function getDateMode()
    {
        return $this->_getValue('date_mode');
    }

    /** set color of a dates
     * this method sets the color of the dates.
     *
     * @param string value color of the dates
     *
     * @author CommSy Development Group
     */
    public function setColor($value)
    {
        $this->_setValue('color', $value);
    }

    /** get color of a dates
     * this method returns the color of the dates.
     *
     * @return string color of a dates
     *
     * @author CommSy Development Group
     */
    public function getColor()
    {
        return $this->_getValue('color');
    }

     /** set calendar_id of a dates
      * this method sets the calendar_id of the dates.
      *
      * @param string value calendar_id of the dates
      *
      * @author CommSy Development Group
      */
     public function setCalendarId($value)
     {
         $this->_setValue('calendar_id', $value);
     }

     /** get calendar_id of a dates
      * this method returns the calendar_id of the dates.
      *
      * @return string calendar_id of a dates
      *
      * @author CommSy Development Group
      */
     public function getCalendarId()
     {
         return $this->_getValue('calendar_id');
     }

     /**
      * @return \App\Entity\Calendars
      *
      * @throws Exception
      */
     public function getCalendar()
     {
         global $symfonyContainer;
         $calendarsService = $symfonyContainer->get('commsy.calendars_service');

         return $calendarsService->getCalendar($this->getCalendarId())[0];
     }

    /** set recurrence_id of a date
     * this method sets the recurrence_id of the date.
     *
     * @param string value recurrence_id of the date
     *
     * @author CommSy Development Group
     */
    public function setRecurrenceId($value)
    {
        $this->_setValue('recurrence_id', $value);
    }

    /** get recurrence_id of a date
     * this method returns the recurrence_id of the date.
     *
     * @return string recurrence_id of a date
     *
     * @author CommSy Development Group
     */
    public function getRecurrenceId()
    {
        return $this->_getValue('recurrence_id');
    }

    /** set recurrence_pattern of a date
     * this method sets the recurrence_pattern of the date.
     *
     * @param string value recurrence_pattern of the date
     *
     * @author CommSy Development Group
     */
    public function setRecurrencePattern($value)
    {
        $this->_setValue('recurrence_pattern', $value);
    }

    /** get recurrence_pattern of a date
     * this method returns the recurrence_pattern of the date.
     *
     * @return array recurrence_pattern of a date
     *
     * @author CommSy Development Group
     */
    public function getRecurrencePattern()
    {
        return $this->_getValue('recurrence_pattern');
    }

    public function issetPrivatDate()
    {
        return 1 == $this->_getValue('date_mode');
    }

    public function getParticipantsItemList(): cs_list
    {
        $members = new cs_list();
        $member_ids = $this->getLinkedItemIDArray(CS_USER_TYPE);
        if (!empty($member_ids)) {
            $user_manager = $this->_environment->getUserManager();
            $user_manager->setIDArrayLimit($member_ids);
            $user_manager->select();
            $members = $user_manager->get();
        }
        // returns a cs_list of user_items
        return $members;
    }

    public function isParticipant($user)
    {
        $link_member_list = $this->getLinkItemList(CS_USER_TYPE);
        $link_member_item = $link_member_list->getFirst();
        $is_member = false;
        while ($link_member_item) {
            $linked_user_id = $link_member_item->getLinkedItemID($this);
            if ($user->getItemID() == $linked_user_id) {
                $is_member = true;
                break;
            }
            $link_member_item = $link_member_list->getNext();
        }

        return $is_member;
    }

    public function addParticipant($user)
    {
        if (!$this->isParticipant($user)) {
            $link_manager = $this->_environment->getLinkItemManager();
            $link_item = $link_manager->getNewItem();
            $link_item->setFirstLinkedItem($this);
            $link_item->setSecondLinkedItem($user);
            $link_item->save();
        }
    }

    public function removeParticipant($user)
    {
        $link_member_list = $this->getLinkItemList(CS_USER_TYPE);
        $link_member_item = $link_member_list->getFirst();
        while ($link_member_item) {
            $linked_user_id = $link_member_item->getLinkedItemID($this);
            if ($user->getItemID() == $linked_user_id) {
                $link_member_item->delete();
            }
            $link_member_item = $link_member_list->getNext();
        }
    }

    /** Checks the data of the item.
     *
     * @return bool TRUE if data is valid FALSE otherwise
     */
    public function isValid()
    {
        // mandatory fields set?
        $title = $this->getTitle();
        $start_day = $this->getStartingDay();

        return parent::isValid()
                 and !empty($title)
                 and !empty($start_day);
    }

    public function save(): void
    {
        $dates_mananger = $this->_environment->getDatesManager();
        $this->_save($dates_mananger);
        $this->_saveFiles();     // this must be done before saveFileLinks
        $this->_saveFileLinks(); // this must be done after saving so we can be sure to have an item id

        $this->updateElastic();
    }

     public function updateElastic()
     {
         global $symfonyContainer;
         $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_date');
         $em = $symfonyContainer->get('doctrine.orm.entity_manager');
         $repository = $em->getRepository(Dates::class);

         $this->replaceElasticItem($objectPersister, $repository);
     }

     public function delete()
     {
         global $symfonyContainer;

         /** @var EventDispatcher $eventDispatcher */
         $eventDispatcher = $symfonyContainer->get('event_dispatcher');

         $itemDeletedEvent = new ItemDeletedEvent($this);
         $eventDispatcher->dispatch($itemDeletedEvent, ItemDeletedEvent::NAME);

         $date_manager = $this->_environment->getDatesManager();
         $this->_delete($date_manager);

         // delete associated annotations
         $this->deleteAssociatedAnnotations();

         $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_date');
         $em = $symfonyContainer->get('doctrine.orm.entity_manager');
         $repository = $em->getRepository(Dates::class);

         $this->deleteElasticItem($objectPersister, $repository);
     }

    /** asks if item is editable by everybody or just creator.
     *
     * @param value
     *
     * @author CommSy Development Group
     */
    public function isPublic(): bool
    {
        if (1 == $this->_getValue('public')) {
            return true;
        } else {
            return false;
        }
    }

    /** sets if announcement is editable by everybody or just creator.
     *
     * @param value
     *
     * @author CommSy Development Group
     */
    public function setPublic($value): void
    {
        $this->_setValue('public', $value);
    }

    public function copy()
    {
        $copy = $this->cloneCopy();
        $copy->setItemID('');
        $copy->setFileList($this->_copyFileList());
        $copy->setContextID($this->_environment->getCurrentContextID());
        $user = $this->_environment->getCurrentUserItem();
        $copy->setCreatorItem($user);
        $copy->setModificatorItem($user);
        $copy->setCalendarId($this->_environment->getCurrentContextItem()->getDefaultCalendarId());
        $list = new cs_list();
        $copy->setGroupList($list);
        $copy->setTopicList($list);
        $copy->save();

        return $copy;
    }

    public function cloneCopy()
    {
        $clone_item = clone $this; // "clone" needed for php5
        $group_list = $this->getGroupList();
        $clone_item->setGroupList($group_list);
        $topic_list = $this->getTopicList();
        $clone_item->setTopicList($topic_list);

        return $clone_item;
    }

     /** get full description of date and time.
      *
      * @author CommSy Development Group
      */
     public function getDateDescription()
     {
         $converter = $this->_environment->getTextConverter();
         $translator = $this->_environment->getTranslationObject();

         // set up style of days and times
         // time
         $parse_time_start = convertTimeFromInput($this->getStartingTime());
         $conforms = $parse_time_start['conforms'];
         if (true === $conforms) {
             $start_time_print = getTimeLanguage($parse_time_start['datetime']);
         } else {
             // TODO: compareWithSearchText
             $start_time_print = $converter->text_as_html_short($this->getStartingTime());
         }

         $parse_time_end = convertTimeFromInput($this->getEndingTime());
         $conforms = $parse_time_end['conforms'];
         if (true === $conforms) {
             $end_time_print = getTimeLanguage($parse_time_end['datetime']);
         } else {
             // TODO: compareWithSearchText
             $end_time_print = $converter->text_as_html_short($this->getEndingTime());
         }
         // day
         $parse_day_start = convertDateFromInput($this->getStartingDay(), $this->_environment->getSelectedLanguage());
         $conforms = $parse_day_start['conforms'];
         if (true === $conforms) {
             $start_day_print = $this->getStartingDayName().', '.$translator->getDateInLang($parse_day_start['datetime']);
         } else {
             // TODO: compareWithSearchText
             $start_day_print = $converter->text_as_html_short($this->getStartingDay());
         }

         $parse_day_end = convertDateFromInput($this->getEndingDay(), $this->_environment->getSelectedLanguage());
         $conforms = $parse_day_end['conforms'];
         if (true === $conforms) {
             $end_day_print = $this->getEndingDayName().', '.$translator->getDateInLang($parse_day_end['datetime']);
         } else {
             // TODO: compareWithSearchText
             $end_day_print = $converter->text_as_html_short($this->getEndingDay());
         }

         // formate dates and times for displaying
         $date_print = '';
         $time_print = '';

         if ('' !== $end_day_print) {
             // with ending day
             $date_print = $translator->getMessage('DATES_AS_OF').' '.$start_day_print.' '.$translator->getMessage('DATES_TILL').' '.$end_day_print;
             if ($parse_day_start['conforms'] && $parse_day_end['conforms']) {
                 // start and end are dates, not string <- ???
                 $date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$translator->getMessage('DATES_DAYS').')';
             }

             if ('' !== $start_time_print && '' === $end_time_print && !$this->isWholeDay()) {
                 // only start time given
                 $time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;

                 if (true === $parse_time_start['conforms']) {
                     $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                 }
             } elseif ('' === $start_time_print && '' !== $end_time_print && !$this->isWholeDay()) {
                 // only end time given
                 $time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;

                 if (true === $parse_time_end['conforms']) {
                     $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                 }
             } elseif ('' !== $start_time_print && '' !== $end_time_print) {
                 // all times given
                 if (!$this->isWholeDay()) {
                     if (true === $parse_time_end['conforms']) {
                         $end_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                     }

                     if (true === $parse_time_start['conforms']) {
                         $start_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                     }

                     $date_print = $translator->getMessage('DATES_AS_OF').' '.$start_day_print.', '.$start_time_print.' '.
                                   $translator->getMessage('DATES_TILL').' '.$end_day_print.', '.$end_time_print;
                 } else {
                     $date_print = $translator->getMessage('DATES_AS_OF').' '.$start_day_print.' '.
                                   $translator->getMessage('DATES_TILL').' '.$end_day_print;
                 }

                 if ($parse_day_start['conforms'] && $parse_day_end['conforms']) {
                     $date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$translator->getMessage('DATES_DAYS').')';
                 }
             }
         } else {
             // without ending day
             $date_print = $translator->getMessage('DATES_ON_DAY_UPPER').' '.$start_day_print;

             if ('' !== $start_time_print && '' == $end_time_print && !$this->isWholeDay()) {
                 // starting time given
                 $time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;

                 if (true === $parse_time_start['conforms']) {
                     $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                 }
             } elseif ('' === $start_time_print && '' !== $end_time_print && !$this->isWholeDay()) {
                 // end time given
                 $time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;

                 if (true === $parse_time_end['conforms']) {
                     $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                 }
             } elseif ('' !== $start_time_print && '' !== $end_time_print && !$this->isWholeDay()) {
                 // all times given
                 if (true === $parse_time_end['conforms']) {
                     $end_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                 }

                 if (true === $parse_time_start['conforms']) {
                     $start_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                 }

                 $time_print = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$translator->getMessage('DATES_TILL').' '.$end_time_print;
             }
         }

         if ($parse_day_start['timestamp'] === $parse_day_end['timestamp'] && $parse_day_start['conforms'] && $parse_day_end['conforms']) {
             $date_print = $translator->getMessage('DATES_ON_DAY_UPPER').' '.$start_day_print;

             if (!$this->isWholeDay()) {
                 if ('' !== $start_time_print && '' === $end_time_print) {
                     // starting time given
                     $time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
                 } elseif ('' === $start_time_print && '' !== $end_time_print) {
                     // endtime given
                     $time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
                 } elseif ('' !== $start_time_print && '' !== $end_time_print) {
                     // all times given
                     $time_print = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$translator->getMessage('DATES_TILL').' '.$end_time_print;
                 }
             }
         }

         // date and time
         $datetime = $date_print;
         if ('' !== $time_print) {
             $datetime .= ' '.$time_print;
         }

         return $datetime;
     }

     public function getDateListDescription()
     {
         $converter = $this->_environment->getTextConverter();
         $translator = $this->_environment->getTranslationObject();

         // set up style of days and times
         // time
         $parse_time_start = convertTimeFromInput($this->getStartingTime());
         $conforms = $parse_time_start['conforms'];
         if (true === $conforms) {
             $start_time_print = getTimeLanguage($parse_time_start['datetime']);
         } else {
             // TODO: compareWithSearchText
             $start_time_print = $converter->text_as_html_short($this->getStartingTime());
         }

         $parse_time_end = convertTimeFromInput($this->getEndingTime());
         $conforms = $parse_time_end['conforms'];
         if (true === $conforms) {
             $end_time_print = getTimeLanguage($parse_time_end['datetime']);
         } else {
             // TODO: compareWithSearchText
             $end_time_print = $converter->text_as_html_short($this->getEndingTime());
         }
         // day
         $parse_day_start = convertDateFromInput($this->getStartingDay(), $this->_environment->getSelectedLanguage());
         $conforms = $parse_day_start['conforms'];
         if (true === $conforms) {
             $start_day_print = $translator->getDateInLang($parse_day_start['datetime']);
         } else {
             // TODO: compareWithSearchText
             $start_day_print = $converter->text_as_html_short($this->getStartingDay());
         }

         $parse_day_end = convertDateFromInput($this->getEndingDay(), $this->_environment->getSelectedLanguage());
         $conforms = $parse_day_end['conforms'];
         if (true === $conforms) {
             $end_day_print = $translator->getDateInLang($parse_day_end['datetime']);
         } else {
             // TODO: compareWithSearchText
             $end_day_print = $converter->text_as_html_short($this->getEndingDay());
         }

         // formate dates and times for displaying
         $date_print = '';
         $time_print = '';

         if ('' !== $end_day_print) {
             // with ending day
             $date_print = $start_day_print.' '.$translator->getMessage('DATES_TILL').' '.$end_day_print;
             if ($parse_day_start['conforms'] && $parse_day_end['conforms']) {
                 // start and end are dates, not string <- ???
                 $date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$translator->getMessage('DATES_DAYS').')';
             }

             if ('' !== $start_time_print && '' === $end_time_print && !$this->isWholeDay()) {
                 // only start time given
                 $time_print = $start_time_print;

                 if (true === $parse_time_start['conforms']) {
                     $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                 }
             } elseif ('' === $start_time_print && '' !== $end_time_print && !$this->isWholeDay()) {
                 // only end time given
                 $time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;

                 if (true === $parse_time_end['conforms']) {
                     $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                 }
             } elseif ('' !== $start_time_print && '' !== $end_time_print) {
                 // all times given
                 if (!$this->isWholeDay()) {
                     if (true === $parse_time_end['conforms']) {
                         $end_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                     }

                     if (true === $parse_time_start['conforms']) {
                         $start_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                     }

                     $date_print = $start_day_print.', '.$start_time_print.' '.
                                   $translator->getMessage('DATES_TILL').' '.$end_day_print.', '.$end_time_print;
                 } else {
                     $date_print = $start_day_print.' '.
                                   $translator->getMessage('DATES_TILL').' '.$end_day_print;
                 }
                 if ($parse_day_start['conforms'] && $parse_day_end['conforms']) {
                     $date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$translator->getMessage('DATES_DAYS').')';
                 }
             }
         } else {
             // without ending day
             $date_print = $start_day_print;

             if ('' !== $start_time_print && '' == $end_time_print && !$this->isWholeDay()) {
                 // starting time given
                 $time_print = $start_time_print;

                 if (true === $parse_time_start['conforms']) {
                     $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                 }
             } elseif ('' === $start_time_print && '' !== $end_time_print && !$this->isWholeDay()) {
                 // end time given
                 $time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;

                 if (true === $parse_time_end['conforms']) {
                     $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                 }
             } elseif ('' !== $start_time_print && '' !== $end_time_print && !$this->isWholeDay()) {
                 // all times given
                 if (true === $parse_time_end['conforms']) {
                     $end_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                 }

                 if (true === $parse_time_start['conforms']) {
                     $start_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
                 }

                 if ($start_time_print === $end_time_print) {
                     $time_print = $translator->getMessage('DATES_AT_TIME').' '.$start_time_print;
                 } else {
                     $time_print = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$translator->getMessage('DATES_TILL').' '.$end_time_print;
                 }
             }
         }

         if ($parse_day_start['timestamp'] === $parse_day_end['timestamp'] && $parse_day_start['conforms'] && $parse_day_end['conforms']) {
             $date_print = $translator->getMessage('DATES_ON_DAY_UPPER').' '.$start_day_print;

             if (!$this->isWholeDay()) {
                 if ('' !== $start_time_print && '' === $end_time_print) {
                     // starting time given
                     $time_print = $start_time_print;
                 } elseif ('' === $start_time_print && '' !== $end_time_print) {
                     // endtime given
                     $time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
                 } elseif ('' !== $start_time_print && '' !== $end_time_print) {
                     // all times given
                     if ($start_time_print === $end_time_print) {
                         $time_print = $translator->getMessage('DATES_AT_TIME').' '.$start_time_print;
                     } else {
                         $time_print = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$translator->getMessage('DATES_TILL').' '.$end_time_print;
                     }
                 }
             }
         }

         // date and time
         $datetime = $date_print;
         if ('' !== $time_print) {
             $datetime .= ' '.$time_print;
         }

         return trim((string) $datetime);
     }

     /** asks if item is a date in an external calendar.
      *
      * @param value
      *
      * @author CommSy Development Group
      */
     public function isExternal()
     {
         if (1 == $this->_getValue('external')) {
             return true;
         } else {
             return false;
         }
     }

     /** sets if item is a date in an external calendar.
      *
      * @param value
      *
      * @author CommSy Development Group
      */
     public function setExternal($value)
     {
         $this->_setValue('external', $value);
     }

     public function getUid()
     {
         return $this->_getValue('uid');
     }

     public function setUid($value)
     {
         $this->_setValue('uid', $value);
     }

     /** asks if item is a date is a whole day date.
      *
      * @param value
      *
      * @author CommSy Development Group
      */
     public function isWholeDay()
     {
         if (1 == $this->_getValue('whole_day')) {
             return true;
         } else {
             return false;
         }
     }

     /** sets if item is a whole day date.
      *
      * @param value
      *
      * @author CommSy Development Group
      */
     public function setWholeDay($value)
     {
         $this->_setValue('whole_day', $value);
     }

     public function getDateTime_recurrence()
     {
         return $this->_getValue('datetime_recurrence');
     }

     public function setDateTime_recurrence($value)
     {
         $this->_setValue('datetime_recurrence', $value);
     }
}
