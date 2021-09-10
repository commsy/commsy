<?php
namespace App\Tests;

use DateTimeImmutable;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class UnitTester extends \Codeception\Actor
{
    use _generated\UnitTesterActions;

   /**
    * Define custom actions here
    */
    public function createPortal($title, \cs_user_item $creator)
    {
        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $this->grabService('commsy_legacy.environment')->getEnvironment();

        $portalManager = $legacyEnvironment->getPortalManager();
        $this->assertInstanceOf(\cs_portal_manager::class, $portalManager);

        /** @var \cs_portal_item $portalItem */
        $portalItem = $portalManager->getNewItem();
        $this->assertInstanceOf(\cs_portal_item::class, $portalItem);

        $portalItem->setTitle($title);

        $now = new DateTimeImmutable();

        $portalItem->setCreatorItem($creator);
        $portalItem->setCreationDate($now->format('Y-m-d H:i:s'));
        $portalItem->setModificatorItem($creator);
        $portalItem->setModificationDate($now->format('Y-m-d H:i:s'));
        $portalItem->open();

        $portalItem->save();
        $this->seeInDatabase('commsy.portal', ['title' => $title]);

        $authSourceManager = $legacyEnvironment->getAuthSourceManager();
        $this->assertInstanceOf(\cs_auth_source_manager::class, $authSourceManager);

        /** @var \cs_auth_source_item $authSourceItem */
        $authSourceItem = $authSourceManager->getNewItem();
        $this->assertInstanceOf(\cs_auth_source_item::class, $authSourceItem);

        $authSourceItem->setContextID($portalItem->getItemID());
        $authSourceItem->setTitle('CommSy');
        $authSourceItem->setCommSyDefault();
        $authSourceItem->setAllowAddAccount();
        $authSourceItem->setAllowChangeUserID();
        $authSourceItem->setAllowDeleteAccount();
        $authSourceItem->setAllowChangeUserData();
        $authSourceItem->setAllowChangePassword();
        $authSourceItem->setShow();
        $authSourceItem->setModificatorItem($creator);

        $authSourceItem->save();
        $this->seeInDatabase('commsy.auth_source', ['context_id' => $portalItem->getItemID(), 'title' => 'CommSy']);

        $portalItem->setAuthDefault($authSourceItem->getItemID());
        $portalItem->save();

        return $portalItem;
    }

    public function createPortalUser($userId, $firstname, $lastname, $email, $password, \cs_portal_item $portal)
    {
        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $this->grabService('commsy_legacy.environment')->getEnvironment();

        /** @var \cs_authentication $authentication */
        $authentication = $legacyEnvironment->getAuthenticationObject();
        $this->assertInstanceOf(\cs_authentication::class, $authentication);

        /** @var \cs_auth_item $newAuthentication */
        $newAuthentication = $authentication->getNewItem();
        $this->assertInstanceOf(\cs_auth_item::class, $newAuthentication);

        $newAuthentication->setUserID($userId);
        $newAuthentication->setPassword($password);
        $newAuthentication->setFirstname($firstname);
        $newAuthentication->setLastname($lastname);
        $newAuthentication->setLanguage('de');
        $newAuthentication->setEmail($email);
        $newAuthentication->setPortalID($portal->getItemId());

        $newAuthentication->setAuthSourceID($portal->getAuthDefault());

        $authentication->save($newAuthentication);
        $this->seeInDatabase('commsy.auth', ['commsy_id' => $portal->getitemId(), 'user_id' => $userId]);

        /** @var \cs_user_item $portalUser */
        $portalUser = $authentication->getUserItem();
        $portalUser->setAGBAcceptanceDate(new DateTimeImmutable());
        $portalUser->makeUser();
        $portalUser->save();
        $this->seeInDatabase('commsy.user', ['context_id' => $portal->getItemId(), 'user_id' => $userId]);

        return $portalUser;
    }

    public function createProjectRoom($title, \cs_user_item $creator, \cs_portal_item $portal)
    {
        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $this->grabService('commsy_legacy.environment')->getEnvironment();

        $projectRoomManager = $legacyEnvironment->getProjectManager();
        $this->assertInstanceOf(\cs_project_manager::class, $projectRoomManager);

        /** @var \cs_project_item $projectRoom */
        $projectRoom = $projectRoomManager->getNewItem();
        $this->assertInstanceOf(\cs_project_item::class, $projectRoom);

        $now = new DateTimeImmutable();

        $projectRoom->setTitle($title);
        $projectRoom->setCreatorItem($creator);
        $projectRoom->setCreationDate($now->format('Y-m-d H:i:s'));
        $projectRoom->setModificatorItem($creator);
        $projectRoom->setModificationDate($now->format('Y-m-d H:i:s'));
        $projectRoom->setContextID($portal->getItemId());
        $projectRoom->open();

        $numItemsBeforeSave = $this->grabNumRecords('commsy.items', ['type' => 'project']);
        $projectRoom->save();
        $this->seeNumRecords($numItemsBeforeSave + 1, 'commsy.items', ['type' => 'project']);

        $this->seeInDatabase('commsy.room', ['type' => 'project', 'title' => $title]);

        return $projectRoom;
    }

    public function createCommunityRoom($title, \cs_user_item $creator, \cs_portal_item $portal)
    {
        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $this->grabService('commsy_legacy.environment')->getEnvironment();

        $communityRoomManager = $legacyEnvironment->getCommunityManager();
        $this->assertInstanceOf(\cs_community_manager::class, $communityRoomManager);

        /** @var \cs_project_item $communityRoom */
        $communityRoom = $communityRoomManager->getNewItem();
        $this->assertInstanceOf(\cs_community_item::class, $communityRoom);

        $now = new DateTimeImmutable();

        $communityRoom->setTitle($title);
        $communityRoom->setCreatorItem($creator);
        $communityRoom->setCreationDate($now->format('Y-m-d H:i:s'));
        $communityRoom->setModificatorItem($creator);
        $communityRoom->setModificationDate($now->format('Y-m-d H:i:s'));
        $communityRoom->setContextID($portal->getItemId());
        $communityRoom->open();

        $numItemsBeforeSave = $this->grabNumRecords('commsy.items', ['type' => 'community']);
        $communityRoom->save();
        $this->seeNumRecords($numItemsBeforeSave + 1, 'commsy.items', ['type' => 'community']);

        $this->seeInDatabase('commsy.room', ['type' => 'community', 'title' => $title]);

        return $communityRoom;
    }
}
