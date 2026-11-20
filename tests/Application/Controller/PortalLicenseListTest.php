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
use Doctrine\DBAL\Connection;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Each license in the portal configuration is one row: its name, then the
 * buttons to delete and to edit it, with UIkit 3 classes so the buttons keep
 * to the row instead of running into the next one.
 */
#[WithStory(AccountStory::class)]
final class PortalLicenseListTest extends AbstractApplicationTestCase
{
    public function testEachLicenseIsOneRowWithSmallButtons(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = $account->getContextId();
        $db = self::getContainer()->get(Connection::class);
        $db->update('user', ['status' => 3], ['context_id' => $portalId, 'user_id' => $account->getUsername()]);
        foreach (['CC BY 4.0', 'CC BY-SA 4.0'] as $position => $title) {
            $db->insert('licenses', ['context_id' => $portalId, 'title' => $title, 'content' => $title, 'position' => $position]);
        }
        $this->loginAsUser($portalId, $account->getUsername(), $account->getPlainPassword());

        $crawler = $this->client->request('GET', "/portal/{$portalId}/settings/licenses");

        self::assertResponseIsSuccessful();
        $rows = $crawler->filter('#licenseSortList > li');
        self::assertCount(2, $rows);
        $rows->each(function ($row): void {
            self::assertCount(1, $row->filter('.uk-flex button.license_delete.uk-button-small'));
            self::assertCount(1, $row->filter('.uk-flex a.uk-button-small'));
        });
        self::assertCount(0, $crawler->filter('#licenseSortList .uk-button-mini'));
    }
}
