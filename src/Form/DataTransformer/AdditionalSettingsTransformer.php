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
use cs_room_item;

class AdditionalSettingsTransformer extends AbstractTransformer
{
    protected $entity = 'additional_settings';

    private \cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_room_item object into an array.
     *
     * @param \cs_room_item $roomItem
     *
     * @return array
     */
    public function transform($roomItem)
    {
        $roomData = [];

        if ($roomItem) {
            // structural auxilaries
            $roomData['structural_auxilaries']['associations']['show_expanded'] = $roomItem->isAssociationShowExpanded();

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
            $roomData['tasks']['additional_status'] = $roomItem->getExtraToDoStatusArray();

            // templates
            $roomData['template']['status'] = $roomItem->isTemplate();
            $roomData['template']['template_description'] = $roomItem->getTemplateDescription();
            if ($roomItem->isCommunityRoom()) {
                $roomData['template']['template_availability'] = $roomItem->getCommunityTemplateAvailability();
            } else {
                $roomData['template']['template_availability'] = $roomItem->getTemplateAvailability();
            }

            // rss
            if ($roomItem->isRSSOn()) {
                $roomData['rss']['status'] = '1';
            } else {
                $roomData['rss']['status'] = '2';
            }

            // archived
            $roomData['archived']['active'] = method_exists($roomItem, 'getArchived') && $roomItem->getArchived();

            // terms and conditions
            $roomData['terms']['status'] = $roomItem->getAGBStatus();
            if ('1' != $roomData['terms']['status']) {
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
     * Save additional settings.
     *
     * @param object $roomObject
     * @param array  $roomData
     *
     * @return \cs_room_item|null
     *
     * @throws TransformationFailedException if room item is not found
     */
    public function applyTransformation($roomObject, $roomData)
    {
        $agbtext_array = [];
        /********* save buzzword and tag options ******/
        $associations = $roomData['structural_auxilaries']['associations'];
        $buzzwords = $roomData['structural_auxilaries']['buzzwords'];
        $categories = $roomData['structural_auxilaries']['categories'];
        $calendars = $roomData['structural_auxilaries']['calendars'];

        // association options
        if (isset($associations['show_expanded']) and !empty($associations['show_expanded']) and true == $associations['show_expanded']) {
            $roomObject->setAssociationShowExpanded();
        } else {
            $roomObject->unsetAssociationShowExpanded();
        }

        // buzzword options
        if (isset($buzzwords['activate']) and !empty($buzzwords['activate']) and true == $buzzwords['activate']) {
            $roomObject->setWithBuzzwords();
        } else {
            $roomObject->setWithoutBuzzwords();
        }
        if (isset($buzzwords['show_expanded']) and !empty($buzzwords['show_expanded']) and true == $buzzwords['show_expanded']) {
            $roomObject->setBuzzwordShowExpanded();
        } else {
            $roomObject->unsetBuzzwordShowExpanded();
        }
        if (isset($buzzwords['mandatory']) and !empty($buzzwords['mandatory']) and true == $buzzwords['mandatory']) {
            $roomObject->setBuzzwordMandatory();
        } else {
            $roomObject->unsetBuzzwordMandatory();
        }

        // tag options
        if (isset($categories['activate']) and !empty($categories['activate']) and true == $categories['activate']) {
            $roomObject->setWithTags();
        } else {
            $roomObject->setWithoutTags();
        }
        if (isset($categories['show_expanded']) and !empty($categories['show_expanded']) and true == $categories['show_expanded']) {
            $roomObject->setTagsShowExpanded();
        } else {
            $roomObject->unsetTagsShowExpanded();
        }
        if (isset($categories['mandatory']) and !empty($categories['mandatory']) and true == $categories['mandatory']) {
            $roomObject->setTagMandatory();
        } else {
            $roomObject->unsetTagMandatory();
        }
        if (isset($categories['edit']) and !empty($categories['edit']) and true == $categories['edit']) {
            $roomObject->setTagEditedByModerator();
        } else {
            $roomObject->setTagEditedByAll();
        }

        // calendar options
        if (isset($calendars['edit']) and !empty($calendars['edit']) and true == $calendars['edit']) {
            $roomObject->setUsersCanEditCalendars();
        } else {
            $roomObject->unsetUsersCanEditCalendars();
        }
        if (isset($calendars['external']) and !empty($calendars['external']) and true == $calendars['external']) {
            $roomObject->setUsersCanSetExternalCalendarsUrl();
        } else {
            $roomObject->unsetUsersCanSetExternalCalendarsUrl();
        }

        /********* save template options ******/
        $template = $roomData['template'];

        if (isset($template['status']) and !empty($template['status'])) {
            if (true == $template['status']) {
                $roomObject->setTemplate();
            } else {
                $roomObject->setNotTemplate();
            }
        } elseif ($roomObject->isProjectRoom()
                or $roomObject->isCommunityRoom()
                or $roomObject->isPrivateRoom()
                or $roomObject->isGroupRoom()
        ) {
            $roomObject->setNotTemplate();
        }
        if (isset($template['template_availability'])) {
            if ($roomObject->isCommunityRoom()) {
                $roomObject->setCommunityTemplateAvailability($template['template_availability']);
            } else {
                $roomObject->setTemplateAvailability($template['template_availability']);
            }
        }
        if (isset($template['template_description'])) {
            $roomObject->setTemplateDescription($template['template_description']);
        }

        /********* rss options ******/
        if (isset($roomData['rss']['status'])) {
            if ('1' === $roomData['rss']['status']) {
                $roomObject->turnRSSOn();
            } elseif ('2' === $roomData['rss']['status']) {
                $roomObject->turnRSSOff();
            }
        } else {
            $roomObject->turnRSSOff();
        }

        /********* save archive options ******/
        $archived = $roomData['archived'];
        if (isset($archived['active']) && method_exists($roomObject, 'getArchived')) {
            if ($archived['active']) {
                if (!$roomObject->getArchived()) {
                    $roomObject->setArchived(true);
                }
            } else {
                if ($roomObject->getArchived()) {
                    $roomObject->setArchived(false);
                }
            }
        }

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

        if (($agbtext_array != $roomObject->getAGBTextArray()) or ($roomData['terms']['status'] != $roomObject->getAGBStatus())) {
            $now = new \DateTimeImmutable();
            $roomObject->setAGBStatus($roomData['terms']['status']);
            $roomObject->setAGBTextArray($agbtext_array);
            $roomObject->setAGBChangeDate($now);
            $current_user->setAGBAcceptanceDate($now);
            $current_user->save();
        }

        /***************** save extra task status ************/
        $roomObject->setExtraToDoStatusArray(array_filter($roomData['tasks']['additional_status']));

        return $roomObject;
    }
}
