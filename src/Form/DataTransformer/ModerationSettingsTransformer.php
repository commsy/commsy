<?php
namespace App\Form\DataTransformer;

use App\Utils\RoomService;
use App\Utils\UserService;
use App\Services\LegacyEnvironment;
use App\Form\DataTransformer\DataTransformerInterface;
use cs_room_item;

class ModerationSettingsTransformer  extends AbstractTransformer
{
    protected $entity = 'moderation_settings';

    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService, UserService $userService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
        $this->emailTexts = array(
          'Select e-mail text' => '-1',
          '------------------' => 'disabled',
          'Address' => 'MAIL_BODY_HELLO',                                               // 2
          'Salutation' => 'MAIL_BODY_CIAO',                                             // 3
          'Delete account' => 'MAIL_BODY_USER_ACCOUNT_DELETE',                          // 5
          'Lock account' => 'MAIL_BODY_USER_ACCOUNT_LOCK',                              // 6
          'Approve membership' => 'MAIL_BODY_USER_STATUS_USER',                         // 7
          'Change status: moderator' => 'MAIL_BODY_USER_STATUS_MODERATOR',              // 8
          'Change status: contact person' => 'MAIL_BODY_USER_MAKE_CONTACT_PERSON',      // 9
          'Change status: no contact person' => 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON', // 10
          'Change status: read only user' => 'MAIL_BODY_USER_STATUS_USER_READ_ONLY',    // 11
        );
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

            $referenceId = $roomItem->getInformationBoxEntryID();
            if (!is_numeric($referenceId)) {
                $referenceId = '';
            }

            $roomData['homenotice']['item_id'] = $referenceId;
            if ( $roomItem->withInformationBox() ) {
               $roomData['homenotice']['show_information_box'] = '1';
            } else {
               $roomData['homenotice']['show_information_box'] = '0';
            }

            // Usage Infos
            $translator = $this->legacyEnvironment->getTranslationObject();
            $array_info_text = array();
            $temp_array['rubric'] = $translator->getMessage('HOME_INDEX');
            $temp_array['key'] = 'home';
            $temp_array['title'] = $roomItem->getUsageInfoHeaderForRubric('home');
            $temp_array['text'] = $roomItem->getUsageInfoTextForRubricInForm('home');
            $array_info_text[] = $temp_array;
            $roomData['usernotice']['title_home'] = $roomItem->getUsageInfoHeaderForRubric('home');
            $roomData['usernotice']['description_home'] = $roomItem->getUsageInfoTextForRubricInForm('home');
            foreach ($roomItem->getAvailableRubrics() as $rubric) {
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

                  $roomData['usernotice']['title_' . $rubric] = $roomItem->getUsageInfoHeaderForRubric($rubric);
                  $roomData['usernotice']['description_' . $rubric] = $roomItem->getUsageInfoTextForRubricInForm($rubric);

                  $array_info_text[] = $temp_array;
                  unset($temp_array);
            }
            $roomData['usernotice']['array_info_text'] = $array_info_text;

            // Mail
            $roomData['email_configuration'] = array();
            $roomData['email_configuration']['email_text_titles'] = $this->emailTexts;
            foreach ($roomItem->getEmailTextArray() as $name => $valueArray) {
                foreach ($valueArray as $language => $message) {
                    if(!empty($message)){
                        $roomData['email_configuration'][mb_strtolower(str_replace("CHOICE", "BODY", $name)) . "_" . $language] = $message;
                    }
                }
            }

            $emailDefaultValues = array();
            foreach (array_values($this->emailTexts) as $message_tag) {
                foreach (['de', 'en'] as $language) {
                    $emailDefaultValues[mb_strtolower(str_replace("CHOICE", "BODY", $message_tag)) . "_" . $language] = $translator->getEmailMessageInLang($language,$message_tag);
                }
            }

            $roomData['email_configuration'] = array_merge($emailDefaultValues, $roomData['email_configuration']);
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

        // Rubrics usage info
        foreach(array_merge(['home'], $roomObject->getAvailableRubrics()) as $rubric){
            $roomObject->setUsageInfoHeaderForRubric($rubric, $roomData['usernotice']["title_" . $rubric]);
            $roomObject->setUsageInfoTextForRubric($rubric, $roomData['usernotice']["description_" . $rubric]);
        }

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
