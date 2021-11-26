<?php

namespace App\Controller;

use App\Entity\AuthSource;
use App\Entity\AuthSourceShibboleth;
use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\User;
use App\Model\API\Room as RoomAPI;
use App\Repository\AuthSourceRepository;
use App\Repository\PortalRepository;
use App\Repository\ServerRepository;
use App\Services\LegacyEnvironment;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class RESTController extends AbstractFOSRestController
{
    /**
     * Get server announcement
     *
     * @Rest\Get("/api/v2/server/announcement")
     * @OA\Response(
     *     response="200",
     *     description="Return server announcement",
     *     @OA\JsonContent(
     *         @OA\Property(property="enabled", type="boolean"),
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="severity", type="string"),
     *         @OA\Property(property="text", type="string")
     *     )
     * )
     * @OA\Tag(name="Server")
     * @Security(name="bearerAuth")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api"}
     * )
     */
    public function serverAnnouncement(ServerRepository $serverRepository)
    {
        $server = $serverRepository->getServer();

        return [
            'enabled' => $server->hasAnnouncementEnabled(),
            'title' => $server->getAnnouncementTitle(),
            'severity' => $server->getAnnouncementSeverity(),
            'text' => $server->getAnnouncementText(),
        ];
    }

    /**
     * List portals
     *
     * Top level portals.
     *
     * @Rest\Get("/api/v2/portal/list")
     * @OA\Response(
     *     response="200",
     *     description="Return a list of portals",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Portal::class, groups={"api"}))
     *     )
     * )
     * @OA\Tag(name="Portals")
     * @Security(name="bearerAuth")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api"}
     * )
     */
    public function portalList(PortalRepository $portalRepository)
    {
        return $portalRepository->findActivePortals();
    }

    /**
     * Get a single portal
     *
     * @Rest\Get("/api/v2/portal/{id}")
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Response(
     *     response="200",
     *     description="Return a single portal",
     *     @OA\JsonContent(
     *         ref=@Model(type=Portal::class, groups={"api"})
     *     )
     * )
     * @OA\Tag(name="Portals")
     * @Security(name="bearerAuth")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api"}
     * )
     */
    public function portal(PortalRepository $portalRepository, int $id)
    {
        return $portalRepository->find($id);
    }

    /**
     * Get portal announcement
     *
     * @Rest\Get("/api/v2/portal/{id}/announcement")
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Response(
     *     response="200",
     *     description="Return portal announcement",
     *     @OA\JsonContent(
     *         @OA\Property(property="enabled", type="boolean"),
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="severity", type="string"),
     *         @OA\Property(property="text", type="string")
     *     )
     * )
     * @OA\Tag(name="Portals")
     * @Security(name="bearerAuth")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api"}
     * )
     */
    public function portalAnnouncement(PortalRepository $portalRepository, int $id)
    {
        $portal = $portalRepository->find($id);

        return [
            'enabled' => $portal->hasAnnouncementEnabled(),
            'title' => $portal->getAnnouncementTitle(),
            'severity' => $portal->getAnnouncementSeverity(),
            'text' => $portal->getAnnouncementText(),
        ];
    }

    /**
     * List auth sources
     *
     * Auth sources for a given portal.
     *
     * @Rest\Get("/api/v2/portal/{id}/auth")
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Response(
     *     response="200",
     *     description="Return a list of auth sources",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=AuthSource::class, groups={"api"}))
     *     )
     * )
     * @OA\Tag(name="Portals")
     * @Security(name="bearerAuth")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api"}
     * )
     */
    public function authSourceList(AuthSourceRepository $authSourceRepository, int $id)
    {
        return $authSourceRepository->findByPortal($id);
    }

    /**
     * Get a single auth source
     *
     * @Rest\Get("/api/v2/auth_source/{id}")
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Response(
     *     response="200",
     *     description="Return a single auth source",
     *     @OA\JsonContent(
     *         ref=@Model(type=AuthSource::class, groups={"api"})
     *     )
     * )
     * @OA\Tag(name="Authentication Sources")
     * @Security(name="bearerAuth")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api"}
     * )
     */
    public function authSource(AuthSourceRepository $authSourceRepository, int $id)
    {
        return $authSourceRepository->find($id);
    }

    /**
     * Get a single auth source login url
     *
     * @Rest\Get("/api/v2/auth_source/{id}/login_url")
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Response(
     *     response="200",
     *     description="Return a direct login url",
     *     @OA\JsonContent(
     *         @OA\Property(property="url", type="string")
     *     )
     * )
     * @OA\Tag(name="Authentication Sources")
     * @Security(name="bearerAuth")
     * @View(
     *     statusCode=200,
     *     serializerGroups={"api"}
     * )
     */
    public function authSourceDirectLoginUrl(
        AuthSourceRepository $authSourceRepository,
        UrlGeneratorInterface $urlGenerator,
        int $id
    ) {
        $authSource = $authSourceRepository->find($id);

        // shibboleth is the only authentication source supporting a direct login for now
        if ($authSource instanceof AuthSourceShibboleth) {
            return [
                'url' => $urlGenerator->generate('app_shibboleth_authshibbolethinit', [
                    'portalId' => $authSource->getPortal()->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        return ['url' => null];
    }

    /**
     * List rooms
     *
     * Rooms in a portal, either of type project or community.
     *
     * @Rest\Get("/api/v2/portal/{id}/rooms")
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Response(
     *     response="200",
     *     description="Return a list of rooms",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Room::class, groups={"api_read"}))
     *     )
     * )
     * @OA\Tag(name="Rooms")
     * @Security(name="bearerAuth")
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
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Response(
     *     response="200",
     *     description="Return a list of users",
     *     @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=User::class, groups={"api_read"}))
     *     )
     * )
     * @OA\Tag(name="Users")
     * @Security(name="bearerAuth")
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
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\RequestBody(
     *     @OA\JsonContent(ref=@Model(type=RoomAPI::class, groups={"api_write"}))
     * )
     * @OA\Response(
     *     response="201",
     *     description="The created room",
     *     @OA\JsonContent(ref=@Model(type=Room::class, groups={"api_read"}))
     * )
     * @OA\Tag(name="Rooms")
     * @Security(name="bearerAuth")
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
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Response(
     *     response="204",
     *     description="No content"
     * )
     * @OA\Tag(name="Rooms")
     * @Security(name="bearerAuth")
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
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Parameter(
     *     name="authSourceId",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Parameter(
     *     name="username",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string")
     * )
     * @OA\Response(
     *     response="204",
     *     description="No content"
     * )
     * @OA\Tag(name="Rooms")
     * @Security(name="bearerAuth")
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

            $newLegacyUser->setAGBAcceptanceDate(new DateTimeImmutable());

            $newLegacyUser->save();
            $newLegacyUser->setCreatorID2ItemID();
        }
    }
}