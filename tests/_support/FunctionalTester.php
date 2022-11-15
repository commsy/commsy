<?php

namespace App\Tests;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\AuthSourceLocal;
use App\Entity\Portal;
use App\Facade\AccountCreatorFacade;
use App\Utils\UserService;
use Codeception\Actor;
use Codeception\Util\HttpCode;
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
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class FunctionalTester extends Actor
{
    use _generated\FunctionalTesterActions;

    public function havePortal(string $title, array $additionalParams = [], ?AuthSourceLocal $authSource = null): Portal
    {
        if (!$authSource) {
            $authSource = new AuthSourceLocal();
            $this->haveInRepository($authSource, [
                'title' => 'Lokal',
                'enabled' => true,
                'default' => true,
                'createRoom' => true,
            ]);
        }

        $portal = new Portal();
        $portal->addAuthSource($authSource);

        $params = [
            'title' => $title,
            'status' => 1,
        ];
        if (!empty($additionalParams)) {
            $params = array_merge($params, $additionalParams);
        }

        $this->haveInRepository($portal, $params);

        return $portal;
    }

    public function haveAuthSource(Portal $portal, AuthSource $authSource, string $title): void
    {
        $this->haveInRepository($authSource, [
            'title' => $title,
            'enabled' => true,
            'default' => false,
            'createRoom' => true,
        ]);

        $portal->addAuthSource($authSource);
        $this->haveInRepository($portal);
    }

    public function haveAccount(Portal $portal, string $username): Account
    {
        /** @var AuthSourceLocal $localAuthSource */
        $localAuthSource = $this->grabEntityFromRepository(AuthSourceLocal::class, [
            'portal' => $portal,
        ]);

        /** @var Account $account */
        $account = $this->make(Account::class, [
            'authSource' => $localAuthSource,
            'contextId' => $portal->getId(),
            'username' => $username,
        ]);

        /** @var AccountCreatorFacade $accountFacade */
        $accountFacade = $this->grabService(AccountCreatorFacade::class);
        $accountFacade->persistNewAccount($account);

        $this->grabEntityFromRepository(Account::class, [
            'username' => $username,
        ]);

        return $account;
    }

    public function haveProjectRoom(string $title, bool $performLogin = true, ?Portal $portal = null, ?Account $account = null)
    {
        if (!$portal) {
            $portal = $this->havePortal('Test portal');
            $this->seeInDatabase('user', ['title' => 'Test portal']);
        }

        if (!$account) {
            $account = $this->haveAccount($portal, 'user');
            $this->seeInDatabase('user', ['user_id' => 'user', 'context_id' => $portal->getId()]);
        }

        if ($performLogin && $portal && $account) {
            $this->amLoggedInAsUser($portal, $account->getUsername(), $account->getPlainPassword());
        }

        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $this->grabService('commsy_legacy.environment')->getEnvironment();

        /** @var UserService $userService */
        $userService = $this->grabService(UserService::class);

        $portalUser = $userService->getPortalUser($account);

        $projectRoomManager = $legacyEnvironment->getProjectManager();

        /** @var \cs_project_item $projectRoom */
        $projectRoom = $projectRoomManager->getNewItem();

        $now = new DateTimeImmutable();

        $projectRoom->setTitle($title);
        $projectRoom->setCreatorItem($portalUser);
        $projectRoom->setCreationDate($now->format('Y-m-d H:i:s'));
        $projectRoom->setModificatorItem($portalUser);
        $projectRoom->setModificationDate($now->format('Y-m-d H:i:s'));
        $projectRoom->setContextID($portal->getId());
        $projectRoom->open();
        $projectRoom->save();

        $this->seeInDatabase('room', ['type' => 'project', 'title' => $title]);

        $this->amOnRoute('app_room_home', [
            'roomId' => $projectRoom->getItemId(),
        ]);
        $this->seeResponseCodeIs(HttpCode::OK);

        return $projectRoom;
    }
}
