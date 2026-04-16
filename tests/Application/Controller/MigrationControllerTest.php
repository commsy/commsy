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
class MigrationControllerTest extends AbstractApplicationTestCase
{
    public function testPasswordMigrationFormIsRendered(): void
    {
        $this->client->request('GET', '/migration/password');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testPasswordMigrationSubmitValidPasswordRedirectsToLogout(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $currentPassword = $account->getPlainPassword();
        $this->loginAsUser($account->getContextId(), $account->getUsername(), $currentPassword);

        $crawler = $this->client->request('GET', '/migration/password');
        $this->assertResponseIsSuccessful();

        // Strong password: lowercase, uppercase, digit, special char, >= 8 chars,
        // and different from the current one.
        $newPassword = 'Str0ng!P4ss-'.bin2hex(random_bytes(4));

        $form = $crawler->selectButton('password_migration[save]')->form();
        $form['password_migration[currentPassword]'] = $currentPassword;
        $form['password_migration[password]'] = $newPassword;
        $form['password_migration[passwordConfirm]'] = $newPassword;
        $this->client->submit($form);

        $this->assertResponseRedirects('/logout');
    }

    public function testPasswordMigrationSubmitMismatchShowsErrors(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $currentPassword = $account->getPlainPassword();
        $this->loginAsUser($account->getContextId(), $account->getUsername(), $currentPassword);

        $crawler = $this->client->request('GET', '/migration/password');

        $form = $crawler->selectButton('password_migration[save]')->form();
        $form['password_migration[currentPassword]'] = $currentPassword;
        $form['password_migration[password]'] = 'Str0ng!P4ssword';
        $form['password_migration[passwordConfirm]'] = 'Different!P4ssword';
        $this->client->submit($form);

        // Symfony re-renders the form on validation failure with 422
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('form');
    }
}
