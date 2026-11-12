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

/**
 * The editor no longer offers the CommSy help on the old text markup: part of
 * it is not rendered any more, the rest the editor does itself. Entries that
 * still carry such markup are rendered as before, see MarkupExtensionTest.
 */
#[WithStory(AccountStory::class)]
final class EditorWithoutMarkupHelpTest extends AbstractApplicationTestCase
{
    public function testTheFullEditorOffersNoMarkupHelp(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $portalId = $account->getContextId();
        $this->loginAsUser($portalId, $account->getUsername(), $account->getPlainPassword());
        $roomId = $this->createRoom($portalId, 'Testraum');

        $this->client->request('GET', "/room/{$portalId}/item/{$roomId}/editdescription");

        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('commsyimage', $html, 'the full editor configuration is rendered');
        self::assertStringNotContainsStringIgnoringCase('commsyhelp', $html);
    }
}
