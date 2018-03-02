<?php

namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Utils\DateService;

class RoomFeedGenerator
{
    private $legacyEnvironment;
    private $roomService;
    private $dateService;

    private $itemManager;
    private $limits = array();

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService, DateService $dateService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
        $this->dateService = $dateService;

        $this->itemManager = $this->legacyEnvironment->getItemManager();
        $this->itemManager->reset();
    }

    public function getFeedList($roomId, $max, $start)
    {
        $rubrics = array();

        foreach ($this->roomService->getRubricInformation($roomId, true) as $rubric) {
            list($rubricName, $modifier) = explode('_', $rubric);
            if (strcmp($modifier, 'hide') != 0) {
                $rubrics[] = $rubricName;
            }
        }

        if (in_array('group', $rubrics) || in_array('topic', $rubrics)) {
            $rubrics[] = 'label';
        }

        // get the lastest items matching the configured rubrics
        $this->itemManager->setContextLimit($roomId);
        $this->itemManager->setIntervalLimit($max + $start);
        $this->itemManager->setTypeArrayLimit($rubrics);
        $this->itemManager->setDeleteLimit(true);

        if (isset($this->limits['buzzword'])) {
            $this->itemManager->setListLimit($this->limits['buzzword']);
        }

        if (isset($this->limits['categories'])) {
            $this->itemManager->setTagArrayLimit($this->limits['categories']);
        }

        $this->itemManager->select();
        $itemList = $this->itemManager->get();

        $recurringDates = [];

        // TODO: group by rubric and get items chunkwise

        // iterate items and build up feed list
        $feedList = array();
        $item = $itemList->getFirst();
        $itemIndex = 0;
        while ($item) {
            if ($itemIndex >= $start) {
                $type = $item->getItemType();
                
                switch ($type) {
                    case 'user':
                        $userManager = $this->legacyEnvironment->getUserManager();
                        $userItem = $userManager->getItem($item->getItemId());
                        if ($userItem and $userItem->getStatus() != 1) {
                            $feedList[] = $userItem;
                        }
                        
                        break;
    
                    case 'material':
                        $materialManager = $this->legacyEnvironment->getMaterialManager();
                        $materialItem = $materialManager->getItem($item->getItemId());
                        if ($materialItem) {
                            $feedList[] = $materialItem;   
                        }
                        break;
    
                    case 'date':
                        $datesManager = $this->legacyEnvironment->getDatesManager();
                        $dateItem = $datesManager->getItem($item->getItemId());
                        if ($dateItem) {
                            if (!$dateItem->isExternal()) {
                                if ($dateItem->getRecurrencePattern() == '') {
                                    $feedList[] = $dateItem;
                                } else {
                                    $foundRecurrenceId = false;
                                    foreach ($feedList as $feedListEntry) {
                                        if ($feedListEntry->getItemType() == CS_DATE_TYPE) {
                                            if ($feedListEntry->getRecurrenceId() == $dateItem->getRecurrenceId()) {
                                                $foundRecurrenceId = true;
                                            }
                                        }
                                    }
                                    if (!$foundRecurrenceId) {
                                        $feedList[] = $dateItem;
                                    }
                                }
                            }
                        }
                        break;
    
                    case 'discussion':
                        $discussionManager = $this->legacyEnvironment->getDiscussionManager();
                        $discussionItem = $discussionManager->getItem($item->getItemId());
                        if ($discussionItem) {
                            $feedList[] = $discussionItem;    
                        }
                        break;
                        
                    case 'todo':
                        $todoManager = $this->legacyEnvironment->getTodoManager();
                        $todoItem = $todoManager->getItem($item->getItemId());
                        if ($todoItem) {
                            $feedList[] = $todoItem;    
                        }
                        break;
                        
                    case 'announcement':
                        $announcementManager = $this->legacyEnvironment->getAnnouncementManager();
                        $announcementItem = $announcementManager->getItem($item->getItemId());
                        if ($announcementItem) {
                            $feedList[] = $announcementItem;    
                        }
                        break;
                        
                    case 'label':
                        $labelManager = $this->legacyEnvironment->getLabelManager();
                        $labelItem = $labelManager->getItem($item->getItemId());
                        if ($labelItem->isSystemLabel()) {
                            break;
                        }
                        if ($labelItem->getItemType() == 'group') {
                            $groupManager = $this->legacyEnvironment->getLabelManager();
                            $groupItem = $groupManager->getItem($item->getItemId());
                            if ($groupItem) {
                                $feedList[] = $groupItem;    
                            }
                        } else if ($labelItem->getItemType() == 'topic') {
                            $topicManager = $this->legacyEnvironment->getTopicManager();
                            $topicItem = $topicManager->getItem($item->getItemId());
                            if ($topicItem) {
                                $feedList[] = $topicItem;    
                            }
                        }
                        break;
                }
            }
            $itemIndex++;
            $item = $itemList->getNext();
        }

        // post-filter for disabled entries
        $feedList = array_filter($feedList, function($feedItem) {
            if ($feedItem->getItemType() == CS_DATE_TYPE) {
                if ($feedItem->isExternal()) {
                    return true;
                }
            }

            $modifcationDate = new \DateTime($feedItem->getModificationDate());

            return $modifcationDate <= new \DateTime();
        });

        return $feedList;
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemId();
                $this->limits['buzzword'] = $itemId;
            }
        }

        // category
        if (isset($formData['category'])) {
            if (isset($formData['category']['category'])) {
                $categories = $formData['category']['category'];

                if (!empty($categories)) {
                    $this->limits['categories'] = $categories;
                }
            }
        }
    }
}

//                 $list = new cs_list();
//                 $rubric = '';
//                    switch($rubric_name) {
//                       case CS_ANNOUNCEMENT_TYPE:
//                             $manager = $environment->getAnnouncementManager();
//                             $manager->reset();
//                             $manager->setContextLimit($environment->getCurrentContextID());
//                             $count_all = $manager->getCountAll();
//                             $manager->setDateLimit(getCurrentDateTimeInMySQL());
//                             $manager->setSortOrder('modified');
//                             $manager->showNoNotActivatedEntries();

//                             $count_select = $manager->getCountAll();
//                             $manager->setIntervalLimit(0, $home_rubric_limit);
//                             if($home_rubric_limit < $count_select){
//                                 $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
//                             }

//                             $manager->select();
//                             $list = $manager->get();

//                          break;
//                       case CS_DATE_TYPE:
//                             $manager = $environment->getDatesManager();
//                             $manager->reset();
//                             $manager->setContextLimit($environment->getCurrentContextID());
//                             $manager->setDateModeLimit(2);
//                             $count_all = $manager->getCountAll();
//                             $manager->setFutureLimit();
//                             $manager->setDateModeLimit(3);
//                             $manager->showNoNotActivatedEntries();

//                             $count_select = $manager->getCountAll();
//                             $manager->setIntervalLimit(0, $home_rubric_limit);
//                             if($home_rubric_limit < $count_select){
//                                 $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
//                             }

//                             $manager->select();
//                             $list = $manager->get();
//                             $rubric = 'dates';
//                          break;
//                       case CS_PROJECT_TYPE:
//                             $room_type = CS_PROJECT_TYPE;
//                             $manager = $environment->getProjectManager();
//                             $manager->reset();
//                             $manager->setContextLimit($environment->getCurrentPortalID());
//                             if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr  ) {
//                                $manager->setCommunityRoomLimit($environment->getCurrentContextID());
//                             } else {
//                                # use redundant infos in community room
//                                $manager->setIDArrayLimit($context_item->getInternalProjectIDArray());
//                             }
//                             $count_all = $manager->getCountAll();
//                             $manager->setSortOrder('activity_rev');
//                             if ( $count_all > 10 ) {
//                                $manager->setIntervalLimit(0,10);
//                             }
//                             $manager->select();
//                             $list = $manager->get();
//                          break;
//                       case CS_GROUP_TYPE:
//                             $manager = $environment->getGroupManager();
//                             $manager->reset();
//                             $manager->setContextLimit($environment->getCurrentContextID());
//                             $manager->select();
//                             $list = $manager->get();
//                             $count_all = $list->getCount();
//                          break;
//                       case CS_TODO_TYPE:
//                             $manager = $environment->getTodoManager();
//                             $manager->reset();
//                             $manager->setContextLimit($environment->getCurrentContextID());
//                             $count_all = $manager->getCountAll();
//                             $manager->setStatusLimit(4);
//                             $manager->setSortOrder('date');
//                             $manager->showNoNotActivatedEntries();

//                             $count_select = $manager->getCountAll();
//                             $manager->setIntervalLimit(0, $home_rubric_limit);
//                             if($home_rubric_limit < $count_select){
//                                 $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
//                             }

//                             $manager->select();
//                             $list = $manager->get();
//                             $item = $list->getFirst();
//                             $tmp_id_array = array();
//                             while ($item){
//                                $tmp_id_array[] = $item->getItemID();
//                                $item = $list->getNext();
//                             }
//                             $step_manager = $environment->getStepManager();
//                             $step_list = $step_manager->getAllStepItemListByIDArray($tmp_id_array);
//                             $item = $step_list->getFirst();
//                             while ($item){
//                                $sub_id_array[] = $item->getItemID();
//                                $item = $step_list->getNext();
//                             }
//                             unset($step_list);
//                             unset($step_manager);
//                             unset($manager);
//                             break;
//                       case CS_TOPIC_TYPE:
//                             $manager = $environment->getTopicManager();
//                             $manager->reset();
//                             $manager->setContextLimit($environment->getCurrentContextID());
                            
//                             $manager->showNoNotActivatedEntries();
                            
//                             $manager->select();
//                             $list = $manager->get();
//                             $count_all = $list->getCount();
//                          break;
//                       case CS_INSTITUTION_TYPE:
//                             $manager = $environment->getInstitutionManager();
//                             $manager->reset();
//                             $manager->setContextLimit($environment->getCurrentContextID());
//                             $manager->select();
//                             $list = $manager->get();
//                             $count_all = $list->getCount();
//                          break;
//                       case CS_USER_TYPE:
//                             $manager = $environment->getUserManager();
//                             $manager->reset();
//                             $manager->setContextLimit($environment->getCurrentContextID());
//                             $manager->setUserLimit();
//                             $count_all = $manager->getCountAll();
//                             if (!$current_user->isGuest()){
//                                $manager->setVisibleToAllAndCommsy();
//                             } else {
//                                $manager->setVisibleToAll();
//                             }
//                             $manager->setAgeLimit($context_item->getTimeSpread());
//                             $manager->select();
//                             $list = $manager->get();
//                          break;
//                       case CS_MATERIAL_TYPE:
// #                           $short_list_view = $class_factory->getClass(MATERIAL_SHORT_VIEW,$param_class_array);
//                             $manager = $environment->getMaterialManager();
//                             $manager->reset();
//                             $manager->create_tmp_table($environment->getCurrentContextID());
//                             $manager->setContextLimit($environment->getCurrentContextID());
//                             $count_all = $manager->getCountAll();
//                             $manager->setOrder('date');
//                             if ($environment->inProjectRoom()){
//                                $manager->setAgeLimit($context_item->getTimeSpread());
//                             } else {
//                                $manager->setIntervalLimit(0,5);
//                                $home_rubric_limit = 5;
//                             }
//                             $manager->showNoNotActivatedEntries();

//                             $count_select = $manager->getCountAll();
//                             $manager->setIntervalLimit(0, $home_rubric_limit);
//                             $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;

//                             if($home_rubric_limit < $count_select){
//                                 $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
//                             }

//                             $manager->select();
//                             $list = $manager->get();
//                             $manager->delete_tmp_table();
//                             $item = $list->getFirst();
//                             $tmp_id_array = array();
//                             while ($item){
//                                $tmp_id_array[] = $item->getItemID();
//                                $item = $list->getNext();
//                             }
//                             $section_manager = $environment->getSectionManager();
//                             $section_list = $section_manager->getAllSectionItemListByIDArray($tmp_id_array);
//                             $item = $section_list->getFirst();
//                             while ($item){
//                                $sub_id_array[] = $item->getItemID();
//                                $v_id_array[$item->getItemID()] = $item->getVersionID();
//                                $item = $section_list->getNext();
//                             }
//                          break;
//                       case CS_DISCUSSION_TYPE:
//                             $manager = $environment->getDiscussionManager();
//                             $manager->reset();
//                             $manager->setContextLimit($environment->getCurrentContextID());
//                             $count_all = $manager->getCountAll();
//                             if ($environment->inProjectRoom() or $environment->inGroupRoom() ) {
//                                $manager->setAgeLimit($context_item->getTimeSpread());
//                             } elseif ($environment->inCommunityRoom()) {
//                                $manager->setIntervalLimit(0,5);
//                                $home_rubric_limit = 5;
//                             }
//                             $manager->showNoNotActivatedEntries();

//                             $count_select = $manager->getCountAll();
//                             $manager->setIntervalLimit(0, $home_rubric_limit);
//                             $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;

//                             if($home_rubric_limit < $count_select){
//                                 $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
//                             }

//                             $manager->select();
//                             $list = $manager->get();
//                             $item = $list->getFirst();
//                             $disc_id_array = array();
//                             while ($item){
//                                $disc_id_array[] = $item->getItemID();
//                                $item = $list->getNext();
//                             }
//                             $discarticle_manager = $environment->getDiscussionArticleManager();
//                             $discarticle_list = $discarticle_manager->getAllDiscArticlesItemListByIDArray($disc_id_array);
//                             $item = $discarticle_list->getFirst();
//                             while ($item){
//                                $disc_id_array[] = $item->getItemID();
//                                $item = $discarticle_list->getNext();
//                             }
//                          break;
//                    }
//                   $rubric_list_array[$rubric_name] = $list;
//                   $rubric_count_all_array[$rubric_name] = $count_all;
//                   $tmp = $list->getFirst();
//                   $ids = array();
//                   while ($tmp){
//                       $id_array[] = $tmp->getItemID();
//                       if ($rubric_name == CS_MATERIAL_TYPE){
//                          $v_id_array[$tmp->getItemID()] = $tmp->getVersionID();
//                       }
//                       $ids[] = $tmp->getItemID();
//                       $tmp = $list->getNext();
//                    }
//                    if (empty($rubric)){
//                       $session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric_name.'_index_ids', $ids);
//                    }else{
//                       $session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric.'_index_ids', $ids);
//                    }



//                 }



//                   $noticed_manager = $environment->getNoticedManager();
//                   $id_array = array_merge($id_array, $disc_id_array);
//                   $id_array = array_merge($id_array, $sub_id_array);
//                   $noticed_manager->getLatestNoticedByIDArray($id_array);
//                   $noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);
//                   $link_manager = $environment->getLinkManager();
//                   $file_id_array = $link_manager->getAllFileLinksForListByIDs($id_array, $v_id_array);
//                   $file_manager = $environment->getFileManager();
//                   $file_manager->setIDArrayLimit($file_id_array);
//                   $file_manager->select();
//                   $manager = $environment->getProjectManager();
//                   $room_max_activity = 0;
//                   if ($this->_environment->inCommunityRoom()) {
//                      $manager->setContextLimit($environment->getCurrentPortalID());

//                      global $c_cache_cr_pr;
//                      if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr ) {
//                         $room_max_activity = $manager->getMaxActivityPointsInCommunityRoom($environment->getCurrentContextID());
//                      } else {
//                         $current_context_item = $environment->getCurrentContextItem();
//                         $room_max_activity = $manager->getMaxActivityPointsInCommunityRoomInternal($current_context_item->getInternalProjectIDArray());
//                         unset($current_context_item);
//                      }
//                   }

//                  $user_manager = $this->_environment->getUserManager();
//                  foreach($rubric_list_array as $key=>$list){
//                     $item_array = array();
//                     $column1_addon = '';
//                     $modificator_id = '';
//                     $item = $list->getFirst();
//                     $recurringDateArray = array();
//                     $params = array();
//                     $params['environment'] = $environment;
//                     $params['with_modifying_actions'] = false;
//                     $view = new cs_view($params);
//                      while($item) {
//                         $may_enter = false;
//                         $noticed_text = $this->_getItemChangeStatus($item);
// #                       $noticed_text = '';
//                         switch($key) {
//                             case CS_ANNOUNCEMENT_TYPE:
//                                 $column1 = $item->getTitle();
//                                 $parse_day_start = convertDateFromInput($item->getSeconddateTime(), $this->_environment->getSelectedLanguage());
//                                 $conforms = $parse_day_start['conforms'];
//                                 if($conforms === true) {
//                                     $column2 = $translator->getDateInLang($parse_day_start['datetime']);
//                                 } else {
//                                     $column2 = $item->getSeconddateTime();
//                                 }
//                                 $column3 = $item->getModificatorItem()->getFullName();
//                                 $modificator_id = $item->getModificatorItem()->getItemID();
//                                 break;
//                             case CS_DATE_TYPE:
//                                 $displayDate = true;
//                                 $column1_addon = false;
                                
//                                 // is this a recurring date?
//                                 if ( $item->getRecurrencePattern() )
//                                 {
//                                     // did we already displayed the first date?
//                                     if ( !isset($recurringDateArray[$item->getRecurrenceId()]) )
//                                     {
//                                         // if not - this is the starting date
//                                         $recurringDateArray[$item->getRecurrenceId()] = $item;
//                                     }
//                                     else
//                                     {
//                                         $displayDate = false;
//                                     }
//                                 }
                                
//                                 if ( $displayDate )
//                                 {
//                                     $column1 = $item->getTitle();
                                    
//                                     if ( $item->getRecurrencePattern() )
//                                     {
//                                         $column1_addon = true;
//                                     }
                                    
//                                     $parse_day_start = convertDateFromInput($item->getStartingDay(),$this->_environment->getSelectedLanguage());
//                                     $conforms = $parse_day_start['conforms'];
//                                     if ($conforms == TRUE) {
//                                         $date = $translator->getDateInLang($parse_day_start['datetime']);
//                                     } else {
//                                         $date = $item->getStartingDay();
//                                     }
//                                     $parse_time_start = convertTimeFromInput($item->getStartingTime());
//                                     $conforms = $parse_time_start['conforms'];
//                                     if ($conforms == TRUE) {
//                                         $time = getTimeLanguage($parse_time_start['datetime']);
//                                     } else {
//                                         $time = $item->getStartingTime();
//                                     }
//                                     if (!empty($time)){
//                                         $time = ', '.$time;
//                                     }
//                                     $column2 = $view->_text_as_html_short($date.$time);
//                                     $column3 = $item->getPlace();
//                                 }
//                                 else
//                                 {
//                                     // go to next item
//                                     $item = $list->getNext();
                                    
//                                     /*
//                                      * the "2" is needed, to continue the while loop an not only
//                                      * the nested switch statement
//                                      */
//                                     continue 2;                 
//                                 }
                                
//                                 break;
//                             case CS_DISCUSSION_TYPE:
//                                 $column1 = $item->getTitle();
//                                 $column2 = $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate());
//                                 $column3 = $item->getModificatorItem()->getFullName();
//                                 $modificator_id = $item->getModificatorItem()->getItemID();
//                                 $reader_array = $item->getAllAndUnreadArticles();
//                                 $column1_addon = $reader_array['unread'].' / '.$reader_array['count'];
//                                 break;
//                             case CS_USER_TYPE:
//                                 $column1 = '';
//                                 $title = $item->getTitle();
//                                 if (!empty($title)){
//                                     $column1 = $item->getTitle().' ';
//                                 }
//                                 $column1 .= $view->_text_as_html_short($item->getFullname());
//                                 ##################################################
//                                 # messenger - MUSS NOCH AUFGERÄUMT WERDEN: HTML INS TEMPLATE
//                                 ##################################################
//                                 global $c_commsy_domain;
//                                 $host = $c_commsy_domain;
//                                 global $c_commsy_url_path;
//                                 $url_to_img = $host.$c_commsy_url_path.'/images/messenger';
//                                 $icq_number = $item->getICQ();
//                                 if ( !empty($icq_number) ){
//                                     //$column1 .= '   <img style="vertical-align:middle;" src="http://status.icq.com/online.gif?icq='.rawurlencode($icq_number).'&amp;img=5" alt="ICQ Online Status" />'.LF;
//                                 }
//                                 $msn_number = $item->getMSN();
//                                 if ( !empty($msn_number) ){
//                                     //$column1 .= '<a href="http://www.IMStatusCheck.com/?msn">'.LF;
//                                     //$column1 .= '   <img style="vertical-align:middle;" src="http://www.IMStatusCheck.com/status/msn/'.rawurlencode($msn_number).'?icons" alt="MSN Online Status" />'.LF;
//                                     //$column1 .= '</a>'.LF;
//                                 }
//                                 $skype_number = $item->getSkype();
//                                 if ( !empty($skype_number) ){
//                                     //$column1 .= '<script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>'.LF;
//                                     //$column1 .= '<a href="skype:'.rawurlencode($skype_number).'?chat">'.LF;
//                                     //$column1 .= '   <img src="http://mystatus.skype.com/smallicon/'.rawurlencode($skype_number).'" style="vertical-align:middle; border: none;" width="16" height="16" alt="Skype Online Status" />'.LF;
//                                     //$column1 .= '</a>'.LF;
//                                 }
//                                 $yahoo_number = $item->getYahoo();
//                                 if ( !empty($yahoo_number) ){
//                                     //$column1 .= '<a href="http://messenger.yahoo.com/edit/send/?.target='.rawurlencode($yahoo_number).'">'.LF;
//                                     //$column1 .= '   <img style="vertical-align:middle;" src="http://opi.yahoo.com/yahooonline/u='.rawurlencode($yahoo_number).'/m=g/t=0/l='.$this->_environment->getSelectedLanguage().'/opi.jpg" alt="Yahoo Online Status Indicator" />'.LF;
//                                     //$column1 .= '</a>'.LF;
//                                 }
//                                 ##################################################
//                                 # messenger - END
//                                 ##################################################
//                                 $phone = $item->getTelephone();
//                                 $handy = $item->getCellularphone();
//                                 $column2 = '';
//                                 if ( !empty($phone) ){
//                                     $column2 .= $view->_text_as_html_short($phone).LF;
//                                 }
//                                 if (!empty($phone) and !empty($handy)) {
//                                     $column2 .= BRLF;
//                                 }
//                                 if ( !empty($handy) ){
//                                     $column2 .= $view->_text_as_html_short($handy).LF;
//                                 }
//                                 if ($item->isEmailVisible()) {
//                                     $email = $item->getEmail();
//                                     $email_text = $email;
//                                     $column3 = curl_mailto( $item->getEmail(), $view->_text_as_html_short(chunkText($email_text,20)),$email_text);
//                                 } else {
//                                     $column3 = $translator->getMessage('USER_EMAIL_HIDDEN');
//                                 }
//                                 break;
//                             case CS_GROUP_TYPE:
//                                 $column1 = $item->getTitle();
//                                 $members = $item->getMemberItemList();
//                                 $column2 = $translator->getMessage('GROUP_MEMBERS').': '.$members->getCount();
//                                 $linked_item_array = $item->getAllLinkedItemIDArray();
//                                 $column3 = $translator->getMessage('COMMON_REFERENCED_LATEST_ENTRIES').': '.count($linked_item_array);
//                                 break;
//                             case CS_TOPIC_TYPE:
//                                 $column1 = $item->getTitle();
//                                 $column2 = $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate());
//                                 $linked_item_array = $item->getAllLinkedItemIDArray();
//                                 $column3 = $translator->getMessage('COMMON_REFERENCED_LATEST_ENTRIES').': '.count($linked_item_array);
//                                 break;
//                             case CS_INSTITUTION_TYPE:
//                                 $column1 = $item->getTitle();
//                                 $members = $item->getMemberItemList();
//                                 $column2 = $translator->getMessage('GROUP_MEMBERS').': '.$members->getCount();
//                                 $linked_item_array = $item->getAllLinkedItemIDArray();
//                                 $column3 = $translator->getMessage('COMMON_REFERENCED_LATEST_ENTRIES').': '.count($linked_item_array);
//                                 break;
//                             case CS_PROJECT_TYPE:
//                                 $column1 = $item->getTitle();
//                                 $column2 = $translator->getMessage('GROUP_MEMBERS').': '.$item->getAllUsers();
//                                 $column3 = $this->_getItemActivity ($item,$room_max_activity);
//                                 $user_manager->setUserIDLimit($current_user->getUserID());
//                                 $user_manager->setAuthSourceLimit($current_user->getAuthSource());
//                                 $user_manager->setContextLimit($item->getItemID());
//                                 $user_manager->select();
//                                 $user_list = $user_manager->get();
//                                 if (!empty($user_list)){
//                                    $room_user = $user_list->getFirst();
//                                 } else {
//                                    $room_user = '';
//                                 }
//                                 if ($current_user->isRoot()) {
//                                    $may_enter = true;
//                                 } elseif ( !empty($room_user) ) {
//                                    $may_enter = $item->mayEnter($room_user);
//                                 } else {
//                                    $may_enter = false;
//                                 }

//                                 break;
//                             case CS_TODO_TYPE:
//                                 $column1 = $item->getTitle();
//                                 $original_date = $item->getDate();
//                                 $date = getDateInLang($original_date);
//                                 $status = $item->getStatus();
//                                 $actual_date = date("Y-m-d H:i:s");
//                                 if ($status != $translator->getMessage('TODO_DONE') and $original_date < $actual_date){
//                                     $date = '<span class="required">'.$date.'</span>';
//                                 }
//                                 if ($original_date == '9999-00-00 00:00:00'){
//                                     $date = $translator->getMessage('TODO_NO_END_DATE');
//                                 }
//                                 $column2 = $date;
//                                 $column3 = $this->_getTodoItemProcess($item,$translator);
//                                 break;
//                             default:
//                                 $column1 = $item->getTitle();
//                                 $column2 = $this->_environment->getTranslationObject()->getDateInLang($item->getModificationDate());
//                                 $column3 = $item->getModificatorItem()->getFullName();
//                                 $modificator_id = $item->getModificatorItem()->getItemID();
//                         }

//                         // files
//                         $with_files = false;
//                         $file_count = 0;
//                         $attachment_infos = array();
//                         if(in_array($key, $this->getRubricsWithFiles())) {
//                             $with_files = true;
                            
//                             if ($key == CS_MATERIAL_TYPE){
//                                 $file_count = $item->getFileListWithFilesFromSections()->getCount();
//                                 $file_list = $item->getFileListWithFilesFromSections();
//                             }elseif($key == CS_DISCUSSION_TYPE){
//                                 $file_count = $item->getFileListWithFilesFromArticles()->getCount();
//                                 $file_list = $item->getFileListWithFilesFromArticles();
//                             }else{
//                                 $file_count = $item->getFileList()->getCount();
//                                 $file_list = $item->getFileList();
//                             }
//                             $file = $file_list->getFirst();
//                             while($file) {
//                                 $lightbox = false;
//                                 if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) $lightbox = true;

//                                 $info = array();
//                                 #$info['file_name'] = $converter->text_as_html_short($file->getDisplayName());
//                                 $info['file_name']  = $converter->filenameFormatting($file->getDisplayName());
//                                 $info['file_icon']  = $file->getFileIcon();
//                                 $info['file_url']   = $file->getURL();
//                                 $info['file_size']  = $file->getFileSize();
//                                 $info['lightbox']   = $lightbox;

//                                 $attachment_infos[] = $info;
//                                 $file = $file_list->getNext();
//                             }
//                         }

//                         $item_array[] = array(
//                             'iid'               => $item->getItemID(),
//                             'user_iid'          => $modificator_id,
//                             'column_1'          => $column1,
//                             'column_1_addon'    => $column1_addon,
//                             'column_2'          => $column2,
//                             'column_3'          => $column3,
//                             'noticed'           => $noticed_text,
//                             'has_attachments'   => $with_files,
//                             'attachment_count'  => $file_count,
//                             'attachment_infos'  => $attachment_infos,
//                             'may_enter'         => $may_enter
//                         );

//                         $item = $list->getNext();
//                     }
//                     $return[$key]['items'] = $item_array;

//                     // message tag
//                     $message_tag = '';
//                     //TODO: complete missing tags
//                     $shown = 0;
//                     switch($key) {
//                         case CS_ANNOUNCEMENT_TYPE:
//                             $message_tag = $translator->getMessage('COMMON_' . mb_strtoupper($key) . '_SHORT_VIEW_DESCRIPTION', $list->getCount(), $rubric_count_all_array[$key]);
//                             break;
//                         case CS_DATE_TYPE:
//                             $message_tag = $translator->getMessage('HOME_DATES_SHORT_VIEW_DESCRIPTION', $list->getCount(), $rubric_count_all_array[$key]);
//                             break;
//                         case CS_PROJECT_TYPE:
//                             if($this->_environment->inProjectRoom()) {
//                                 $message_tag = $translator->getMessage('PROJECT_SHORT_DESCRIPTION', 5);
//                             } elseif($this->_environment->inCommunityRoom()) {
//                                 if(isset($list) && $list->isNotEmpty() && $list->getCount() < 10){
//                                     $count = $list->getCount();
//                                 }else{
//                                     $count = '10';
//                                 }
//                                 $message_tag = $translator->getMessage('COMMUNITY_SHORT_DESCRIPTION').' '.$count;
//                             }
//                             break;
//                         case CS_GROUP_TYPE:
//                             $message_tag = $translator->getMessage('HOME_GROUP_SHORT_VIEW_DESCRIPTION', $list->getCount());
//                             break;
//                         case CS_TODO_TYPE:
//                             $message_tag = $translator->getMessage('TODO_SHORT_VIEW_DESCRIPTION', $list->getCount(), $rubric_count_all_array[$key]);
//                             break;
//                         case CS_TOPIC_TYPE:
//                             if(isset($list) && $list->isNotEmpty()) {
//                                 $shown = $list->getCount();
//                             } else {
//                                 $shown = 0;
//                             }
//                             $message_tag = $translator->getMessage('HOME_TOPIC_SHORT_VIEW_DESCRIPTION', $shown);
//                             break;
//                         case CS_INSTITUTION_TYPE:
//                             if($rubric_count_all_array[$key] > 0) {
//                                 $message_tag = $translator->getMessage('HOME_INSTITUTION_SHORT_VIEW_DESCRIPTION', $list->getCount());
//                             }
//                             break;
//                         case CS_USER_TYPE:

//                             if($this->_environment->inProjectRoom()) {
//                                 global $who_is_online;
//                                 if(isset($who_is_online) && $who_is_online) {
//                                     $shown = $list->getCount();
//                                     if($shown > 0) {
//                                         $days = ($context_item->isProjectRoom() ? $context_item->getTimeSpread() : 90);
//                                         $item = $list->getFirst();
//                                         $count_active_now = 0;
//                                         while($item) {
//                                             $lastlogin = $item->getLastLogin();
//                                             if($lastlogin > getCurrentDateTimeMinusMinutesInMySQL($days)) {
//                                                 $count_active_now++;
//                                             }
//                                             $item = $list->getNext();
//                                         }
//                                     }

//                                     $message_tag = $translator->getMessage('HOME_USER_SHORT_VIEW_DESCRIPTION2', $shown, $count_active_now, $rubric_count_all_array[$key], $days);
//                                 } else {
//                                     $message_tag = $translator->getMessage('HOME_USER_SHORT_VIEW_DESCRIPTION', $list->getCount(), $rubric_count_all_array[$key]);
//                                 }
//                             } else {
//                                 $message_tag = $translator->getMessage('COMMON_SHORT_CONTACT_VIEW_DESCRIPTION', $list->getCount(), $rubric_count_all_array[$key]);
//                             }
//                             break;
//                         case CS_MATERIAL_TYPE:
//                             if($this->_environment->inProjectRoom()) {
//                                 $period = $context_item->getTimeSpread();
//                                 $message_tag = $translator->getMessage('COMMON_SHORT_VIEW_DESCRIPTION', $list->getCount(), $period, $rubric_count_all_array[$key]);
//                             } else {
//                                 $message_tag = $translator->getMessage('COMMON_SHORT_MATERIAL_VIEW_DESCRIPTION', $list->getCount(), $rubric_count_all_array[$key]);
//                             }
//                             break;
//                         case CS_DISCUSSION_TYPE:
//                             $shown = $list->getCount();
//                             if($this->_environment->inProjectRoom() || $this->_environment->inGroupRoom()) {
//                                 $period = $context_item->getTimeSpread();
//                                 $message_tag = $translator->getMessage('COMMON_SHORT_VIEW_DESCRIPTION', $shown, $period, $rubric_count_all_array[$key]);
//                             } elseif($this->_environment->inCommunityRoom()) {
//                                 if($shown != 1) {
//                                     $message_tag = $translator->getMessage('COMMON_SHORT_VIEW_DESCRIPTION_CR', $shown, $rubric_count_all_array[$key]);
//                                 } else {
//                                     $message_tag = $translator->getMessage('COMMON_SHORT_VIEW_DESCRIPTION_CR_ONE', $shown, $rubric_count_all_array[$key]);
//                                 }
//                             }
//                             break;
//                     }
//                     $return[$key]['message_tag'] = $message_tag;

//                  }


//                   // TODO attachment_count...


//                     // append return
//                     /*
//                     $return = array(
//                         'items'     => $rubric_array/*,
//                         'count_all' => $count_all_shown*/
//                     /*);
//                     */
//             return $return;