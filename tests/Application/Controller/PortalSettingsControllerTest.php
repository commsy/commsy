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
use Tests\Story\PortalStory;
use Zenstruck\Foundry\Attribute\WithStory;

class PortalSettingsControllerTest extends AbstractApplicationTestCase
{
    #[WithStory(PortalStory::class)]
    public function testRoomTermsTemplates(): void
    {
        $portal = PortalStory::get('portal');

        $this->loginAsRoot();

        $this->client->request('GET', "/portal/{$portal->getId()}/settings/contents/roomTermsTemplates");
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h3', 'Vorlagen Nutzungsbedingungen');
    }

    #[WithStory(PortalStory::class)]
    public function testLicenses(): void
    {
        $portal = PortalStory::get('portal');

        $this->loginAsRoot();

        $this->client->request('GET', "/portal/{$portal->getId()}/settings/licenses");
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h3', 'Neue Lizenz anlegen');

        // Create a new license
        $this->client->submitForm('Neue Lizenz anlegen', [
            'license[title]' => 'Lizenz ABC',
            'license[content]' => 'content',
        ]);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('div', 'Lizenz ABC');

        // Edit
        $this->client->clickLink('Bearbeiten');
        $this->client->submitForm('Lizenz speichern', [
            'license[title]' => 'Lizenz DEF',
            'license[content]' => 'content2',
        ]);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('div', 'Lizenz DEF');
    }

    public function testAccountIndex(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = $account->getContextId();

        $this->loginAsRoot();

        $this->client->request('GET', "/portal/{$portalId}/settings/accountindex");
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('a', "{$account->getFirstname()} {$account->getLastname()}");
        $this->assertAnySelectorTextContains('a', $account->getEmail());
    }

    public function testAccountIndexDetail(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = $account->getContextId();

        $this->loginAsRoot();

        $this->client->request('GET', "/portal/{$portalId}/settings/accountindex");
        $this->client->clickLink("{$account->getFirstname()} {$account->getLastname()}");

        $this->assertResponseIsSuccessful();
    }

    public function testAccountIndexDetailEdit(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = $account->getContextId();

        $this->loginAsRoot();

        // Navigate to detail page
        $this->client->request('GET', "/portal/{$portalId}/settings/accountindex");
        $crawler = $this->client->clickLink("{$account->getFirstname()} {$account->getLastname()}");
        $this->assertResponseIsSuccessful();

        // Click edit
        $editLink = $crawler->filter('ul.uk-dropdown-nav a')->first();
        $this->client->clickLink($editLink->text());

        $this->assertResponseIsSuccessful();

        $this->client->submitForm('account_index_detail_edit_save', [
            'account_index_detail_edit[firstName]' => 'Max',
            'account_index_detail_edit[lastName]' => 'Mustermann',
            'account_index_detail_edit[email]' => 'max.mustermann@example.com',
        ]);
        $this->assertResponseIsSuccessful();

        $this->assertSame('Max', $account->getFirstname());
        $this->assertSame('Mustermann', $account->getLastname());
        $this->assertSame('max.mustermann@example.com', $account->getEmail());
    }

    #[WithStory(PortalStory::class)]
    public function testTimePulses(): void
    {
        $portal = PortalStory::get('portal');

        $this->loginAsRoot();

        $this->client->request('GET', "/portal/{$portal->getId()}/settings/timepulses");
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h3', 'Zeittakte');

        // Create a new time pulse
        $this->client->submitForm('Zeittakt hinzufügen', [
            'time_pulse_template[titleGerman]' => 'Test Zeittakt DE',
            'time_pulse_template[titleEnglish]' => 'Test Time Pulse EN',
            'time_pulse_template[startDay]' => 1,
            'time_pulse_template[startMonth]' => 1,
            'time_pulse_template[endDay]' => 31,
            'time_pulse_template[endMonth]' => 12,
        ]);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('a', 'Test Zeittakt DE');

        // Edit
        $this->client->clickLink('Test Zeittakt DE');
        $this->assertResponseIsSuccessful();
        $this->client->submitForm('Zeittakt speichern', [
            'time_pulse_template[titleGerman]' => 'Geänderter Zeittakt',
        ]);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('a', 'Geänderter Zeittakt');

        // Delete
        $this->client->clickLink('Geänderter Zeittakt');
        $this->client->submitForm('Zeittakt löschen');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextNotContains('a', 'Geänderter Zeittakt');

        // Test options form
        $this->client->submitForm('Speichern', [
            'time_pulses[showTimePulses]' => '1',
            'time_pulses[timePulseNameGerman]' => 'Semester',
            'time_pulses[timePulseNameEnglish]' => 'Semester',
            'time_pulses[numberOfFutureTimePulses]' => '2',
        ], 'POST');
        $this->assertResponseIsSuccessful();
    }
}
