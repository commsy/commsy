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

namespace Tests\Application\Controller;

use App\Entity\Account;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class FileControllerTest extends AbstractApplicationTestCase
{
    private Account $account;

    public function setUp(): void
    {
        parent::setUp();
        $this->account = AccountStory::get('account');
    }

    public function testThemeBackgroundReturnsImageForExistingTheme(): void
    {
        $this->client->request('GET', '/theme/default/background');

        $this->assertResponseIsSuccessful();
        $this->assertStringStartsWith(
            'image/',
            $this->client->getResponse()->headers->get('Content-Type') ?? ''
        );
    }

    public function testThemeBackgroundReturns404ForMissingTheme(): void
    {
        $this->client->request('GET', '/theme/nonexistent-theme-xyz/background');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testServerLogoReturns404WhenNoLogoSet(): void
    {
        $this->client->request('GET', '/logo/server');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPortalLogoReturns404WhenNoLogoSet(): void
    {
        $portalId = $this->account->getPortal()->getId();
        $this->client->request('GET', "/logo/portal/{$portalId}");

        $this->assertResponseStatusCodeSame(404);
    }

    public function testRoomLogoReturns404WhenNoLogoFile(): void
    {
        $this->loginAsUser(
            $this->account->getPortal()->getId(),
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );
        $roomId = $this->createRoom($this->account->getPortal()->getId(), 'FileCtrl-Raum');

        $this->client->request('GET', "/room/{$roomId}/logo");

        $this->assertResponseStatusCodeSame(404);
    }

    public function testRoomBackgroundThemeServesDefaultImage(): void
    {
        $this->loginAsUser(
            $this->account->getPortal()->getId(),
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );
        $roomId = $this->createRoom($this->account->getPortal()->getId(), 'FileCtrl-Raum-BG');

        $this->client->request('GET', "/room/{$roomId}/theme/background/");

        $this->assertResponseIsSuccessful();
        $this->assertStringStartsWith(
            'image/',
            $this->client->getResponse()->headers->get('Content-Type') ?? ''
        );
    }

    public function testRoomBackgroundCustomFallsBackToPlaceholder(): void
    {
        $this->loginAsUser(
            $this->account->getPortal()->getId(),
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );
        $roomId = $this->createRoom($this->account->getPortal()->getId(), 'FileCtrl-Raum-BG2');

        // room has no custom background → controller falls back to
        // themes/customBgPlaceholder.png
        $this->client->request('GET', "/room/{$roomId}/custom/background/");

        $this->assertResponseIsSuccessful();
    }

    public function testFileTempRouteRejectsAnonymousAccess(): void
    {
        $portalId = $this->account->getPortal()->getId();
        $this->client->request('GET', "/file_temp/user/{$portalId}");

        // ROLE_USER required → AccessDeniedHandler redirects (302) or 403
        $status = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(
            $status === 302 || $status === 403 || $status === 401,
            "expected redirect or forbidden, got {$status}"
        );
    }
}
