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

namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use cs_environment;
use cs_room_item;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ModerationSettingsTransformer extends AbstractTransformer
{
    protected $entity = 'moderation_settings';

    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly ParameterBagInterface $parameterBag
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->emailTexts = [
            'Select e-mail text' => '-1',
            '------------------' => 'disabled',
            'Address' => 'MAIL_BODY_HELLO',
            // 2
            'Salutation' => 'MAIL_BODY_CIAO',
            // 3
            'Delete account' => 'MAIL_BODY_USER_ACCOUNT_DELETE',
            // 5
            'Lock account' => 'MAIL_BODY_USER_ACCOUNT_LOCK',
            // 6
            'Approve membership' => 'MAIL_BODY_USER_STATUS_USER',
            // 7
            'Change status: moderator' => 'MAIL_BODY_USER_STATUS_MODERATOR',
            // 8
            'Change status: contact person' => 'MAIL_BODY_USER_MAKE_CONTACT_PERSON',
            // 9
            'Change status: no contact person' => 'MAIL_BODY_USER_UNMAKE_CONTACT_PERSON',
            // 10
            'Change status: read only user' => 'MAIL_BODY_USER_STATUS_USER_READ_ONLY',
        ];
    }

    /**
     * Transforms a cs_room_item object to an array.
     *
     * @param cs_room_item $roomItem
     */
    public function transform($roomItem): array
    {
        $temp_array = [];
        $roomData = [];

        if ($roomItem) {
            $referenceId = $roomItem->getInformationBoxEntryID();
            if (!is_numeric($referenceId)) {
                $referenceId = '';
            }

            $roomData['homenotice']['item_id'] = $referenceId;
            if ($roomItem->withInformationBox()) {
                $roomData['homenotice']['show_information_box'] = '1';
            } else {
                $roomData['homenotice']['show_information_box'] = '0';
            }

            // Usage Infos
            $translator = $this->legacyEnvironment->getTranslationObject();
            $array_info_text = [];
            $temp_array['rubric'] = $translator->getMessage('HOME_INDEX');
            $temp_array['key'] = 'home';
            $temp_array['title'] = $roomItem->getUsageInfoHeaderForRubric('home');
            $temp_array['text'] = $roomItem->getUsageInfoTextForRubricInForm('home');
            $array_info_text[] = $temp_array;
            $roomData['usernotice']['title_home'] = $roomItem->getUsageInfoHeaderForRubric('home');
            $roomData['usernotice']['description_home'] = $roomItem->getUsageInfoTextForRubricInForm('home');
            foreach ($roomItem->getAvailableRubrics() as $rubric) {
                $temp_array = [];
                $temp_array['rubric'] = match (mb_strtoupper((string) $rubric, 'UTF-8')) {
                    'ANNOUNCEMENT' => $translator->getMessage('ANNOUNCEMENT_INDEX'),
                    'DATE' => $translator->getMessage('DATE_INDEX'),
                    'DISCUSSION' => $translator->getMessage('DISCUSSION_INDEX'),
                    'INSTITUTION' => $translator->getMessage('INSTITUTION_INDEX'),
                    'GROUP' => $translator->getMessage('GROUP_INDEX'),
                    'MATERIAL' => $translator->getMessage('MATERIAL_INDEX'),
                    'PROJECT' => $translator->getMessage('PROJECT_INDEX'),
                    'TODO' => $translator->getMessage('TODO_INDEX'),
                    'TOPIC' => $translator->getMessage('TOPIC_INDEX'),
                    'USER' => $translator->getMessage('USER_INDEX'),
                    default => $translator->getMessage('COMMON_MESSAGETAG_ERROR cs_configuration_usageinfo_form(113) '),
                };
                $temp_array['key'] = $rubric;
                $temp_array['title'] = $roomItem->getUsageInfoHeaderForRubric($rubric);
                $temp_array['text'] = $roomItem->getUsageInfoTextForRubricInForm($rubric);

                $roomData['usernotice']['title_'.$rubric] = $roomItem->getUsageInfoHeaderForRubric($rubric);
                $roomData['usernotice']['description_'.$rubric] = $roomItem->getUsageInfoTextForRubricInForm($rubric);

                $array_info_text[] = $temp_array;
                unset($temp_array);
            }
            $roomData['usernotice']['array_info_text'] = $array_info_text;

            // Mail
            $roomData['email_configuration'] = [];
            $roomData['email_configuration']['email_text_titles'] = $this->emailTexts;
            foreach ($roomItem->getEmailTextArray() as $name => $valueArray) {
                foreach ($valueArray as $language => $message) {
                    if (!empty($message)) {
                        $roomData['email_configuration'][mb_strtolower(str_replace('CHOICE', 'BODY', (string) $name)).'_'.$language] = $message;
                    }
                }
            }

            $emailDefaultValues = [];
            foreach (array_values($this->emailTexts) as $message_tag) {
                foreach (['de', 'en'] as $language) {
                    $emailDefaultValues[mb_strtolower(str_replace('CHOICE', 'BODY', (string) $message_tag)).'_'.$language] = $translator->getEmailMessageInLang($language, $message_tag);
                }
            }

            $roomData['email_configuration'] = array_merge($emailDefaultValues, $roomData['email_configuration']);
        }

        return $roomData;
    }

    /**
     * Save moderation settings.
     *
     * @param object $roomObject
     * @param array  $roomData
     *
     * @throws TransformationFailedException if room item is not found
     */
    public function applyTransformation($roomObject, $roomData): cs_room_item
    {
        if (!empty($roomData['homenotice']['item_id'])) {
            $roomObject->setInformationBoxEntryID($roomData['homenotice']['item_id']);
        }
        if ('1' == $roomData['homenotice']['show_information_box']) {
            $roomObject->setwithInformationBox('yes');
        } else {
            $roomObject->setwithInformationBox('no');
        }

        // Rubrics usage info
        foreach (array_merge(['home'], $roomObject->getAvailableRubrics()) as $rubric) {
            $roomObject->setUsageInfoHeaderForRubric($rubric, $roomData['usernotice']['title_'.$rubric]);
            $roomObject->setUsageInfoTextForRubric($rubric, $roomData['usernotice']['description_'.$rubric]);
        }

        // Mail
        if (isset($roomData['email_configuration'])) {
            $store = [];
            $enabledLocales = $this->parameterBag->get('kernel.enabled_locales');
            foreach ($roomData['email_configuration'] as $name => $value) {
                if (str_starts_with((string) $name, 'mail_body')) {
                    $fieldName = strtoupper(substr((string) $name, 0, -3));
                    $lang = substr((string) $name, -2);

                    if (in_array($lang, $enabledLocales)) {
                        $store[$fieldName][$lang] = $value;
                    }
                }
            }

            foreach ($store as $tag => $values) {
                $roomObject->setEmailText($tag, $values);
            }
        }

        return $roomObject;
    }
}
