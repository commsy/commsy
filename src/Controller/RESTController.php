<?php

namespace App\Controller;

use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Entity\Room;
use App\Model\API\Room as RoomAPI;
use App\Entity\User;
use App\Services\LegacyEnvironment;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class RESTController extends AbstractFOSRestController
{
    /**
     * List portals
     *
     * Top level portals.
     *
     * @Rest\Get("/api/v2/portal/list")
     * @SWG\Response(
     *     response="200",
     *     description="Return a list of portals",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Portal::class, groups={"api"}))
     *     )
     * )
     * @SWG\Tag(name="portals")
     * @Security(name="Bearer")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api"}
     * )
     */
    public function portalList(EntityManagerInterface $entityManager)
    {
        return $entityManager->getRepository(Portal::class)
            ->findActivePortals();
    }

    /**
     * List auth sources
     *
     * Auth sources for a given portal.
     *
     * @Rest\Get("/api/v2/portal/{id}/auth")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     type="integer"
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Return a list of auth sources",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=AuthSource::class, groups={"api"}))
     *     )
     * )
     * @SWG\Tag(name="portals")
     * @Security(name="Bearer")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api"}
     * )
     */
    public function authList(EntityManagerInterface $entityManager, int $id)
    {
        return $entityManager->getRepository(AuthSource::class)
            ->findByPortal($id);
    }

    /**
     * List rooms
     *
     * Rooms in a portal, either of type project or community.
     *
     * @Rest\Get("/api/v2/portal/{id}/rooms")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     type="integer"
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Return a list of rooms",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Room::class, groups={"api_read"}))
     *     )
     * )
     * @SWG\Tag(name="rooms")
     * @Security(name="Bearer")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api_read"}
     * )
     */
    public function roomList(EntityManagerInterface $entityManager, int $id)
    {
        return $entityManager->getRepository(Room::class)
            ->getMainRoomQueryBuilder($id)
            ->getQuery()
            ->getResult();
    }

    /**
     * List users
     *
     * Users in a portal.
     *
     * @Rest\Get("/api/v2/portal/{id}/users")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     type="integer"
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Return a list of users",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=User::class, groups={"api_read"}))
     *     )
     * )
     * @SWG\Tag(name="users")
     * @Security(name="Bearer")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api_read"}
     * )
     */
    public function userList(EntityManagerInterface $entityManager, int $id)
    {
        return $entityManager->getRepository(User::class)
            ->findActiveUsers($id);
    }

    /**
     * Create a room
     *
     * Creates a new project or community room
     *
     * @Rest\Post("/api/v2/portal/{id}/rooms")
     * @ParamConverter(
     *     "roomAPI",
     *     converter="fos_rest.request_body",
     *     options={"validator"={"groups"={"api_write"}}}
     * )
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     type="integer"
     * )
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(ref=@Model(type=RoomAPI::class, groups={"api_write"}))
     * )
     * @SWG\Response(
     *     response="201",
     *     description="The created room",
     *     @SWG\Schema(ref=@Model(type=Room::class, groups={"api_read"}))
     * )
     * @SWG\Tag(name="rooms")
     * @Security(name="Bearer")
     * @View(
     *     statusCode=201,
     *     serializerGroups={"api_read"}
     * )
     */
    public function roomCreate(
        int $id,
        RoomAPI $roomAPI,
        ConstraintViolationListInterface $validationErrors,
        LegacyEnvironment $legacyEnvironment,
        EntityManagerInterface $entityManager
    ) {
        if ($validationErrors->count() === 0) {
            $legacyEnvironment = $legacyEnvironment->getEnvironment();
            $legacyEnvironment->setCurrentContextID($id);

            /** @var \cs_portal_item $portal */
            $portal = $legacyEnvironment->getCurrentContextItem();

            // find creator user item
            $userManager = $legacyEnvironment->getUserManager();
            $legacyCreator = $userManager->getItemByUserIDAuthSourceID($roomAPI->getUserName(), $roomAPI->getAuthSourceId());
            if ($legacyCreator === null) {
                throw new HttpException(400, 'No user found');
            }
            $legacyEnvironment->setCurrentUserItem($legacyCreator);

            $manager = $legacyEnvironment->getManager($roomAPI->getType());
            $newLegacyRoom = $manager->getNewItem();
            $newLegacyRoom->setCreatorItem($legacyCreator);
            $newLegacyRoom->setCreationDate(date("Y-m-d H:i:s"));
            $newLegacyRoom->setModificatorItem($legacyCreator);
            $newLegacyRoom->setModificationDate($newLegacyRoom->getCreationDate());
            $newLegacyRoom->setContextID($portal->getItemID());
            $newLegacyRoom->open();
            $newLegacyRoom->setTitle($roomAPI->getTitle());
            $newLegacyRoom->setDescription($roomAPI->getDescription());

            $newLegacyRoom->save();

            return $entityManager->getRepository(Room::class)
                ->find($newLegacyRoom->getItemID());
        } else {
            throw new HttpException(400, $validationErrors);
        }
    }

    /**
     * Deleted a room
     *
     * Deleted an existing room
     *
     * @Rest\Delete("/api/v2/rooms/{id}")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     type="integer"
     * )
     * @SWG\Response(
     *     response="204",
     *     description="No content"
     * )
     * @SWG\Tag(name="rooms")
     * @Security(name="Bearer")
     * @View(
     *     statusCode=204,
     *     serializerGroups={"api_read"}
     * )
     */
    public function roomDelete(
        int $id,
        LegacyEnvironment $legacyEnvironment,
        EntityManagerInterface $entityManager
    ) {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();

        /** @var Room $room */
        $room = $entityManager->getRepository(Room::class)
            ->find($id);

        $legacyEnvironment->setCurrentContextID($room->getContextId());

        $roomManager = $legacyEnvironment->getRoomManager();
        $legacyRoom = $roomManager->getItem($id);
        $legacyRoom->delete();
    }

    /**
     * Add room member
     *
     * Adds a user to a room
     *
     * @Rest\Put("/api/v2/rooms/{id}/membership/{authSourceId}/{username}")
     * @SWG\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     type="integer"
     * )
     * @SWG\Parameter(
     *     name="authSourceId",
     *     in="path",
     *     required=true,
     *     type="integer"
     * )
     * @SWG\Parameter(
     *     name="username",
     *     in="path",
     *     required=true,
     *     type="string"
     * )
     * @SWG\Response(
     *     response="204",
     *     description="No content"
     * )
     * @SWG\Tag(name="rooms")
     * @Security(name="Bearer")
     * @View(
     *     statusCode=204,
     *     serializerGroups={"api_read"}
     * )
     */
    public function roomMembership(
        int $id,
        int $authSourceId,
        string $username,
        LegacyEnvironment $legacyEnvironment,
        EntityManagerInterface $entityManager
    ) {
        // "root" or "guest" is not allowed as username
        if (strtolower($username) === 'root' || strtolower($username) === 'guest') {
            throw new HttpException(400, 'Username not allowed');
        }

        $legacyEnvironment = $legacyEnvironment->getEnvironment();

        /** @var Room $room */
        $room = $entityManager->getRepository(Room::class)
            ->find($id);

        $legacyEnvironment->setCurrentContextID($room->getContextId());

        // find portal user item
        $userManager = $legacyEnvironment->getUserManager();
        /** @var \cs_user_item $legacyPortalUser */
        $legacyPortalUser = $userManager->getItemByUserIDAuthSourceID($username, $authSourceId);
        if ($legacyPortalUser === null) {
            throw new HttpException(400, 'No user found');
        }
        $legacyEnvironment->setCurrentUserItem($legacyPortalUser);

        $roomManager = $legacyEnvironment->getRoomManager();
        $legacyRoom = $roomManager->getItem($room->getItemId());

        $legacyUserInRoom = $legacyRoom->getUserByUserID($username, $authSourceId);
        if ($legacyUserInRoom === null) {
            /** @var \cs_user_item $newLegacyUser */
            $newLegacyUser = null;
            $picture = null;

            // TODO: try to make use of UserService->cloneUser() instead

            $privateRoomUserItem = $legacyPortalUser->getRelatedPrivateRoomUserItem();
            if ($privateRoomUserItem === null) {
                $newLegacyUser = $legacyPortalUser->cloneData();
                $picture = $legacyPortalUser->getPicture();
            } else {
                $newLegacyUser = $privateRoomUserItem->cloneData();
                $picture = $privateRoomUserItem->getPicture();
            }

            $newLegacyUser->setContextID($id);

            if ($picture) {
                $values = explode('_', $picture);
                $values[0] = 'cid' . $newLegacyUser->getContextID();
                $newPictureName = implode('_', $values);

                $discManager = $legacyEnvironment->getDiscManager();
                $discManager->copyImageFromRoomToRoom($picture, $newLegacyUser->getContextID());
                $newLegacyUser->setPicture($newPictureName);
            }

            $newLegacyUser->makeUser();

            $groupManager = $legacyEnvironment->getLabelManager();
            $groupManager->setExactNameLimit('ALL');
            $groupManager->setContextLimit($legacyRoom->getItemID());
            $groupManager->select();

            /** @var \cs_list $groupList */
            $groupList = $groupManager->get();
            if ($groupList->getCount() === 1) {
                /** @var \cs_label_item $group */
                $group = $groupList->getFirst();
                $group->setTitle('ALL');
                $newLegacyUser->setGroupByID($group->getItemID());
            }

            $newLegacyUser->setAGBAcceptance();

            $newLegacyUser->save();
            $newLegacyUser->setCreatorID2ItemID();
        }
    }
}