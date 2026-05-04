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

use App\Entity\Invitations;
use Doctrine\ORM\EntityManagerInterface;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Story\AccountStory;
use Tests\Story\PortalStory;
use Zenstruck\Foundry\Attribute\WithStory;

class AccountControllerTest extends AbstractApplicationTestCase
{
    #[WithStory(PortalStory::class)]
    public function testSignUp(): void
    {
        $portal = PortalStory::get('portal');
        $this->client->request('GET', "/register/{$portal->getId()}");

        $this->client->submitForm('sign_up_form[submit]', [
            'sign_up_form[firstname]' => 'Firstname',
            'sign_up_form[lastname]' => 'Lastname',
            'sign_up_form[username]' => 'username',
            'sign_up_form[email][first]' => 'some@mail.test',
            'sign_up_form[email][second]' => 'some@mail.test',
            'sign_up_form[plainPassword][first]' => 'zfCbzLm9h4$h',
            'sign_up_form[plainPassword][second]' => 'zfCbzLm9h4$h',
        ]);

        $this->assertResponseRedirects("/login/{$portal->getId()}");
    }

    #[WithStory(PortalStory::class)]
    public function testSignUpWithBadPassword(): void
    {
        $portal = PortalStory::get('portal');
        $this->client->request('GET', "/register/{$portal->getId()}");

        $this->client->submitForm('sign_up_form[submit]', [
            'sign_up_form[firstname]' => 'Firstname',
            'sign_up_form[lastname]' => 'Lastname',
            'sign_up_form[username]' => 'username',
            'sign_up_form[email][first]' => 'some@mail.test',
            'sign_up_form[email][second]' => 'some@mail.test',
            'sign_up_form[plainPassword][first]' => 'badpassword',
            'sign_up_form[plainPassword][second]' => 'badpassword',
        ]);

        $this->assertAnySelectorTextContains('li', 'Das eingegebene Passwort muss mindestens einen Großbuchstaben enthalten');
        $this->assertAnySelectorTextContains('li', 'Das eingegebene Passwort muss mindestens ein Sonderzeichen enthalten');
        $this->assertAnySelectorTextContains('li', 'Das eingegebene Passwort muss mindestens eine Zahl enthalten');
        $this->assertAnySelectorTextContains('div', 'Das Passwort muss mindestens 8 Zeichen lang sein und mindestens einen Klein- und Großbuchstaben, sowie ein Sonderzeichen und eine Zahl enthalten');
    }

    #[WithStory(PortalStory::class)]
    public function testSignUpWithUnallowedEmail(): void
    {
        $this->loginAsRoot();

        $portal = PortalStory::get('portal');
        $crawler = $this->client->request('GET', "/portal/{$portal->getId()}/settings/auth/local");

        $form = $crawler->selectButton('auth_local[save]')->form();
        $form['auth_local[enabled]']->tick();
        $form['auth_local[title]'] = 'Lokal';
        $form['auth_local[mailRegex]'] = '~.*@domain.tld~';
        $form['auth_local[addAccount]']->select('yes');
        $this->client->submit($form);

        $this->logout();

        $this->client->request('GET', "/register/{$portal->getId()}");

        $this->client->submitForm('sign_up_form[submit]', [
            'sign_up_form[firstname]' => 'Firstname',
            'sign_up_form[lastname]' => 'Lastname',
            'sign_up_form[username]' => 'username',
            'sign_up_form[email][first]' => 'some@other.tld',
            'sign_up_form[email][second]' => 'some@other.tld',
            'sign_up_form[plainPassword][first]' => 'zfCbzLm9h4$h',
            'sign_up_form[plainPassword][second]' => 'zfCbzLm9h4$h',
        ]);

        $this->assertAnySelectorTextContains('li', 'email_regex_de');
    }

    #[WithStory(AccountStory::class)]
    public function testSignUpWithInvitation(): void
    {
        $account = AccountStory::get('account');
        $plainPasword = $account->getPlainPassword();

        // Enable invitations in portal settings
        $this->loginAsRoot();
        $crawler = $this->client->request('GET', "/portal/{$account->getContextId()}/settings/auth/local");
        $form = $crawler->selectButton('auth_local[save]')->form();
        $form['auth_local[addAccount]']->select('invitation');
        $this->client->submit($form);
        $this->logout();

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $plainPasword);
        $roomId = $this->createRoom($account->getContextId(), 'Testraum');

        // Create an invitation in the room settings
        $this->client->request('GET', "/room/{$roomId}/settings/invitations");
        $this->client->submitForm('invitations_settings[send]', [
            'invitations_settings[email]' => 'asdf@some.mail',
        ]);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('label', 'asdf@some.mail');
        $this->logout();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        /** @var Invitations $invitation */
        $invitation = $entityManager->getRepository(Invitations::class)->findOneBy(['email' => 'asdf@some.mail']);

        // No token
        $this->client->request('GET', "/register/{$account->getContextId()}");
        $this->assertAnySelectorTextContains('li', 'Der Einladungslink ist nicht (mehr) gültig.');

        // Wrong token
        $this->client->request('GET', "/register/{$account->getContextId()}", [
            'token' => 'invalid',
        ]);
        $this->assertAnySelectorTextContains('li', 'Der Einladungslink ist nicht (mehr) gültig.');

        // Valid token
        $this->client->request('GET', "/register/{$account->getContextId()}", [
            'token' => $invitation->getHash(),
        ]);
        $this->assertSelectorNotExists('li');
    }

    /**
     * Tests that a room user gets automatically created for an account on login if the login request
     * contains a key (as defined in the portal) with the room's unique textual identifier (slug).
     */
    #[WithStory(AccountStory::class)]
    public function testSignUpWithSlugsCreatesRoomMember(): void
    {
        $account = AccountStory::get('account');
        $plainPasword = $account->getPlainPassword();

        // Enable auto-creation in portal settings
        $this->loginAsRoot();
        $crawler = $this->client->request('GET', "/portal/{$account->getContextId()}/settings/auth/workspacemembership");
        $form = $crawler->selectButton('auth_workspace_membership[save]')->form();
        $form['auth_workspace_membership[authMembershipEnabled]']->tick();
        $form['auth_workspace_membership[authMembershipIdentifier]'] = 'roomslugs';
        $this->client->submit($form);
        $this->logout();

        $this->loginAsUser($account->getContextId(), $account->getUsername(), $plainPasword);
        $roomId = $this->createRoom($account->getContextId(), 'Testraum');

        // Set a slug in the room settings
        $this->client->request('GET', "/room/$roomId/settings/general");
        $this->client->submitForm('general_settings[save]', [
            'general_settings[slugs]' => 'test-room',
        ]);
        $this->logout();

        // Register as new user
        $this->client->request('GET', "/register/{$account->getContextId()}");
        $this->client->submitForm('sign_up_form[submit]', [
            'sign_up_form[firstname]' => 'Firstname',
            'sign_up_form[lastname]' => 'Lastname',
            'sign_up_form[username]' => 'username2',
            'sign_up_form[email][first]' => 'another@mail.test',
            'sign_up_form[email][second]' => 'another@mail.test',
            'sign_up_form[plainPassword][first]' => 'zfCbzLm9h4$h',
            'sign_up_form[plainPassword][second]' => 'zfCbzLm9h4$h',
        ]);

        // Sign in with server parameter set
        $this->client->request('GET', "/login/{$account->getContextId()}");
        $this->client->submitForm('login_local', [
            'email' => 'username2',
            'password' => 'zfCbzLm9h4$h',
        ], 'POST', [
            'roomslugs' => 'test-room',
        ]);

        // Given the above, login should cause room user(s) for the currently logged-in account to be created
        // Check if the room can be accessed successfully for the newly logged-in account
        $this->client->request('GET', "/room/$roomId");
        $this->assertResponseIsSuccessful();
    }

    public function testAccessAccount(): void
    {
        $account = AccountStory::get('account');
        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', "/portal/{$account->getContextId()}/account/personal");
        $this->assertResponseIsSuccessful();
    }

    public function testChangePasswordPageRenders(): void
    {
        $account = AccountStory::get('account');
        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', '/account/changepassword');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testPrivacyPageRenders(): void
    {
        $account = AccountStory::get('account');
        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', "/portal/{$account->getContextId()}/account/privacy");
        $this->assertResponseIsSuccessful();
    }

    public function testNotificationsPageRequiresPortalModerator(): void
    {
        // The notifications endpoint requires PORTAL_MODERATOR; AccountStory
        // produces a regular non-moderator user, so the access-denied
        // handler redirects to the portal/room fallback.
        $account = AccountStory::get('account');
        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', "/portal/{$account->getContextId()}/account/notifications");
        $this->assertResponseRedirects();
    }

    public function testNewsletterPageRenders(): void
    {
        $account = AccountStory::get('account');
        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', "/portal/{$account->getContextId()}/account/newsletter");
        $this->assertResponseIsSuccessful();
    }

    public function testAdditionalPageRenders(): void
    {
        $account = AccountStory::get('account');
        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', "/portal/{$account->getContextId()}/account/additional");
        $this->assertResponseIsSuccessful();
    }

    public function testDeleteAccountPageRenders(): void
    {
        $account = AccountStory::get('account');
        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', "/portal/{$account->getContextId()}/account/delete");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testMergeAccountsPageRenders(): void
    {
        $account = AccountStory::get('account');
        $this->loginAsUser($account->getContextId(), $account->getUsername(), $account->getPlainPassword());

        $this->client->request('GET', "/portal/{$account->getContextId()}/account/merge");
        $this->assertResponseIsSuccessful();
    }
}
