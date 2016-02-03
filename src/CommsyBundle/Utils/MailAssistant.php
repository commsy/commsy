<?php

namespace CommsyBundle\Utils;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

use Symfony\Component\Translation\DataCollectorTranslator;

use \Twig_Environment;

class MailAssistant
{
    private $legacyEnvironment;
    private $translator;
    private $twig;

    public function __construct(LegacyEnvironment $legacyEnvironment, DataCollectorTranslator $translator, Twig_Environment $twig)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->translator = $translator;
        $this->twig = $twig;
    }

    public function prepareMessage($item)
    {
        $currentContextItem = $this->legacyEnvironment->getCurrentContextItem();

        return $this->twig->render('CommsyBundle:Mail:send.html.twig', [
            'contextItem' => $currentContextItem,
            'item' => $item,
            'content' => $this->generateMessageContent($item),
        ]);
    }

    private function generateMessageContent($item)
    {
        $content = '';

        $type = $item->getType();

        if ($type === 'date') {
            /*
            // set up style of days and times
            $parse_time_start = convertTimeFromInput($item->getStartingTime());
            $conforms = $parse_time_start['conforms'];
            if ($conforms == TRUE) {
                $start_time_print = getTimeLanguage($parse_time_start['datetime']);
            } else {
                $start_time_print = $item->getStartingTime();
            }

            $parse_time_end = convertTimeFromInput($item->getEndingTime());
            $conforms = $parse_time_end['conforms'];
            if ($conforms == TRUE) {
                $end_time_print = getTimeLanguage($parse_time_end['datetime']);
            } else {
                $end_time_print = $item->getEndingTime();
            }

            $parse_day_start = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
            $conforms = $parse_day_start['conforms'];
            if ($conforms == TRUE) {
                $start_day_print = getDateInLang($parse_day_start['datetime']);
            } else {
                $start_day_print = $item->getStartingDay();
            }

            $parse_day_end = convertDateFromInput($item->getEndingDay(),$this->_environment->getSelectedLanguage());
            $conforms = $parse_day_end['conforms'];
            if ($conforms == TRUE) {
                $end_day_print =getDateLanguage($parse_day_end['datetime']);
            } else {
                $end_day_print =$item->getEndingDay();
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
                $date_print = $start_day_print;
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
            $dates_content = '';
            $dates_content = $translator->getMessage('DATES_DATETIME').': '.$item_name.LF;
            if ($time_print != '') {
                $dates_content .= $translator->getMessage('COMMON_TIME').': '.$date_print.','.$time_print.LF;
            } else {
                $dates_content .= $translator->getMessage('COMMON_TIME').': '.$date_print.LF;
            }
            // Place
            $place = $item->getPlace();
            if (!empty($place)) {
                $dates_content .= $translator->getMessage('DATES_PLACE').': ';
                $dates_content .= $place.LF;
            }
            $content = $dates_content;

            */
        } elseif ($type === 'discussion' || $type === 'discussions') {
            /*
                $discussion_content = $translator->getMessage('COMMON_DISCUSSION').': '.$item->getTitle().LF;
            $article_count = $item->getAllArticlesCount();
            $discussion_content .= $translator->getMessage('DISCUSSION_DISCARTICLE_COUNT').': '.$article_count.LF;
            $time = $item->getLatestArticleModificationDate();
            $discussion_content .= $translator->getMessage('DISCUSSION_LAST_ENTRY').': '.getDateTimeInLang($time).LF;
            $content = $discussion_content;

            */
        } elseif ($type === 'material' | $type === 'materials') {
            $content = $this->translator->transChoice('material', 0, [], 'rubric');
            $content .= ': ' . $item->getTitle();
        } elseif ($type === 'announcement') {
            $content = $this->translator->transChoice('announcement', 0, [], 'rubric');
            $content .= ': ' . $item->getTitle();
        } elseif ($type === 'label' || $type === 'labels') {
            $labelType = $item->getLabelType();

            if ($labelType === 'group' || $labelType === 'groups') {
                $content = $this->translator->transChoice('group', 0, [], 'rubric');
                $content .= ': ' . $item->getTitle();
            } else if ($labelType === 'institution' || $labelType === 'institutions') {
                $content = $this->translator->transChoice('institution', 1, [], 'rubric');
                $content .= ': ' . $item->getTitle();
            }
        }

        return $content;
    }
}

/*

    public function actionInit() {
        
       
        // context information
        $contextInformation = array();
        $contextInformation["name"] = $current_context->getTitle();
        $response['context'] = $contextInformation;

        // group information
        $groupArray = $this->getAllLabelsByType("group");

        // institutions information
        $institutionArray = $this->getAllLabelsByType("institution");

        // receiver
        $showAttendees = false;

        if ($module === CS_DATE_TYPE) {
            $showAttendees = true;
            $attendeeType = CS_DATE_TYPE;
        }
        if ($module === CS_TODO_TYPE) {
            $showAttendees = true;
            $attendeeType = CS_TODO_TYPE;
        }
        
        $response['showAttendees'] = $showAttendees;
        $response['attendeeType'] = $attendeeType;


        $showGroupRecipients = false;
        $showInstitutionRecipients = false;
        if ( $this->_environment->inProjectRoom() and !empty($groupArray) ) {
            if ( $current_context->withRubric(CS_GROUP_TYPE) ) {
                $showGroupRecipients = true;
            }
        } else {
            if ( $current_context->withRubric(CS_INSTITUTION_TYPE) and !empty($institutionArray) ) {
                $showInstitutionRecipients = true;
            }
        }

        //Projectroom and no groups enabled -> send mails to group all
        $withGroups = true;
        if ( $current_context->isProjectRoom() && !$current_context->withRubric(CS_GROUP_TYPE)) {
            $showGroupRecipients = true;
            $withGroups = false;

            // get number of users
            $cid = $this->_environment->getCurrentContextId();
            $user_manager = $this->_environment->getUserManager();
            $user_manager->setUserLimit();
            $user_manager->setContextLimit($cid);
            $count = $user_manager->getCountAll();
         $response['numMebers'] = $count;

            $groupArray = array_slice($groupArray, 0, 1);
        }
        
        $response['showGroupRecipients'] = $showGroupRecipients;
        $response['withGroups'] = $withGroups;
        $response['groups'] = $groupArray;

        $allMembers = false;
        if ( ($current_context->isCommunityRoom() && !$current_context->withRubric(CS_INSTITUTION_TYPE)) || $current_context->isGroupRoom()) {
            $allMembers = true;

            // get number of users
            $cid = $this->_environment->getCurrentContextId();
            $user_manager = $this->_environment->getUserManager();
            $user_manager->setUserLimit();
            $user_manager->setContextLimit($cid);
            $count = $user_manager->getCountAll();
            
            $response['numMebers'] = $count;
        }
        
        $response['showInstitutionRecipients'] = $showInstitutionRecipients;
        $response['institutions'] = $institutionArray;
        $response['allMembers'] = $allMembers;
        
        $this->setSuccessfullDataReturn($response);
        echo $this->_return;
    }

    */