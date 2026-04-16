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
use App\Entity\Portal;
use App\Services\LegacyEnvironment;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

#[WithStory(AccountStory::class)]
class TouControllerTest extends AbstractApplicationTestCase
{
    private Account $account;

    public function setUp(): void
    {
        parent::setUp();

        $this->account = AccountStory::get('account');
        $this->loginAsUser(
            $this->account->getContextId(),
            $this->account->getUsername(),
            $this->account->getPlainPassword()
        );
    }

    public function testPortalTermsReturns404WhenAGBDisabled(): void
    {
        $portalId = $this->account->getContextId();

        $this->client->request('GET', "/portal/{$portalId}/terms");

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPortalTermsRendersFormWhenEnabled(): void
    {
        $this->enablePortalAGB();

        $portalId = $this->account->getContextId();
        $this->client->request('GET', "/portal/{$portalId}/terms");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testRoomTermsRendersForm(): void
    {
        $roomId = $this->createRoom($this->account->getContextId(), 'TOU-Raum');
        $this->setupRoomAGB($roomId);

        $this->client->request('GET', "/room/{$roomId}/terms");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    private function enablePortalAGB(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        /** @var Portal $portal */
        $portal = $em->find(Portal::class, $this->account->getPortal()->getId());
        $portal->setAGBEnabled(true);
        // ensure the form is rendered: portalUser.AGBAcceptanceDate (null) < AGBChangeDate
        $portal->setAGBChangeDate(new DateTime('-1 day'));
        $em->flush();
    }

    private function setupRoomAGB(int $roomId): void
    {
        /** @var LegacyEnvironment $legacyEnvironment */
        $legacyEnvironment = self::getContainer()->get(LegacyEnvironment::class);
        $env = $legacyEnvironment->getEnvironment();
        $roomItem = $env->getRoomManager()->getItem($roomId);
        $roomItem->setAGBTextArray(['DE' => 'Test-AGB', 'EN' => 'Test terms']);
        $roomItem->save();
    }
}
