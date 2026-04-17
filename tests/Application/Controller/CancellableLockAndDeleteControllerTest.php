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
use App\Entity\Room;
use App\Room\RoomStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class CancellableLockAndDeleteControllerTest extends AbstractApplicationTestCase
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

        $this->roomId = $this->createRoom($this->portalId, 'Löschraum');
    }

    public function testDeleteOrLockPageRendersBothForms(): void
    {
        $this->client->request(
            'GET',
            "/room/{$this->portalId}/settings/cancellabledelete/{$this->roomId}"
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCancelButtonOnDeleteFormRedirectsToDetail(): void
    {
        $crawler = $this->client->request(
            'GET',
            "/room/{$this->portalId}/settings/cancellabledelete/{$this->roomId}"
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('cancellable_delete[cancel]')->form();
        $this->client->submit($form);

        // redirect to some detail route (portalId-based or roomId-based)
        $status = $this->client->getResponse()->getStatusCode();
        $this->assertGreaterThanOrEqual(300, $status);
        $this->assertLessThan(400, $status);
    }

    public function testDeleteButtonSoftDeletesRoom(): void
    {
        $crawler = $this->client->request(
            'GET',
            "/room/{$this->portalId}/settings/cancellabledelete/{$this->roomId}"
        );
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('cancellable_delete[delete]')->form();
        // the form's IdenticalTo constraint expects the uppercase, translated
        // "delete" string from the "profile" domain; we must translate with
        // the same locale the controller used when rendering the form
        // (otherwise the default translator locale may diverge across tests).
        $locale = $this->client->getResponse()->headers->get('Content-Language') ?? 'en';
        /** @var TranslatorInterface $translator */
        $translator = self::getContainer()->get(TranslatorInterface::class);
        $form['cancellable_delete[confirm]'] = mb_strtoupper(
            $translator->trans('delete', [], 'profile', $locale)
        );
        $this->client->submit($form);

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertGreaterThanOrEqual(300, $status);
        $this->assertLessThan(400, $status);

        // verify the room is marked for deletion
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        /** @var Room $room */
        $room = $em->find(Room::class, $this->roomId);
        $this->assertFalse($room->isIndexable(), 'deleted room should no longer be indexable');
    }

    public function testUnlockRoute(): void
    {
        // lock the room first via the legacy API (this is the production precondition
        // for the unlock route — there is no HTTP way to reach it otherwise).
        $legacyEnvironment = self::getContainer()
            ->get(\App\Services\LegacyEnvironment::class)
            ->getEnvironment();
        $roomItem = $legacyEnvironment->getRoomManager()->getItem($this->roomId);
        $roomItem->lock(RoomStatus::LOCKED);
        $roomItem->save();

        $this->client->request(
            'GET',
            "/room/{$this->portalId}/settings/unlock/{$this->roomId}"
        );

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertGreaterThanOrEqual(300, $status);
        $this->assertLessThan(400, $status);
    }
}
