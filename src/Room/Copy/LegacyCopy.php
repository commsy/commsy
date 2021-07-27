<?php

namespace App\Room\Copy;

use App\Event\ItemReindexEvent;
use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use cs_environment;
use cs_room_item;
use cs_user_item;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class LegacyCopy
 * @package App\Room\Copy
 *
 * TODO: Refactor this to a real strategy pattern for the different room types
 * This is basically a copy of the old legacy include file
 */
class LegacyCopy implements CopyStrategy
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var ItemService
     */
    private ItemService $itemService;

    /**
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $eventDispatcher;

    /**
     * LegacyCopy constructor.
     * @param LegacyEnvironment $legacyEnvironment
     * @param ItemService $itemService
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        ItemService $itemService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->itemService = $itemService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param cs_room_item $source
     * @param cs_room_item $target
     */
    public function copySettings(cs_room_item $source, cs_room_item $target): void
    {
        $copy_array = [];
        $copy_array['context'] = true;
        $copy_array['homeconf'] = true;
        $copy_array['timespread'] = true;
        $copy_array['extras'] = true;
        $copy_array['plugins'] = true;
        $copy_array['color'] = true;
        $copy_array['todostatus'] = true;
        $copy_array['usageinfo'] = true;
        $copy_array['topicpath'] = true;
        $copy_array['tag'] = true;
        $copy_array['discussionstatus'] = true;
        $copy_array['todomanagementstatus'] = true;
        $copy_array['detailboxconf'] = true;
        $copy_array['listboxconf'] = true;
        $copy_array['homerightconf'] = true;
        $copy_array['datespresentationstatus'] = true;
        $copy_array['htmltextareastatus'] = true;
        $copy_array['buzzword'] = true;
        $copy_array['netnavigation'] = true;
        $copy_array['emailtext'] = true;
        $copy_array['title'] = true;
        $copy_array['logo'] = true;
        $copy_array['BGImage'] = true;
        $copy_array['wiki'] = true;
        $copy_array['informationbox'] = true;
        $copy_array['myentrydisplayconf'] = false;
        $copy_array['grouproomfct'] = false;
        $copy_array['rss'] = true;
        $copy_array['language'] = true;
        $copy_array['visibilitydefaults'] = true;

        // now adaption for special rooms
        if ($source->isProjectRoom()) {
            $copy_array['grouproomfct'] = true;
        }

        if (!$source->isPrivateRoom()) {
            $copy_array['title'] = false;
        }

        // new private room
        // only copy entry rubric
        if ($source->isPrivateRoom()) {
            $copy_array['homeconf'] = false;
            $copy_array['timespread'] = false;
            $copy_array['plugins'] = false;
            $copy_array['color'] = false;
            $copy_array['usageinfo'] = false;
            $copy_array['chat'] = false;
            $copy_array['detailboxconf'] = false;
            $copy_array['listboxconf'] = false;
            $copy_array['homerightconf'] = false;
            $copy_array['datespresentationstatus'] = false;
            $copy_array['htmltextareastatus'] = false;
            $copy_array['emailtext'] = false;
            $copy_array['title'] = false;
            $copy_array['logo'] = false;
            $copy_array['wiki'] = false;
            $copy_array['informationbox'] = false;
            $copy_array['myentrydisplayconf'] = true;
        }

        // room context
        if ($copy_array['context']) {
            $target->setRoomContext($source->getRoomContext());
        }

        // config of home
        if ($copy_array['homeconf']) {
            $target->setHomeConf($source->getHomeConf());
        }

        // time spread
        if ($copy_array['timespread']) {
            if ($source->isProjectRoom()
                or $source->isGroupRoom()
            ) {
                $target->setTimeSpread($source->getTimeSpread());
            }
        }

        // config of extras
        if ($copy_array['extras']) {
            $extra_config = $source->getExtraConfig();
            unset($extra_config['TEMPLATE']);
            $target->setExtraConfig($extra_config);
        }

        // config of plugins
        if ($copy_array['plugins']) {
            $target->setPluginConfig($source->getPluginConfig());
        }

        // config of colors
        if ($copy_array['color']) {
            $target->setColorArray($source->getColorArray());
            $target->generateLayoutImages();
        }

        //ToDos
        if ($copy_array['todostatus']) {
            $target->setExtraToDoStatusArray($source->getExtraToDoStatusArray());
        }

        // config of usage infos
        if ($copy_array['usageinfo']) {
            $target->setUsageInfoArray($source->getUsageInfoArray());
            $target->setUsageInfoHeaderArray($source->getUsageInfoHeaderArray());
            $target->setUsageInfoTextArray($source->getUsageInfoTextArray());
            $target->setUsageInfoFormArray($source->getUsageInfoFormArray());
            $target->setUsageInfoFormHeaderArray($source->getUsageInfoFormHeaderArray());
            $target->setUsageInfoFormTextArray($source->getUsageInfoFormTextArray());
        }

        // config of path
        if ($copy_array['topicpath']) {
            if ($source->withPath()) {
                $target->setWithPath();
            } else {
                $target->setWithoutPath();
            }
        }

        // config of tags
        if ($copy_array['tag']) {
            if ($source->isTagMandatory()) {
                $target->setTagMandatory();
            } else {
                $target->unsetTagMandatory();
            }
            if ($source->isTagEditedByAll()) {
                $target->setTagEditedByAll();
            } else {
                $target->setTagEditedByModerator();
            }
            if ($source->withTags()) {
                $target->setWithTags();
            } else {
                $target->setWithoutTags();
            }
            if ($source->isTagsShowExpanded()) {
                $target->setTagsShowExpanded();
            } else {
                $target->unsetTagsShowExpanded();
            }
        }

        if ($copy_array['discussionstatus']) {
            $target->setDiscussionStatus($source->getDiscussionStatus());
        }
        if ($copy_array['todomanagementstatus']) {
            $target->setTodoManagmentStatus($source->getTodoManagmentStatus());
        }
        if ($copy_array['detailboxconf']) {
            $target->setDetailBoxConf($source->getDetailBoxConf());
        }
        if ($copy_array['listboxconf']) {
            $target->setListBoxConf($source->getListBoxConf());
        }
        if ($copy_array['homerightconf']) {
            $target->setHomeRightConf($source->getHomeRightConf());
        }
        if ($copy_array['datespresentationstatus']) {
            $target->setDatesPresentationStatus($source->getDatesPresentationStatus());
        }
        if ($copy_array['htmltextareastatus']) {
            $target->setHtmlTextAreaStatus($source->getHtmlTextAreaStatus());
        }
        // config of buzzwords
        if ($copy_array['buzzword']) {
            if ($source->isBuzzwordMandatory()) {
                $target->setBuzzwordMandatory();
            } else {
                $target->unsetBuzzwordMandatory();
            }
            if ($source->withBuzzwords()) {
                $target->setWithBuzzwords();
            } else {
                $target->setWithoutBuzzwords();
            }
            if ($source->isBuzzwordShowExpanded()) {
                $target->setBuzzwordShowExpanded();
            } else {
                $target->unsetBuzzwordShowExpanded();
            }
        }

        // config of netnavigation
        if ($copy_array['netnavigation']) {
            if ($source->isNetnavigationShowExpanded()) {
                $target->setNetnavigationShowExpanded();
            } else {
                $target->unsetNetnavigationShowExpanded();
            }
            if ($source->withNetnavigation()) {
                $target->setWithNetnavigation();
            } else {
                $target->setWithoutNetnavigation();
            }
        }

        // config of email message tags
        if ($copy_array['emailtext']) {
            $target->setEmailTextArray($source->getEmailTextArray());
        }

        // title and logo
        if ($copy_array['title']) {
            if ($source->isPrivateRoom()) {
                $title = $source->getTitlePure();
                $translator = $this->legacyEnvironment->getTranslationObject();
                if (empty($title)
                    or $title == $translator->getMessage('COMMON_PRIVATEROOM')
                ) {
                    $title = 'PRIVATEROOM';
                }
                $target->setTitle($title);
            } else {
                $target->setTitle($source->getTitle());
            }
        }
        if ($copy_array['logo']) {
            $disc_manager = $this->legacyEnvironment->getDiscManager();
            if ($source->getItemID() > 99) {
                if ($disc_manager->copyImageFromRoomToRoom($source->getLogoFilename(), $target->getItemID())) {
                    $logo_file_name_new = str_replace($source->getItemID(), $target->getItemID(),
                        $source->getLogoFilename());
                    $target->setLogoFilename($logo_file_name_new);
                }
            } else {
                $target->setLogoFilename('');
                $disc_manager->unlinkFile($target->getLogoFilename());
            }
        }
        if ($copy_array['BGImage']) {
            $disc_manager = $this->legacyEnvironment->getDiscManager();
            if ($source->getItemID() > 99) {
                if ($disc_manager->copyImageFromRoomToRoom($source->getBGImageFilename(), $target->getItemID())) {
                    $logo_file_name_new = str_replace($source->getItemID(), $target->getItemID(),
                        $source->getBGImageFilename());
                    $target->setBGImageFilename($logo_file_name_new);
                }
            } else {
                $target->setBGImageFilename('');
                $disc_manager->unlinkFile($target->getBGImageFilename());
            }
        }

        // information box
        if ($copy_array['informationbox']) {
            if ($source->withInformationBox()) {
                $target->setwithInformationBox('yes');
            } else {
                $target->setwithInformationBox('no');
            }
        }

        // my entry display configuration
        if ($copy_array['myentrydisplayconf']) {
            $target->setMyEntriesDisplayConfig($source->getMyEntriesDisplayConfig());
        }

        // grouproom functions
        if ($copy_array['grouproomfct']) {
            if ($source->withGrouproomFunctions()) {
                $target->setWithGrouproomFunctions();
            } else {
                $target->setWithGrouproomFunctions();
            }
            if ($source->isGrouproomActive()) {
                $target->setGrouproomActive();
            } else {
                $target->setGrouproomInactive();
            }
        }

        // rss
        if ($copy_array['rss']) {
            if ($source->isRSSOn()) {
                $target->turnRSSOn();
            } else {
                $target->turnRSSOff();
            }
        }

        // config of language
        if ($copy_array['language']) {
            $target->setLanguage($source->getLanguage());
        }

        // config of dates presentation status
        if ($copy_array['visibilitydefaults']) {
            if ($source->isActionBarVisibleAsDefault()) {
                $target->setActionBarVisibilityDefault('1');
            } else {
                $target->setActionBarVisibilityDefault('-1');
            }

            if ($source->isReferenceBarVisibleAsDefault()) {
                $target->setReferenceBarVisibilityDefault('1');
            } else {
                $target->setReferenceBarVisibilityDefault('-1');
            }

            if ($source->isDetailsBarVisibleAsDefault()) {
                $target->setDetailsBarVisibilityDefault('1');
            } else {
                $target->setDetailsBarVisibilityDefault('-1');
            }

            if ($source->isAnnotationsBarVisibleAsDefault()) {
                $target->setAnnotationsBarVisibilityDefault('1');
            } else {
                $target->setAnnotationsBarVisibilityDefault('-1');
            }
        }
    }

    /**
     * @param cs_room_item $source
     * @param cs_room_item $target
     */
    public function copyData(cs_room_item $source, cs_room_item $target, cs_user_item $creator): void
    {
        $copy_array = [];
        $copy_array['informationbox'] = true;
        $copy_array['usageinfo'] = true;
        $copy_array['grouproom'] = false;

        // now adaption for special rooms
        if ($source->isProjectRoom()) {
            $copy_array['grouproom'] = true;
        }

        // new private room
        // only copy entry rubric
        if ($source->isPrivateRoom()) {
            $copy_array['usageinfo'] = false;
            $copy_array['informationbox'] = false;
        }

        // copy data
        $new_id_array = [];

        $data_type_array = [];
        $data_type_array[] = CS_ANNOUNCEMENT_TYPE;
        $data_type_array[] = CS_DATE_TYPE;
        $data_type_array[] = CS_DISCUSSION_TYPE;
        $data_type_array[] = CS_LABEL_TYPE;
        $data_type_array[] = CS_MATERIAL_TYPE;
        $data_type_array[] = CS_FILE_TYPE;
        $data_type_array[] = CS_TODO_TYPE;
        $data_type_array[] = CS_TAG_TYPE;

        foreach ($data_type_array as $type) {
            $manager = $this->legacyEnvironment->getManager($type);
            $id_array = $manager->copyDataFromRoomToRoom(
                $source->getItemID(),
                $target->getItemID(),
                $creator->getItemID());
            $new_id_array = $new_id_array + $id_array;
        }
        unset($data_type_array);

        // copy secondary data
        $data_type_array = [];
        $data_type_array[] = CS_ANNOTATION_TYPE;
        $data_type_array[] = CS_DISCARTICLE_TYPE;
        $data_type_array[] = CS_SECTION_TYPE;
        $data_type_array[] = CS_STEP_TYPE;

        foreach ($data_type_array as $type) {
            $manager = $this->legacyEnvironment->getManager($type);
            $id_array = $manager->copyDataFromRoomToRoom(
                $source->getItemID(),
                $target->getItemID(),
                $creator->getItemID(),
                $new_id_array);
            $new_id_array = $new_id_array + $id_array;
        }
        unset($data_type_array);

        // copy links
        $data_type_array = [];
        $data_type_array[] = CS_LINK_TYPE;
        $data_type_array[] = CS_LINKITEM_TYPE;
        $data_type_array[] = CS_LINKITEMFILE_TYPE;
        $data_type_array[] = CS_TAG2TAG_TYPE;

        foreach ($data_type_array as $type) {
            $manager = $this->legacyEnvironment->getManager($type);
            $id_array = $manager->copyDataFromRoomToRoom(
                $source->getItemID(),
                $target->getItemID(),
                $creator->getItemID(),
                $new_id_array);
            $new_id_array = $new_id_array + $id_array;
        }
        unset($data_type_array);


        if ($copy_array['informationbox']) {
            if ($source->withInformationBox()) {
                $target->setwithInformationBox('yes');
                $id = $source->getInformationBoxEntryID();
                if (isset($new_id_array[$id])) {
                    $target->setInformationBoxEntryID($new_id_array[$id]);
                }
            }
        }

        // link modifier item
        $manager = $this->legacyEnvironment->getLinkModifierItemManager();
        foreach ($id_array as $value) {
            if (!mb_stristr($value, CS_FILE_TYPE)) {
                $manager->markEdited($value, $creator->getItemID());
            }
        }

        // now change all old item ids in descriptions with new IDs
        // copy data
        $data_type_array = [];
        $data_type_array[] = CS_ANNOUNCEMENT_TYPE;
        $data_type_array[] = CS_DATE_TYPE;
        $data_type_array[] = CS_LABEL_TYPE;
        $data_type_array[] = CS_MATERIAL_TYPE;
        $data_type_array[] = CS_TODO_TYPE;
        $data_type_array[] = CS_ANNOTATION_TYPE;
        $data_type_array[] = CS_DISCARTICLE_TYPE;
        $data_type_array[] = CS_SECTION_TYPE;
        $data_type_array[] = CS_STEP_TYPE;
        foreach ($data_type_array as $type) {
            $manager = $this->legacyEnvironment->getManager($type);
            $manager->refreshInDescLinks($target->getItemID(), $new_id_array);
        }
        unset($data_type_array);

        $arrayContextID = [];
        $arrayContextID[$source->getItemID()] = $target->getItemID();
        $new_id_array = $new_id_array + $arrayContextID;

        // now change all old item ids in usage infos with new IDs
        if ($copy_array['usageinfo']) {
            $array = $target->getUsageInfoTextArray();
            $new_array = [];
            foreach ($array as $key => $value) {
                $replace = false;
                preg_match_all('~\[[0-9]*(\]|\|)~u', $value, $matches);
                if (isset($matches[0])) {
                    foreach ($matches[0] as $match) {
                        $id = mb_substr($match, 1);
                        $last_char = mb_substr($id, mb_strlen($id));
                        $id = mb_substr($id, 0, mb_strlen($id) - 1);
                        if (isset($new_id_array[$id])) {
                            $value = str_replace('[' . $id . $last_char, '[' . $new_id_array[$id] . $last_char, $value);
                            $replace = true;
                        }
                    }
                    $new_array[$key] = $value;
                }
                preg_match_all('~\(:item ([0-9]*) ~u', $value, $matches);
                if (isset($matches[1])
                    and !empty($matches[1])
                ) {
                    foreach ($matches[1] as $match) {
                        $id = $match;
                        if (isset($new_id_array[$id])) {
                            $value = str_replace('(:item ' . $id, '(:item ' . $new_id_array[$id], $value);
                            $replace = true;
                        }
                    }
                    $new_array[$key] = $value;
                }
                #cid=([0-9]*)
                preg_match_all('~iid=([0-9]*) ~u', $value, $matches);
                if (isset($matches[0])
                    and !empty($matches[0])
                ) {
                    foreach ($matches[1] as $match) {
                        $id = $match;
                        if (isset($new_id_array[$id])) {
                            $value = str_replace('iid=' . $id, 'iid=' . $new_id_array[$id], $value);
                            $replace = true;
                        }
                    }
                    $new_array[$key] = $value;
                }

                preg_match_all('~cid=([0-9]*) ~xu', $value, $matches);
                if (isset($matches[0])
                    and !empty($matches[0])
                ) {
                    foreach ($matches[1] as $match) {
                        $id = $match;
                        if (isset($new_id_array[$id])) {
                            $value = str_replace('cid=' . $id, 'cid=' . $target->getItemID(), $value);
                            $replace = true;
                        }
                    }
                    $new_array[$key] = $value;
                }

                // html textarea security
                if (!empty($new_array[$key])
                    and $replace
                ) {
                    if (strstr($new_array[$key], '<!-- KFC TEXT')) {
                        include_once('functions/security_functions.php');
                        $new_array[$key] = renewSecurityHash($new_array[$key]);
                    }
                }
            }

            $target->setUsageInfoTextArray($new_array);

            $array = $target->getUsageInfoFormTextArray();
            $new_array = [];
            foreach ($array as $key => $value) {
                $replace = false;
                preg_match_all('~\[[0-9]*(\]|\|)~u', $value, $matches);
                if (isset($matches[0])) {
                    foreach ($matches[0] as $match) {
                        $id = mb_substr($match, 1);
                        $last_char = mb_substr($id, mb_strlen($id));
                        $id = mb_substr($id, 0, mb_strlen($id) - 1);
                        if (isset($new_id_array[$id])) {
                            $value = str_replace('[' . $id . $last_char, '[' . $new_id_array[$id] . $last_char, $value);
                            $replace = true;
                        }
                    }
                    $new_array[$key] = $value;
                }
                preg_match_all('~\(:item ([0-9]*) ~u', $value, $matches);
                if (isset($matches[1])
                    and !empty($matches[1])
                ) {
                    foreach ($matches[1] as $match) {
                        $id = $match;
                        if (isset($new_id_array[$id])) {
                            $value = str_replace('(:item ' . $id, '(:item ' . $new_id_array[$id], $value);
                            $replace = true;
                        }
                    }
                    $new_array[$key] = $value;
                }

                preg_match_all('~iid=([0-9]*) ~u', $value, $matches);
                if (isset($matches[0])
                    and !empty($matches[0])
                ) {
                    foreach ($matches[1] as $match) {
                        $id = $match;
                        if (isset($new_id_array[$id])) {
                            $value = str_replace('iid=' . $id, 'iid=' . $new_id_array[$id], $value);
                            $replace = true;
                        }
                    }
                    $new_array[$key] = $value;
                }

                preg_match_all('~cid=([0-9]*) ~xu', $value, $matches);
                if (isset($matches[0])
                    and !empty($matches[0])
                ) {
                    foreach ($matches[1] as $match) {
                        $id = $match;
                        if (isset($new_id_array[$id])) {
                            $value = str_replace('cid=' . $id, 'cid=' . $target->getItemID(), $value);
                            $replace = true;
                        }
                    }
                    $new_array[$key] = $value;
                }

                // html textarea security
                if (!empty($new_array[$key])
                    and $replace
                ) {
                    if (strstr($new_array[$key], '<!-- KFC TEXT')) {
                        include_once('functions/security_functions.php');
                        $new_array[$key] = renewSecurityHash($new_array[$key]);
                    }
                }
            }
            $target->setUsageInfoFormTextArray($new_array);
        }

        // information box
        if ($copy_array['informationbox']) {
            if ($source->withInformationBox()
                and isset($new_id_array)
                and !empty($new_id_array[$source->getInformationBoxEntryID()])
            ) {
                $target->setInformationBoxEntryID($new_id_array[$source->getInformationBoxEntryID()]);
            }
        }

        $target->save();

        // update the the Elastic search index with all newly created items
        $itemManager = $this->legacyEnvironment->getItemManager();
        $itemList = $itemManager->getItemList($new_id_array);

        foreach ($itemList as $item) {
            $itemId = $item->getItemID();
            if ($itemId != $target->getItemID()) {
                $typedItem = $this->itemService->getTypedItem($itemId);
                $this->eventDispatcher->dispatch(new ItemReindexEvent($typedItem), ItemReindexEvent::class);
            }
        }

        ############################################
        # FLAG: group rooms
        ############################################
        if ($copy_array['informationbox']) {
            if ($source->showGroupRoomFunctions()) {
                // group rooms will not copied
                $group_manager = $this->legacyEnvironment->getGroupManager();
                $group_manager->setContextLimit($target->getItemID());
                $group_manager->select();
                $group_list = $group_manager->get();
                if ($group_list->isNotEmpty()) {
                    $group_item = $group_list->getFirst();
                    while ($group_item) {
                        if ($group_item->isGroupRoomActivated()) {
                            $group_item->unsetGroupRoomActive();
                            $group_item->unsetGroupRoomItemID();
                            $group_item->save();
                        }
                        $group_item = $group_list->getNext();
                    }
                }
            }
        }
    }
}