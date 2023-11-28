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

use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Services\LegacyEnvironment;
use App\Services\RoomCategoriesService;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_community_item;
use cs_environment;
use cs_project_item;
use cs_room_item;
use Doctrine\Persistence\ManagerRegistry;

class GeneralSettingsTransformer extends AbstractTransformer
{
    protected $entity = 'general_settings';

    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly RoomService $roomService,
        private readonly UserService $userService,
        private readonly RoomRepository $roomRepository,
        private readonly ManagerRegistry $managerRegistry,
        private readonly RoomCategoriesService $roomCategoriesService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_room_item object to an array.
     *
     * @param cs_room_item $roomItem
     *
     * @return array
     */
    public function transform($roomItem)
    {
        $roomData = [];

        $defaultRubrics = $roomItem->getAvailableDefaultRubricArray();

        if ($roomItem) {
            $roomData['title'] = html_entity_decode($roomItem->getTitle());
            $roomData['language'] = $roomItem->getLanguage();

            if ($roomItem->checkNewMembersAlways()) {
                $roomData['access_check'] = 'always';
            } elseif ($roomItem->checkNewMembersNever()) {
                $roomData['access_check'] = 'never';
            } elseif ($roomItem->checkNewMembersWithCode()) {
                $roomData['access_check'] = 'withcode';
                $roomData['access_code'] = $roomItem->getCheckNewMemberCode();
            }

            $roomData['room_description'] = $roomItem->getDescription();

            // slugs
            /** @var Room $roomORM */
            $roomORM = $this->roomRepository->findOneBy(['itemId' => $roomItem->getItemID()]);
            $roomData['slugs'] = $roomORM->getSlugs();

            $rubrics = [];
            foreach ($this->roomService->getRubricInformation($roomItem->getItemID(), true) as $rubric) {
                [$rubricName, $modifier] = explode('_', (string) $rubric);
                $rubrics[$rubricName] = $modifier;
            }
            foreach (array_diff($defaultRubrics, array_keys($rubrics)) as $deactivated_rubric) {
                $rubrics[$deactivated_rubric] = 'off';
            }
            $roomData['rubrics'] = $rubrics;

            $roomData['assignment_restricted'] = $roomItem->isAssignmentOnlyOpenForRoomMembers();

            $roomData['open_for_guest'] = $roomItem->isOpenForGuests();

            $roomData['material_open_for_guest'] = $roomItem->isMaterialOpenForGuests();

            $linkedCommunityRooms = [];

            if (!$roomItem->isGroupRoom() && !$roomItem->isUserroom()) {
                foreach ($roomItem->getCommunityList()->to_array() as $key => $communityRoom) {
                    if ($communityRoom) {
                        $linkedCommunityRooms[] = $communityRoom->getItemID();
                    }
                }
                $roomData['community_rooms'] = $linkedCommunityRooms;
            }

            // time contexts
            if ($roomItem->isProjectRoom() || $roomItem->isGroupRoom()) {
                if ($roomItem->isContinuous()) {
                    $roomData['time_pulses'][] = 'cont';
                }

                $roomTimeList = $roomItem->getTimeList();

                if (!$roomTimeList->isEmpty()) {
                    $roomTimeItem = $roomTimeList->getFirst();
                    while ($roomTimeItem) {
                        $roomData['time_pulses'][] = $roomTimeItem->getItemID();

                        $roomTimeItem = $roomTimeList->getNext();
                    }
                }
            }
        }

        return $roomData;
    }

    /**
     * Save general settings.
     *
     * @param object $roomObject
     * @param array  $roomData
     *
     * @throws TransformationFailedException if room item is not found
     */
    public function applyTransformation($roomObject, $roomData): cs_room_item
    {
        $rubricArray = [];
        foreach (explode(',', (string) $roomData['rubricOrder']) as $rubricName) {
            $rubricValue = $roomData['rubrics'][$rubricName];
            if (0 == strcmp((string) $rubricValue, 'off')) {
                continue;
            }
            $rubricArray[] = $rubricName.'_'.$rubricValue;
        }

        $roomObject->setHomeConf(implode(',', $rubricArray));

        $roomObject->setTitle($roomData['title']);

        if (isset($roomData['language'])) {
            $roomObject->setLanguage($roomData['language']);
        }

        if (isset($roomData['room_description'])) {
            $roomObject->setDescription(strip_tags((string) $roomData['room_description']));
        } else {
            $roomObject->setDescription('');
        }

        // room categories
        if (isset($roomData['categories'])) {
            $this->roomCategoriesService->setRoomCategoriesLinkedToContext(
                $roomObject->getItemId(),
                $roomData['categories']
            );
        }

        // room slugs
        /** @var Room $roomORM */
        $roomORM = $this->roomRepository->findOneBy(['itemId' => $roomObject->getItemID()]);
        $em = $this->managerRegistry->getManager();
        $em->persist($roomORM);
        $em->flush();

        // assignment
        if ($roomObject instanceof cs_project_item && isset($roomData['community_rooms'])) {
            /*
             * if assignment is mandatory, the array must not be empty
             */
            if ('mandatory' !== $this->legacyEnvironment->getCurrentPortalItem()->getProjectRoomLinkStatus() || sizeof($roomData['community_rooms']) > 0) {
                $roomObject->setCommunityListByID(array_values($roomData['community_rooms']));
            }
        } elseif ($roomObject instanceof cs_community_item) {
            if (isset($roomData['assignment_restricted'])) {
                if ($roomData['assignment_restricted']) {
                    $roomObject->setAssignmentOnlyOpenForRoomMembers();
                } else {
                    $roomObject->setAssignmentOpenForAnybody();
                }
            }
            if (isset($roomData['open_for_guest'])) {
                if ($roomData['open_for_guest']) {
                    $roomObject->setOpenForGuests();
                } else {
                    $roomObject->setClosedForGuests();
                }
            }
            if (isset($roomData['material_open_for_guest'])) {
                if ($roomData['material_open_for_guest']) {
                    $roomObject->setMaterialOpenForGuests();
                } else {
                    $roomObject->setMaterialClosedForGuests();
                }
            }
        }

        // check member
        if (isset($roomData['access_check'])) {
            switch ($roomData['access_check']) {
                case 'never':
                    $this->userService->grantAccessToAllPendingApplications();
                    $roomObject->setCheckNewMemberNever();
                    break;
                case 'always':
                    $roomObject->setCheckNewMemberAlways();
                    break;
                case 'withcode':
                    $roomObject->setCheckNewMemberWithCode();
                    $roomObject->setCheckNewMemberCode($roomData['access_code']);
                    break;
            }
        }

        // time context
        if (isset($roomData['time_pulses'])) {
            if (in_array('cont', $roomData['time_pulses'])) {
                $roomObject->setContinuous();
                $roomObject->setTimeListByID([]);
            } else {
                $roomObject->setNotContinuous();
                $roomObject->setTimeListByID($roomData['time_pulses']);
            }
        }

        return $roomObject;
    }
}
