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


             /*
          // mail text choice
          $array_mail_text[0]['text']  = '*'.$translator->getMessage('MAIL_CHOICE_CHOOSE_TEXT');
          $array_mail_text[0]['value'] = -1;

          // mail salutation
          $array_mail_text[1]['text']  = '----------------------';
          $array_mail_text[1]['value'] = 'disabled';
          $array_mail_text[2]['text']  = $translator->getMessage('MAIL_CHOICE_HELLO');
          $array_mail_text[2]['value'] = 'MAIL_CHOICE_HELLO';

          $array_mail_text[3]['text']  = $translator->getMessage('MAIL_CHOICE_CIAO');
          $array_mail_text[3]['value'] = 'MAIL_CHOICE_CIAO';

          // user
          $array_mail_text[4]['text']  = '----------------------';
          $array_mail_text[4]['value'] = 'disabled';
          $array_mail_text[5]['text']  = $translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_DELETE');
          $array_mail_text[5]['value'] = 'MAIL_CHOICE_USER_ACCOUNT_DELETE';
          $array_mail_text[6]['text']  = $translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_LOCK');
          $array_mail_text[6]['value'] = 'MAIL_CHOICE_USER_ACCOUNT_LOCK';
          $array_mail_text[7]['text']  = $translator->getMessage('MAIL_CHOICE_USER_STATUS_USER');
          $array_mail_text[7]['value'] = 'MAIL_CHOICE_USER_STATUS_USER';
          $array_mail_text[8]['text']  = $translator->getMessage('MAIL_CHOICE_USER_STATUS_MODERATOR');
          $array_mail_text[8]['value'] = 'MAIL_CHOICE_USER_STATUS_MODERATOR';
          $array_mail_text[9]['text']  = $translator->getMessage('MAIL_CHOICE_USER_MAKE_CONTACT_PERSON');
          $array_mail_text[9]['value'] = 'MAIL_CHOICE_USER_MAKE_CONTACT_PERSON';
          $array_mail_text[10]['text']  = $translator->getMessage('MAIL_CHOICE_USER_UNMAKE_CONTACT_PERSON');
          $array_mail_text[10]['value'] = 'MAIL_CHOICE_USER_UNMAKE_CONTACT_PERSON';
          $array_mail_text[11]['text']  = $translator->getMessage('MAIL_CHOICE_USER_STATUS_READ_ONLY_USER');
          $array_mail_text[11]['value'] = 'MAIL_CHOICE_USER_STATUS_READ_ONLY_USER';
          if ($this->legacyEnvironment->inCommunityRoom()) {
             $array_mail_text[12]['text']  = $translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_PASSWORD');
             $array_mail_text[12]['value'] = 'MAIL_CHOICE_USER_ACCOUNT_PASSWORD';
             $array_mail_text[13]['text']  = $translator->getMessage('MAIL_CHOICE_USER_ACCOUNT_MERGE');
             $array_mail_text[13]['value'] = 'MAIL_CHOICE_USER_ACCOUNT_MERGE';
          }
          

          $languages = $this->legacyEnvironment->getAvailableLanguageArray();
          foreach($array_mail_text as $index => $array) {
            switch($array['value']) {
                case -1:                                        $message_tag = ''; break;
                case 'MAIL_CHOICE_HELLO':                       $message_tag = 'MAIL_BODY_HELLO'; break;
                case 'MAIL_CHOICE_CIAO':                        $message_tag = 'MAIL_BODY_CIAO'; break;
                case 'MAIL_CHOICE_USER_ACCOUNT_DELETE':         $message_tag = 'MAIL_BODY_USER_ACCOUNT_DELETE'; break;
                case 'MAIL_CHOICE_USER_ACCOUNT_LOCK':           $message_tag = 'MAIL_BODY_USER_ACCOUNT_LOCK'; break;
                case 'MAIL_CHOICE_USER_STATUS_USER':            $message_tag = 'MAIL_BODY_USER_STATUS_USER'; break;
                case 'MAIL_CHOICE_USER_STATUS_MODERATOR':       $message_tag = 'MAIL_BODY_USER_STATUS_MODERATOR'; break;
                case 'MAIL_CHOICE_USER_MAKE_CONTACT_PERSON':    $message_tag = 'MAIL_BODY_USER_MAKE_CONTACT_PERSON'; break;
                case 'MAIL_CHOICE_USER_UNMAKE_CONTACT_PERSON':  $message_tag = 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON'; break;
                case 'MAIL_CHOICE_USER_ACCOUNT_PASSWORD':       $message_tag = 'MAIL_BODY_USER_ACCOUNT_PASSWORD'; break;
                case 'MAIL_CHOICE_USER_ACCOUNT_MERGE':          $message_tag = 'MAIL_BODY_USER_ACCOUNT_MERGE'; break;
                case 'MAIL_CHOICE_USER_PASSWORD_CHANGE':        $message_tag = 'MAIL_BODY_USER_PASSWORD_CHANGE'; break;
                case 'MAIL_CHOICE_MATERIAL_WORLDPUBLIC':        $message_tag = 'MAIL_BODY_MATERIAL_WORLDPUBLIC'; break;
                case 'MAIL_CHOICE_MATERIAL_NOT_WORLDPUBLIC':    $message_tag = 'MAIL_BODY_MATERIAL_NOT_WORLDPUBLIC'; break;
                case 'MAIL_CHOICE_ROOM_LOCK':                   $message_tag = 'MAIL_BODY_ROOM_LOCK'; break;
                case 'MAIL_CHOICE_ROOM_UNLOCK':                 $message_tag = 'MAIL_BODY_ROOM_UNLOCK'; break;
                case 'MAIL_CHOICE_ROOM_UNLINK':                 $message_tag = 'MAIL_BODY_ROOM_UNLINK'; break;
                case 'MAIL_CHOICE_ROOM_DELETE':                 $message_tag = 'MAIL_BODY_ROOM_DELETE'; break;
                case 'MAIL_CHOICE_ROOM_OPEN':                   $message_tag = 'MAIL_BODY_ROOM_OPEN'; break;
                case 'MAIL_CHOICE_USER_STATUS_READ_ONLY_USER':  $message_tag = 'MAIL_BODY_USER_STATUS_USER_READ_ONLY'; break;
            }

            foreach ($languages as $language) {
                if (!empty($message_tag)) {
                    $array_mail_text[$index]['body_' . $language] = $translator->getEmailMessageInLang($language,$message_tag);
                } else {
                    $array_mail_text[$index]['body_' . $language] = '';
                }
            }

        }

*/

            foreach ($roomItem->getEmailTextArray() as $name => $valueArray){
                foreach ($valueArray as $language => $message) {
                    if(!empty($message)){
                        $roomData['email_configuration'][mb_strtolower(str_replace("CHOICE", "BODY", $name)) . "_" . $language] = $message;                        
                    }
                }
            }
        }

        dump($roomItem);
        dump($roomData);

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

        // foreach ($roomData as $name => $value )

        // {
        //     $fieldName = substr($name, 0, 20);
        //     if ( substr($name, 0, 20) === "moderation_mail_body" )
        //     {
        //         $lang = substr($name, 21, 2);

        //         $num = substr($name, 24);
                
        //         switch ( $num )
        //         {
        //             case 2: $messageTag = "MAIL_BODY_HELLO";                            break;
        //             case 3: $messageTag = "MAIL_BODY_CIAO";                             break;
        //             case 5: $messageTag = "MAIL_BODY_USER_ACCOUNT_DELETE";              break;
        //             case 6: $messageTag = "MAIL_BODY_USER_ACCOUNT_LOCK";                break;
        //             case 7: $messageTag = "MAIL_BODY_USER_STATUS_USER";                 break;
        //             case 8: $messageTag = "MAIL_BODY_USER_STATUS_MODERATOR";            break;
        //             case 9: $messageTag = "MAIL_BODY_USER_MAKE_CONTACT_PERSON";         break;
        //             case 10: $messageTag = "MAIL_BODY_USER_UNMAKE_CONTACT_PERSON";      break;
        //             case 11: $messageTag = "MAIL_BODY_USER_STATUS_USER_READ_ONLY";      break;
        //             case 12: $messageTag = "MAIL_BODY_USER_ACCOUNT_PASSWORD";           break;
        //             case 13: $messageTag = "MAIL_BODY_USER_ACCOUNT_MERGE";              break;
                    
        //         }
                
        //         $languages = $this->legacyEnvironment->getAvailableLanguageArray();
        //         if ( in_array($lang, $languages ))
        //         {
        //             $store[$messageTag][$lang] = $value;
        //         }
        //     }
        // }
        
        // foreach ( $store as $tag => $values )
        // {
        //     $roomObject->setEmailText($tag, $values);
        // }

        dump($roomData);
        dump($roomObject);

        return $roomObject;
    }
}
