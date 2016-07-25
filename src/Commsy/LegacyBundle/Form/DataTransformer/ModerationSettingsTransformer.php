<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Utils\UserService;
use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class ModerationSettingsTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService, UserService $userService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
    }

    /**
     * Transforms a cs_room_item object to an array
     *
     * @param cs_room_item $roomItem
     * @return array
     */
    public function transform($roomItem)
    {
        $roomData = array();

        if ($roomItem) {

            $roomData['title'] = $roomItem->getTitle();
            $roomData['language'] = $roomItem->getLanguage();

        }
        return $roomData;
    }

    /**
     * Save moderation settings
     *
     * @param object $roomObject
     * @param array $roomData
     * @return cs_room_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($roomObject, $roomData)
    {
        if(!empty($roomData['homenotice']['item_id'])){
            $roomObject->setInformationBoxEntryID($roomData['homenotice']['item_id']);
        }
        if ($roomData['homenotice']['show_information_box'] == '1') {
            $roomObject->setwithInformationBox('yes');
        }
        else{
            $roomObject->setwithInformationBox('no');
        }

        $info_array = array();
        if (is_array($roomObject->_getExtra('USAGE_INFO'))) {
            $info_array = $roomObject->_getExtra('USAGE_INFO');
        }

        // get selected rubric from form
        $info_rubric = $roomData['usernotice']['array_info_text_rubric'];

        if (!empty($info_rubric)) {
            // if info array is empty, add rubric
            if (empty($info_array)) {
                $info_array[] = $info_rubric;
                $roomObject->setUsageInfoArray($info_array);
            }

            /*
             * Note: Why adding twice? Why differ between empty and !in_array?
             */

            // if rubric is not in array push it
            elseif (!in_array($info_rubric . "_no", $info_array)) {
                array_push($info_array, $info_rubric . "no");
                $roomObject->setUsageInfoArray($info_array);
            }

            // if rubric is in array remove it
            elseif (in_array($info_rubric . "_no", $info_array)) {
                $temp = array($info_rubric . "_no");
                $newArray = array_diff($info_array, $temp);
                $roomObject->setUsageInfoArray($newArray);
            }

            // set title
            if (!empty($roomData['usernotice']["moderation_title_" . $info_rubric])) {
                $text_converter = $this->legacyEnvironment->getTextConverter();
                $roomObject->setUsageInfoHeaderForRubric($info_rubric, $text_converter->sanitizeHTML($roomData['usernotice']["moderation_title_" . $info_rubric]));
            }

            // set text
            if (!empty($roomData['usernotice']["moderation_description_" . $info_rubric])) {
                $roomObject->setUsageInfoTextForRubric($info_rubric, $roomData['usernotice']["moderation_description_" . $info_rubric]);
            } else {
                $roomObject->setUsageInfoTextForRubric($info_rubric, "");
            }
        }


        // Mail
        $store = array();
        foreach ($roomData as $name => $value )
        {
            if ( substr($name, 0, 20) === "moderation_mail_body" )
            {
                $lang = substr($name, 21, 2);
                $num = substr($name, 24);
                
                switch ( $num )
                {
                    case 2: $messageTag = "MAIL_BODY_HELLO";                            break;
                    case 3: $messageTag = "MAIL_BODY_CIAO";                             break;
                    case 5: $messageTag = "MAIL_BODY_USER_ACCOUNT_DELETE";              break;
                    case 6: $messageTag = "MAIL_BODY_USER_ACCOUNT_LOCK";                break;
                    case 7: $messageTag = "MAIL_BODY_USER_STATUS_USER";                 break;
                    case 8: $messageTag = "MAIL_BODY_USER_STATUS_MODERATOR";            break;
                    case 9: $messageTag = "MAIL_BODY_USER_MAKE_CONTACT_PERSON";         break;
                    case 10: $messageTag = "MAIL_BODY_USER_UNMAKE_CONTACT_PERSON";      break;
                    case 11: $messageTag = "MAIL_BODY_USER_STATUS_USER_READ_ONLY";      break;
                    case 12: $messageTag = "MAIL_BODY_USER_ACCOUNT_PASSWORD";           break;
                    case 13: $messageTag = "MAIL_BODY_USER_ACCOUNT_MERGE";              break;
                    
                }
                
                $languages = $this->legacyEnvironment->getAvailableLanguageArray();
                if ( in_array($lang, $languages ))
                {
                    $store[$messageTag][$lang] = $value;
                }
            }
        }
        
        foreach ( $store as $tag => $values )
        {
            $roomObject->setEmailText($tag, $values);
        }

        return $roomObject;
    }
}
