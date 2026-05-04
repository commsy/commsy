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
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class SettingsControllerTest extends AbstractApplicationTestCase
{
    private Account $account;
    private int $portalId;
    private int $roomId;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->portalId = $this->account->getPortal()->getId();

        $this->loginAsUser(
            $this->portalId,
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );

        // The settings routes require ITEM_ENTER + MODERATOR on the room.
        // The user that just created the room is implicitly its moderator.
        $this->roomId = $this->createRoom($this->portalId, 'Settings-Raum');
    }

    public function testGeneralPageRenders(): void
    {
        $this->client->request('GET', "/room/{$this->roomId}/settings/general");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testModerationPageRenders(): void
    {
        $this->client->request('GET', "/room/{$this->roomId}/settings/moderation");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testAdditionalPageRenders(): void
    {
        $this->client->request('GET', "/room/{$this->roomId}/settings/additional");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testAppearancePageRenders(): void
    {
        $this->client->request('GET', "/room/{$this->roomId}/settings/appearance");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testExtensionsPageRenders(): void
    {
        $this->client->request('GET', "/room/{$this->roomId}/settings/extensions");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testDeleteUserRoomsPageRenders(): void
    {
        $this->client->request('GET', "/room/{$this->roomId}/settings/deleteuserrooms");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testDeletePageRenders(): void
    {
        $this->client->request('GET', "/room/{$this->roomId}/settings/delete/");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testInvitationsPageRenders(): void
    {
        $this->client->request('GET', "/room/{$this->roomId}/settings/invitations");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testDeleteSubmitWithCorrectConfirmRedirectsToListAll(): void
    {
        $crawler = $this->client->request('GET', "/room/{$this->roomId}/settings/delete/");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('delete_room[delete]')->form();
        $locale = $this->client->getResponse()->headers->get('Content-Language') ?? 'en';
        /** @var TranslatorInterface $translator */
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $form['delete_room[confirm]'] = mb_strtoupper($translator->trans('delete', [], 'profile', $locale));

        $this->client->submit($form);

        // soft-delete redirects to the all-rooms list for the portal
        $this->assertResponseRedirects("/room/{$this->portalId}/all");
    }

    public function testDeleteSubmitWithBlankConfirmShowsError(): void
    {
        $crawler = $this->client->request('GET', "/room/{$this->roomId}/settings/delete/");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('delete_room[delete]')->form();
        $form['delete_room[confirm]'] = '';
        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(422);
    }
}
