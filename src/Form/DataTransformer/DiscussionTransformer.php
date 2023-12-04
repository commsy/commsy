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
use cs_discussion_item;
use cs_environment;

class DiscussionTransformer extends AbstractTransformer
{
    protected $entity = 'discussion';

    private readonly cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_discussion_item object to an array.
     *
     * @param cs_discussion_item $discussionItem
     */
    public function transform($discussionItem): array
    {
        $discussionData = [];

        if ($discussionItem) {
            $discussionData['title'] = html_entity_decode($discussionItem->getTitle());
            $discussionData['draft'] = $discussionItem->isDraft();
            $discussionData['permission'] = $discussionItem->isPrivateEditing();
            $discussionData['description'] = $discussionItem->getDescription();

            if ($discussionItem->isNotActivated()) {
                $discussionData['hidden'] = true;

                $activating_date = $discussionItem->getActivatingDate();
                if (!stristr((string) $activating_date, '9999')) {
                    $datetime = new \DateTime($activating_date);
                    $discussionData['hiddendate']['date'] = $datetime;
                    $discussionData['hiddendate']['time'] = $datetime;
                }
            }

            // external viewer
            if ($this->legacyEnvironment->getCurrentContextItem()->isPrivateRoom()) {
                $discussionData['external_viewer_enabled'] = true;
                $discussionData['external_viewer'] = $discussionItem->getExternalViewerString();
            } else {
                $discussionData['external_viewer_enabled'] = false;
            }
        }

        return $discussionData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param object $discussionObject
     * @param array  $discussionData
     *
     * @throws TransformationFailedException if room item is not found
     */
    public function applyTransformation($discussionObject, $discussionData): cs_discussion_item
    {
        $discussionObject->setTitle($discussionData['title']);
        $discussionObject->setDescription($discussionData['description'] ?: '');
        if ($discussionData['permission']) {
            $discussionObject->setPrivateEditing('0');
        } else {
            $discussionObject->setPrivateEditing('1');
        }

        if (isset($discussionData['hidden'])) {
            if ($discussionData['hidden']) {
                if (isset($discussionData['hiddendate']['date'])) {
                    // add validdate to validdate
                    $datetime = $discussionData['hiddendate']['date'];
                    if ($discussionData['hiddendate']['time']) {
                        $time = explode(':', (string) $discussionData['hiddendate']['time']->format('H:i'));
                        $datetime->setTime($time[0], $time[1]);
                    }
                    $discussionObject->setActivationDate($datetime->format('Y-m-d H:i:s'));
                } else {
                    $discussionObject->setActivationDate('9999-00-00 00:00:00');
                }
            } else {
                if ($discussionObject->isNotActivated()) {
                    $discussionObject->setActivationDate(null);
                }
            }
        } else {
            if ($discussionObject->isNotActivated()) {
                $discussionObject->setActivationDate(null);
            }
        }

        // external viewer
        if ($this->legacyEnvironment->getCurrentContextItem()->isPrivateRoom()) {
            if (!empty(trim((string) $discussionData['external_viewer']))) {
                $userIds = explode(' ', (string) $discussionData['external_viewer']);
                $discussionObject->setExternalViewerAccounts($userIds);
            } else {
                $discussionObject->unsetExternalViewerAccounts();
            }
        }

        return $discussionObject;
    }
}
