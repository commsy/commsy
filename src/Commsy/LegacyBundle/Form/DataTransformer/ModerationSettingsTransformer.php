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

            $roomData['homenotice']['item_id'] = $roomItem->getInformationBoxEntryID();
            if ( $roomItem->withInformationBox() ) {
               $roomData['homenotice']['show_information_box'] = '1';
            } else {
               $roomData['homenotice']['show_information_box'] = '0';
            }

            // Usage Infos
            $translator = $this->legacyEnvironment->getTranslationObject();
            $default_rubrics = $roomItem->getAvailableRubrics();

            dump($default_rubrics);
            //$roomData['usernotice']['array_info_text_rubric'] = $default_rubrics->values();

            $array_info_text = array();
            $temp_array['rubric'] = $translator->getMessage('HOME_INDEX');
            $temp_array['key'] = 'home';
            $temp_array['title'] = $roomItem->getUsageInfoHeaderForRubric('home');
            $temp_array['text'] = $roomItem->getUsageInfoTextForRubricInForm('home');
            $array_info_text[] = $temp_array;
            foreach ($default_rubrics as $rubric) {
                 $temp_array = array();
                 switch ( mb_strtoupper($rubric, 'UTF-8') ){
                    case 'ANNOUNCEMENT':
                       $temp_array['rubric'] = $translator->getMessage('ANNOUNCEMENT_INDEX');
                       break;
                    case 'DATE':
                       $temp_array['rubric'] = $translator->getMessage('DATE_INDEX');
                       break;
                    case 'DISCUSSION':
                       $temp_array['rubric'] = $translator->getMessage('DISCUSSION_INDEX');
                       break;
                    case 'INSTITUTION':
                       $temp_array['rubric'] = $translator->getMessage('INSTITUTION_INDEX');
                       break;
                    case 'GROUP':
                       $temp_array['rubric'] = $translator->getMessage('GROUP_INDEX');
                       break;
                    case 'MATERIAL':
                       $temp_array['rubric'] = $translator->getMessage('MATERIAL_INDEX');
                       break;
                    case 'PROJECT':
                       $temp_array['rubric'] = $translator->getMessage('PROJECT_INDEX');
                       break;
                    case 'TODO':
                       $temp_array['rubric'] = $translator->getMessage('TODO_INDEX');
                       break;
                    case 'TOPIC':
                       $temp_array['rubric'] = $translator->getMessage('TOPIC_INDEX');
                       break;
                    case 'USER':
                       $temp_array['rubric'] = $translator->getMessage('USER_INDEX');
                       break;
                    default:
                       $temp_array['rubric'] = $translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_configuration_usageinfo_form(113) ');
                       break;
                  }
                  $temp_array['key'] = $rubric;
                  $temp_array['title'] = $roomItem->getUsageInfoHeaderForRubric($rubric);
                  $temp_array['text'] = $roomItem->getUsageInfoTextForRubricInForm($rubric);
                  $array_info_text[] = $temp_array;
                  unset($temp_array);
            }
            $roomData['usernotice']['array_info_text'] = $array_info_text;

            foreach ($roomItem->getEmailTextArray() as $name => $valueArray){
                foreach ($valueArray as $language => $message) {
                    if(!empty($message)){
                        $roomData['email_configuration'][mb_strtolower(str_replace("CHOICE", "BODY", $name)) . "_" . $language] = $message;                        
                    }
                }
            }
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

        
        // // get selected rubric from form
        // $info_rubric = $roomData['usernotice']['array_info_text_rubric'];

        // if (!empty($info_rubric)) {
        //     // if info array is empty, add rubric
        //     if (empty($info_array)) {
        //         $info_array[] = $info_rubric;
        //         $roomObject->setUsageInfoArray($info_array);
        //     }

        //     /*
        //      * Note: Why adding twice? Why differ between empty and !in_array?
        //      */

        //     // if rubric is not in array push it
        //     elseif (!in_array($info_rubric . "_no", $info_array)) {
        //         array_push($info_array, $info_rubric . "no");
        //         $roomObject->setUsageInfoArray($info_array);
        //     }

        //     // if rubric is in array remove it
        //     elseif (in_array($info_rubric . "_no", $info_array)) {
        //         $temp = array($info_rubric . "_no");
        //         $newArray = array_diff($info_array, $temp);
        //         $roomObject->setUsageInfoArray($newArray);
        //     }

        //     // set title
        //     if (!empty($roomData['usernotice']["moderation_title_" . $info_rubric])) {
        //         $text_converter = $this->legacyEnvironment->getTextConverter();
        //         $roomObject->setUsageInfoHeaderForRubric($info_rubric, $text_converter->sanitizeHTML($roomData['usernotice']["moderation_title_" . $info_rubric]));
        //     }

        //     // set text
        //     if (!empty($roomData['usernotice']["moderation_description_" . $info_rubric])) {
        //         $roomObject->setUsageInfoTextForRubric($info_rubric, $roomData['usernotice']["moderation_description_" . $info_rubric]);
        //     } else {
        //         $roomObject->setUsageInfoTextForRubric($info_rubric, "");
        //     }
        // }


        // Mail
        if(isset($roomData['email_configuration']))

        {
            $store = array();
            $languages = $this->legacyEnvironment->getAvailableLanguageArray();

            foreach ( $roomData['email_configuration'] as $name => $value )
            {
                if( substr($name, 0, 9) === "mail_body" )
                {
                    $fieldName = strtoupper((substr($name, 0, -3)));                                                   
                    $lang = substr($name, -2);

                    if ( in_array( $lang, $languages ))
                    {
                        $store[$fieldName][$lang] = $value;
                    }
                }
            }
            
            foreach ( $store as $tag => $values )
            {
                $roomObject->setEmailText($tag, $values);
            }
        }

        return $roomObject;
    }
}
