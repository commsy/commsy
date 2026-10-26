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

declare(strict_types=1);

namespace Tests\Application\Controller;

use App\Entity\Account;
use App\Entity\Portal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Wherever a project room is deleted from, the moderation of its community
 * rooms is told, where the portal asks for that.
 */
#[WithStory(AccountStory::class)]
final class ProjectRoomDeletionMailTest extends AbstractApplicationTestCase
{
    private int $portalId;
    private int $communityId;
    private int $projectId;

    public function setUp(): void
    {
        parent::setUp();

        $account = AccountStory::get('account');
        $portal = $account->getPortal();
        self::assertInstanceOf(Portal::class, $portal);
        $this->portalId = $portal->getId();
        $portal->setNotifyCommunityModForAllProjectRooms(true);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $this->loginAsUser($this->portalId, $account->getUsername(), $account->getPlainPassword());
        $this->communityId = $this->createWorkspace('community', 'Lindenhof');

        $crawler = $this->client->request('GET', "/room/{$this->communityId}/project/create");
        $form = $crawler->selectButton('project[save]')->form();
        $form['project[title]'] = 'Projekt';
        $this->client->submit($form);
        self::assertTrue(1 === preg_match("~^/room/{$this->communityId}/project/(\d+)$~", (string) $this->client->getResponse()->headers->get('Location'), $matches));
        $this->projectId = (int) $matches[1];
    }

    public function testDeletingItFromTheCommunityRoomTellsItsModeration(): void
    {
        $this->submitDeletion("/room/{$this->communityId}/project/{$this->projectId}/delete", 'delete_room');

        self::assertNotSame([], $this->communityModerationMails());
    }

    public function testDeletingItFromItsSettingsTellsTheModerationOfItsCommunityRooms(): void
    {
        $this->submitDeletion("/room/{$this->projectId}/settings/delete/", 'delete_room');

        self::assertNotSame([], $this->communityModerationMails());
    }

    public function testDeletingItFromTheRoomListTellsTheModerationOfItsCommunityRooms(): void
    {
        $this->submitDeletion("/room/{$this->portalId}/settings/cancellabledelete/{$this->projectId}", 'cancellable_delete');

        self::assertNotSame([], $this->communityModerationMails());
    }

    private function submitDeletion(string $uri, string $formName): void
    {
        $crawler = $this->client->request('GET', $uri);
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton("{$formName}[delete]")->form();
        $locale = $this->client->getResponse()->headers->get('Content-Language') ?? 'en';
        $form["{$formName}[confirm]"] = mb_strtoupper(self::getContainer()->get(TranslatorInterface::class)->trans('delete', [], 'profile', $locale));
        $this->client->submit($form);
        self::assertResponseRedirects();
    }

    /**
     * @return list<Email> the mails to the moderation of the community room
     */
    private function communityModerationMails(): array
    {
        return array_values(array_filter(
            self::getMailerMessages(),
            static fn ($mail): bool => $mail instanceof Email && 1 === preg_match('~(Raumes|workspace) (&quot;|")Lindenhof(&quot;|")~', (string) $mail->getHtmlBody()),
        ));
    }

    private function createWorkspace(string $type, string $title): int
    {
        $crawler = $this->client->request('GET', "/room/{$this->portalId}/all/create");
        $form = $crawler->selectButton('context[save]')->form();
        $form['context[title]'] = $title;
        $form['context[type_select]']->select($type);
        $this->client->submit($form);
        self::assertTrue(1 === preg_match('~^/portal/\d+/room/(\d+)~', (string) $this->client->getResponse()->headers->get('Location'), $matches));

        return (int) $matches[1];
    }
}
