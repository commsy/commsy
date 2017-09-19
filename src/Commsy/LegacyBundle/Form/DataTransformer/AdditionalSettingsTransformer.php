<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Utils\UserService;
use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class AdditionalSettingsTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService, UserService $userService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
    }

    /**
     * Transforms a cs_room_item object into an array
     *
     * @param cs_room_item $roomItem
     * @return array
     */
    public function transform($roomItem)
    {
        $roomData = array();

        if ($roomItem) {
            // structural auxilaries
            $roomData['structural_auxilaries']['buzzwords']['activate'] = $roomItem->withBuzzwords();
            $roomData['structural_auxilaries']['buzzwords']['show_expanded'] = $roomItem->isBuzzwordShowExpanded();
            $roomData['structural_auxilaries']['buzzwords']['mandatory'] = $roomItem->isBuzzwordMandatory();

            $roomData['structural_auxilaries']['categories']['activate'] = $roomItem->withTags();
            $roomData['structural_auxilaries']['categories']['show_expanded'] = $roomItem->isTagsShowExpanded();
            $roomData['structural_auxilaries']['categories']['mandatory'] = $roomItem->isTagMandatory();
            $roomData['structural_auxilaries']['categories']['edit'] = !$roomItem->isTagEditedByAll();

            $roomData['structural_auxilaries']['calendars']['edit'] = $roomItem->usersCanEditCalendars();
            $roomData['structural_auxilaries']['calendars']['external'] = $roomItem->usersCanSetExternalCalendarsUrl();

            // tasks
            $roomData['tasks']['additional_status']  = $roomItem->getExtraToDoStatusArray();

            // templates
            $roomData['template']['status'] = $roomItem->isTemplate();
            $roomData['template']['template_description'] = $roomItem->getTemplateDescription();
            if($roomItem->isCommunityRoom()){
                $roomData['template']['template_availability'] = $roomItem->getCommunityTemplateAvailability();
            }
            else{
                $roomData['template']['template_availability'] = $roomItem->getTemplateAvailability();
            }

            // rss
            if ($roomItem->isRSSOn()) {
                $roomData['rss']['status'] = '1';
            } else {
                $roomData['rss']['status'] = '2';
            }

            // archived
            $roomData['archived']['active'] = !$roomItem->isOpen();

            // terms and conditions
            $roomData['terms']['status'] = $roomItem->getAGBStatus();
            if ($roomData['terms']['status'] != '1'){
                $roomData['terms']['status'] = '2';
            }

            $agb_text_array = $roomItem->getAGBTextArray();
            $languages = $this->legacyEnvironment->getAvailableLanguageArray();
            foreach ($languages as $language) {
                if (!empty($agb_text_array[cs_strtoupper($language)])) {
                   $roomData['terms']['agb_text_'.$language] = $agb_text_array[cs_strtoupper($language)];
                } else {
                   $roomData['terms']['agb_text_'.$language] = '';
                }
            }
        }
        return $roomData;
    }

    /**
     * Save additional settings
     *
     * @param object $roomObject
     * @param array $roomData
     * @return cs_room_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($roomObject, $roomData)
    {
        /********* save buzzword and tag options ******/
        $buzzwords = $roomData['structural_auxilaries']['buzzwords'];
        $categories = $roomData['structural_auxilaries']['categories'];
        $calendars = $roomData['structural_auxilaries']['calendars'];

        // buzzword options
        if ( isset($buzzwords['activate']) and !empty($buzzwords['activate']) and $buzzwords['activate'] == true) {
            $roomObject->setWithBuzzwords();
        } else {
            $roomObject->setWithoutBuzzwords();
        }
        if ( isset($buzzwords['show_expanded']) and !empty($buzzwords['show_expanded']) and $buzzwords['show_expanded'] == true) {
            $roomObject->setBuzzwordShowExpanded();
        } else {
            $roomObject->unsetBuzzwordShowExpanded();
        }
        if ( isset($buzzwords['mandatory']) and !empty($buzzwords['mandatory']) and $buzzwords['mandatory'] == true ) {
            $roomObject->setBuzzwordMandatory();
        } else {
            $roomObject->unsetBuzzwordMandatory();
        }

        // tag options
        if ( isset($categories['activate']) and !empty($categories['activate']) and $categories['activate'] == true) {
            $roomObject->setWithTags();
        } else {
            $roomObject->setWithoutTags();
        }
        if ( isset($categories['show_expanded']) and !empty($categories['show_expanded']) and $categories['show_expanded'] == true) {
            $roomObject->setTagsShowExpanded();
        } else {
            $roomObject->unsetTagsShowExpanded();
        }
        if ( isset($categories['mandatory']) and !empty($categories['mandatory']) and $categories['mandatory'] == true ) {
            $roomObject->setTagMandatory();
        } else {
            $roomObject->unsetTagMandatory();
        }
        if ( isset($categories['edit']) and !empty($categories['edit']) and $categories['edit'] == true ) {
            $roomObject->setTagEditedByModerator();
        } else {
            $roomObject->setTagEditedByAll();
        }

        // calendar options
        if ( isset($calendars['edit']) and !empty($calendars['edit']) and $calendars['edit'] == true ) {
            $roomObject->setUsersCanEditCalendars();
        } else {
            $roomObject->unsetUsersCanEditCalendars();
        }
        if ( isset($calendars['external']) and !empty($calendars['external']) and $calendars['external'] == true ) {
            $roomObject->setUsersCanSetExternalCalendarsUrl();
        } else {
            $roomObject->unsetUsersCanSetExternalCalendarsUrl();
        }

        /********* save template options ******/
        $template = $roomData['template'];

        if ( isset($template['status']) and !empty($template['status'])) {
            if ( $template['status'] == true ) {
               $roomObject->setTemplate();
            } else {
               $roomObject->setNotTemplate();
            }
        } elseif ( $roomObject->isProjectRoom()
                or $roomObject->isCommunityRoom()
                or $roomObject->isPrivateRoom()
                or $roomObject->isGroupRoom()
              ) {
            $roomObject->setNotTemplate();
        }
        if ( isset($template['template_availability'])){
            if ( $roomObject->isCommunityRoom() ){
               $roomObject->setCommunityTemplateAvailability($template['template_availability']);
            } else{
               $roomObject->setTemplateAvailability($template['template_availability']);
            }
        }
        if ( isset($template['template_description'])){
            $roomObject->setTemplateDescription($template['template_description']);
        }

        /********* rss options ******/
        if (isset($roomData['rss']['status'])) {
            if ($roomData['rss']['status'] === '1') {
                $roomObject->turnRSSOn();
            } else if ($roomData['rss']['status'] === '2') {
                $roomObject->turnRSSOff();
            }
        } else {
            $roomObject->turnRSSOff();
        }

        /********* save archive options ******/
        $archived = $roomData['archived'];
        if (isset($archived['active'])) {
            if ($archived['active']) {
                $roomObject->moveToArchive();
                $this->legacyEnvironment->activateArchiveMode();
            } else {
                if ($this->legacyEnvironment->isArchiveMode()) {
                    $roomObject->backFromArchive();
                    $this->legacyEnvironment->deactivateArchiveMode();
                }
            }
        }

        /***************** save room status *************/
        /*
        if ( isset($roomData['room_status']) ) {
            if ($roomData['room_status'] == '') {
                // archive
                if ($this->legacyEnvironment->isArchiveMode() ) {
                    $roomObject->backFromArchive();
                    $this->legacyEnvironment->deactivateArchiveMode();
                }
                // archive
                // old: should be impossible
                else {
                    // Fix: Find Group-Rooms if existing
                    if( $roomObject->isGrouproomActive() ) {  // GrouproomActive schmeißt fehler gucken ob er hier rein rennt wegen Kategorie einstellungen
                        $groupRoomList = $roomObject->getGroupRoomList();

                        if( !$groupRoomList->isEmpty() ) {
                            $roomObject = $groupRoomList->getFirst();

                            while($room_item) {
                                // All GroupRooms have to be opened too
                                $roomObject->open();
                                $roomObject->save();
         
                                $roomObject = $groupRoomList->getNext();
                            }
                        }
                    }
                    // ~Fix
                    $roomObject->open();
                }
            } elseif ($roomData['room_status'] == 2) {
                // template or not: template close, others archive
                if ( !$roomObject->isTemplate() ) {
                    $roomObject->moveToArchive();
                    $this->legacyEnvironment->activateArchiveMode();                             
                }
            }
        }
        // status != 2 and =! empty
        else {
            // archive
            if ($this->legacyEnvironment->isArchiveMode() ) {
                $roomObject->backFromArchive();
                $this->legacyEnvironment->deactivateArchiveMode();
            }
        }
        */
        

        /***************** save AGB *************/
        $languages = $this->legacyEnvironment->getAvailableLanguageArray();
        $current_user = $this->legacyEnvironment->getCurrentUserItem();
        foreach ($languages as $language) {
            if (!empty($roomData['terms']['agb_text_'.mb_strtolower($language, 'UTF-8')])) {
                $agbtext_array[mb_strtoupper($language, 'UTF-8')] = $roomData['terms']['agb_text_'.mb_strtolower($language, 'UTF-8')];
            } else {
               $agbtext_array[mb_strtoupper($language, 'UTF-8')] = '';
            }
         }
         
        if(($agbtext_array != $roomObject->getAGBTextArray()) or ($roomData['terms']['status'] != $roomObject->getAGBStatus())) {
            $roomObject->setAGBStatus($roomData['terms']['status']);
            $roomObject->setAGBTextArray($agbtext_array);
            $roomObject->setAGBChangeDate();
            $current_user->setAGBAcceptance();
            $current_user->save();
         }
         
        /***************** save extra task status ************/
        $roomObject->setExtraToDoStatusArray(array_filter($roomData['tasks']['additional_status']));

        return $roomObject;
    }
}
