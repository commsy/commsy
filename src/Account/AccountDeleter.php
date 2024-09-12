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

namespace App\Account;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\SavedSearch;
use App\Entity\User;
use App\Event\AccountDeletedEvent;
use App\Repository\HashRepository;
use App\Services\LegacyEnvironment;
use App\User\UserListBuilder;
use App\Utils\UserService;
use cs_environment;
use cs_room_item;
use cs_task_item;
use cs_user_item;
use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use FOS\ElasticaBundle\Persister\ObjectPersister;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class AccountDeleter
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        private readonly UserService $userService,
        private readonly UserListBuilder $userListBuilder,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AccountManager $accountManager,
        #[Autowire(service: 'app.elastica.object_persister.commsy_user')]
        private readonly ObjectPersister $objectPersister,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function deleteAccount(Account $account): void
    {
        // Dispatch a delete event which will handle more related actions linke deleting saved searches
        $this->eventDispatcher->dispatch(new AccountDeletedEvent($account));

        $portalUser = $this->userService->getPortalUser($account);

        $privateRoomManager = $this->legacyEnvironment->getPrivateRoomManager();
        $privateRoom = $privateRoomManager->getRelatedOwnRoomForUser($portalUser, $account->getContextId());
        $privateRoom?->delete();

        $userList = $this->userListBuilder
            ->fromAccount($account)
            ->withProjectRoomUser()
            ->withCommunityRoomUser()
            ->withUserRoomUser()
            ->withPrivateRoomUser()
            ->getList();

        $users = iterator_to_array($userList);
        array_walk($users, fn(cs_user_item $user) => $this->deleteLegacyProfile($user));

        $this->entityManager->remove($account);
        $this->entityManager->flush();

        $portalUser->delete();
    }

    public function deleteLegacyProfile(cs_user_item $user): void
    {
        // delete associated tasks
        $taskManager = $this->legacyEnvironment->getTaskManager();
        $tasks = iterator_to_array($taskManager->getTaskListForItem($user));
        array_walk($tasks, fn (cs_task_item $task) => $task->delete());

        // delete any associated user room
        $user->getLinkedUserroomItem()?->delete();

        if ($user->isContact()) {
            $user->makeNoContactPerson();
            $room = $user->getContextItem();
            if ($room instanceof cs_room_item) {
                $room->renewContactPersonString();
            }
        }

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['itemId' => $user->getItemID()]);
        $this->deleteProfile($user);

        // legacy item deletion
        $user->delete();
    }

    private function deleteProfile(User $user): void
    {
        // If the user is member of a project room, delete all related group rooms
        $roomRepository = $this->entityManager->getRepository(Room::class);
        $room = $roomRepository->findOneBy(['itemId' => $user->getContextId()]);
        if ($room !== null && $room->getType() === 'project') {
            $account = $this->accountManager->getAccountFromUser($user);
            $groupRoomUsers = $this->userListBuilder
                ->fromAccount($account)
                ->withGroupRoomUser($room->getItemId())
                ->getList();

            array_walk($groupRoomUsers, fn(cs_user_item $user) => $this->deleteLegacyProfile($user));
        }

        // Delete hash values
        $hashRepository = $this->entityManager->getRepository(HashRepository::class);
        $hash = $hashRepository->findByUserId($user->getItemId());
        if (!empty($hash)) {
            $hashRepository->deleteHash($hash, true);
        }

        // Soft-delete user profile in user table
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();
        $deleterId = $currentUser->getItemId() !== 0 ? $currentUser->getItemId() : 0;
        $deletionDate = new DateTime();
        $user->setDeleterId($deleterId);
        $user->setDeletionDate($deletionDate);
        $this->entityManager->persist($user);

        // Soft-delete user profile in items table
        $conn = $this->entityManager->getConnection();
        $stmt = $conn->prepare('
            UPDATE items SET deletion_date = :deletionDate, deleter_id = :deleterId WHERE item_id = :itemId
        ');

        try {
            $stmt->bindValue('deletionDate', $deletionDate, 'datetime');
            $stmt->bindValue('deleterId', $deleterId);
            $stmt->bindValue('itemId', $user->getItemId());

            $stmt->executeStatement();
        } catch (Exception $e) {
            throw new Exception();
        }

        $this->entityManager->flush();

        // delete all related items
        $this->deleteAllUserEntries($user);

        // Elastic
        $this->objectPersister->deleteOne($user);
    }

    private function deleteAllUserEntries(User $user): void
    {
        // datenschutz: overwrite or not (03.09.2012 IJ)
        $overwrite = true;
        global $symfonyContainer;
        $disable_overwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');
        if (!empty($disable_overwrite) and 'TRUE' === $disable_overwrite) {
            $overwrite = false;
        }

        if ($overwrite) {
            $announcement_manager = $this->legacyEnvironment->getAnnouncementManager();
            $dates_manager = $this->legacyEnvironment->getDatesManager();
            $discussion_manager = $this->legacyEnvironment->getDiscussionManager();
            $discarticle_manager = $this->legacyEnvironment->getDiscussionarticlesManager();
            $material_manager = $this->legacyEnvironment->getMaterialManager();
            $section_manager = $this->legacyEnvironment->getSectionManager();
            $annotation_manager = $this->legacyEnvironment->getAnnotationManager();
            $label_manager = $this->legacyEnvironment->getLabelManager();
            $tag_manager = $this->legacyEnvironment->getTagManager();
            $todo_manager = $this->legacyEnvironment->getTodosManager();
            $step_manager = $this->legacyEnvironment->getStepManager();

            // replace users entries with the standard message for deleted entries
            $announcement_manager->deleteAnnouncementsofUser($user->getItemId());
            $dates_manager->deleteDatesOfUser($user->getItemId());
            $discussion_manager->deleteDiscussionsOfUser($user->getItemId());
            $discarticle_manager->deleteDiscarticlesOfUser($user->getItemId());
            $material_manager->deleteMaterialsOfUser($user->getItemId());
            $section_manager->deleteSectionsOfUser($user->getItemId());
            $annotation_manager->deleteAnnotationsOfUser($user->getItemId());
            $todo_manager->deleteTodosOfUser($user->getItemId());
            $step_manager->deleteStepsOfUser($user->getItemId());

            // NOTE: we don't replace hashtags (aka buzzwords) and categories (aka tags) with the standard message for
            // deleted entries since these are structural elements benefitting all room users, and which have no direct
            // association in the UI to the user who created them.
            // However note that, even with these lines uncommented, buzzwords currently won't get overwritten in the UI
            // if the server option `security.privacy_disable_overwriting` (in parameters.yml) is set to `flag`.
//          $label_manager->deleteLabelsOfUser($this->getItemID());
//          $tag_manager->deleteTagsOfUser($this->getItemID());
        }
    }
}
