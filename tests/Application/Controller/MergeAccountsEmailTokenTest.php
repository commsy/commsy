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
use App\Entity\AccountMergeToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Tests\Application\AbstractApplicationTestCase;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceLocalFactory;
use Tests\Factory\AuthSourceShibbolethFactory;
use Tests\Factory\PortalFactory;

/**
 * End-to-end coverage of the e-mail-token account-merge flow: an externally
 * authenticated "old" account A is legitimised by a token mailed to A, not by
 * a password. The local-account path keeps verifying the password.
 */
class MergeAccountsEmailTokenTest extends AbstractApplicationTestCase
{
    use MailerAssertionsTrait;

    public function testExternalAccountMergeSendsConfirmationMailWithoutMerging(): void
    {
        ['portal' => $portal, 'new' => $newAccount, 'old' => $oldAccount, 'shib' => $shibSource] =
            $this->createScenario();

        $portalId = $portal->getId();
        $oldAccountId = $oldAccount->getId();

        $this->loginAsUser($portalId, $newAccount->getUsername(), $newAccount->getPlainPassword());

        $crawler = $this->client->request('GET', "/portal/{$portalId}/account/merge");
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('profile_mergeaccounts[save]')->form();
        $form['profile_mergeaccounts[combineUserId]'] = 'old.bkennung';
        $form['profile_mergeaccounts[auth_source]'] = (string) $shibSource->getId();
        $this->client->submit($form);

        $this->assertResponseRedirects("/portal/{$portalId}/account/merge");

        // A confirmation mail is sent to the OLD account A's address ...
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage(0);
        $this->assertEmailAddressContains($email, 'To', 'old-bkennung@example.test');

        // ... but no merge has happened yet: A still exists.
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->assertNotNull(
            $entityManager->getRepository(Account::class)->find($oldAccountId),
            'old account must still exist before confirmation'
        );

        // A pending merge token has been stored, bound to (A -> N).
        $mergeToken = $this->singleMergeToken();
        $this->assertNotNull($mergeToken);
        $this->assertSame($oldAccountId, $mergeToken->getFromAccount()->getId());
        $this->assertSame($newAccount->getId(), $mergeToken->getIntoAccount()->getId());

        // The raw token lives only in the mail link; the DB stores its SHA-256 hash.
        $rawToken = $this->extractTokenFromLastMail();
        $this->assertSame(hash('sha256', $rawToken), $mergeToken->getTokenHash());
        $this->assertNotSame($rawToken, $mergeToken->getTokenHash());
        $this->assertStringContainsString(
            "/account/merge/confirm/{$rawToken}",
            $email->getHtmlBody() ?? ''
        );
    }

    public function testConfirmationLinkMergesAndConsumesToken(): void
    {
        ['portal' => $portal, 'new' => $newAccount, 'old' => $oldAccount, 'shib' => $shibSource] =
            $this->createScenario();

        $portalId = $portal->getId();
        $oldAccountId = $oldAccount->getId();

        $this->loginAsUser($portalId, $newAccount->getUsername(), $newAccount->getPlainPassword());

        // Request the merge (sends the mail, stores the token).
        $crawler = $this->client->request('GET', "/portal/{$portalId}/account/merge");
        $form = $crawler->selectButton('profile_mergeaccounts[save]')->form();
        $form['profile_mergeaccounts[combineUserId]'] = 'old.bkennung';
        $form['profile_mergeaccounts[auth_source]'] = (string) $shibSource->getId();
        $this->client->submit($form);

        // The raw token is only in the mail (DB stores its hash).
        $rawToken = $this->extractTokenFromLastMail();

        // GET shows the confirmation page (does NOT merge yet).
        $confirmCrawler = $this->client->request(
            'GET',
            "/portal/{$portalId}/account/merge/confirm/{$rawToken}"
        );
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form button[type=submit]');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->assertNotNull(
            $entityManager->getRepository(Account::class)->find($oldAccountId),
            'GET on the confirmation link must not merge'
        );
        $this->assertSame(1, $this->mergeTokenCount());

        // POST confirms: the merge runs; the logged-in initiator (N) is sent into the portal.
        $confirmForm = $confirmCrawler->filter('form')->form();
        $this->client->submit($confirmForm);
        $this->assertResponseRedirects("/portal/{$portalId}/enter");

        $entityManager->clear();
        $this->assertNull(
            $entityManager->getRepository(Account::class)->find($oldAccountId),
            'old account A must be deleted after a confirmed merge'
        );
        // Single-use: the token row is gone (FK ON DELETE CASCADE on the deleted account A).
        $this->assertSame(0, $this->mergeTokenCount(), 'merge token must be consumed');
    }

    public function testLocalAccountMergeUsesPasswordAndMergesDirectly(): void
    {
        $localSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $portal = PortalFactory::createOne(['authSources' => [$localSource]]);

        $newAccount = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'new.local',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);
        $oldAccount = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'old.local',
            'plainPassword' => 'zfCbzLm9h4$h',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $portalId = $portal->getId();
        $oldAccountId = $oldAccount->getId();

        $this->loginAsUser($portalId, $newAccount->getUsername(), $newAccount->getPlainPassword());

        $crawler = $this->client->request('GET', "/portal/{$portalId}/account/merge");
        $form = $crawler->selectButton('profile_mergeaccounts[save]')->form();
        $form['profile_mergeaccounts[combineUserId]'] = 'old.local';
        $form['profile_mergeaccounts[combinePassword]'] = 'zfCbzLm9h4$h';
        $form['profile_mergeaccounts[auth_source]'] = (string) $localSource->getId();
        $this->client->submit($form);

        $this->assertResponseRedirects("/portal/{$portalId}/account/merge");
        // Local path legitimises by password, not by mail.
        $this->assertEmailCount(0);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $this->assertNull(
            $entityManager->getRepository(Account::class)->find($oldAccountId),
            'local account must be merged (deleted) directly after a valid password'
        );
    }

    public function testLocalAccountMergeWithWrongPasswordDoesNotMerge(): void
    {
        $localSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $portal = PortalFactory::createOne(['authSources' => [$localSource]]);

        $newAccount = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'new.local',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);
        $oldAccount = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'old.local',
            'plainPassword' => 'zfCbzLm9h4$h',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $portalId = $portal->getId();
        $oldAccountId = $oldAccount->getId();

        $this->loginAsUser($portalId, $newAccount->getUsername(), $newAccount->getPlainPassword());

        $crawler = $this->client->request('GET', "/portal/{$portalId}/account/merge");
        $form = $crawler->selectButton('profile_mergeaccounts[save]')->form();
        $form['profile_mergeaccounts[combineUserId]'] = 'old.local';
        $form['profile_mergeaccounts[combinePassword]'] = 'wrong-password';
        $form['profile_mergeaccounts[auth_source]'] = (string) $localSource->getId();
        $this->client->submit($form);

        // Invalid form re-renders with status 422 (Symfony); no merge, no mail.
        $this->assertResponseStatusCodeSame(422);
        $this->assertEmailCount(0);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->assertNotNull(
            $entityManager->getRepository(Account::class)->find($oldAccountId),
            'wrong password must not merge the account'
        );
    }

    public function testInvalidTokenShowsInvalidPageAndDoesNotMerge(): void
    {
        ['portal' => $portal] = $this->createScenario();

        $this->client->request(
            'GET',
            "/portal/{$portal->getId()}/account/merge/confirm/deadbeefdeadbeef"
        );

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('form button[type=submit]');
        $this->assertAnySelectorTextContains('p', 'ungültig oder abgelaufen');
    }

    /**
     * @return array{portal: mixed, new: mixed, old: mixed, shib: mixed, local: mixed}
     */
    private function createScenario(): array
    {
        $localSource = AuthSourceLocalFactory::createOne(['enabled' => true, 'default' => true]);
        $shibSource = AuthSourceShibbolethFactory::createOne(['enabled' => true, 'default' => false]);
        $portal = PortalFactory::createOne(['authSources' => [$localSource, $shibSource]]);

        $newAccount = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $localSource,
            'username' => 'new.local',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        $oldAccount = AccountFactory::createOne([
            'portal' => $portal,
            'authSource' => $shibSource,
            'username' => 'old.bkennung',
            'email' => 'old-bkennung@example.test',
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        return [
            'portal' => $portal,
            'new' => $newAccount,
            'old' => $oldAccount,
            'shib' => $shibSource,
            'local' => $localSource,
        ];
    }

    private function singleMergeToken(): ?AccountMergeToken
    {
        return static::getContainer()->get(EntityManagerInterface::class)
            ->createQuery('SELECT t FROM App\Entity\AccountMergeToken t')
            ->getOneOrNullResult();
    }

    private function mergeTokenCount(): int
    {
        return (int) static::getContainer()->get(EntityManagerInterface::class)
            ->createQuery('SELECT COUNT(t.id) FROM App\Entity\AccountMergeToken t')
            ->getSingleScalarResult();
    }

    private function extractTokenFromLastMail(): string
    {
        $email = $this->getMailerMessage(0);
        self::assertNotNull($email);
        self::assertSame(
            1,
            preg_match('~/account/merge/confirm/([0-9a-f]{64})~', (string) $email->getHtmlBody(), $matches),
            'confirmation link with a 64-char token must be present in the mail'
        );

        return $matches[1];
    }
}
