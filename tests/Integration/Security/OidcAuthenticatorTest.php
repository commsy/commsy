<?php

declare(strict_types=1);

namespace Tests\Integration\Security;

use App\Account\AccountManager;
use App\Entity\Account;
use App\Security\Oidc\Flow\AuthorizationCodeFlow;
use App\Security\Oidc\Flow\UserInfo;
use App\Security\OidcAuthenticator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\Factory\AccountFactory;
use Tests\Factory\AuthSourceOIDCFactory;
use Tests\Factory\PortalFactory;

class OidcAuthenticatorTest extends KernelTestCase
{
    public function testAccountIsUpdated(): void
    {
        self::bootKernel();

        $oidcSource = AuthSourceOIDCFactory::createOne();
        $portal = PortalFactory::createOne([
            'authSources' => [$oidcSource],
        ]);
        $account = AccountFactory::createOne([
            'username' => 'identifier',
            'portal' => $portal,
            'authSource' => $oidcSource,
        ]);

        self::bootKernel();
        $container = self::getContainer();

        // Mock AccountManager
        $accountManager = $this->createMock(AccountManager::class);
        $accountManager->expects(self::once())
            ->method('propagateAccountDataToProfiles')
            ->with($this->isInstanceOf(Account::class), $this->isFalse(), $this->isNull());
        $container->set(AccountManager::class, $accountManager);

        // Mock AuthorizationCodeFlow
        $codeFlow = $this->createMock(AuthorizationCodeFlow::class);
        $codeFlow->expects(self::once())
            ->method('authenticate')
            ->willReturn(new UserInfo(
                'identifier',
                'email',
                'firstname',
                'lastname',
                'display'
            ));
        $container->set(AuthorizationCodeFlow::class, $codeFlow);

        $account = AccountFactory::find($account->getId());

        /** @var OidcAuthenticator $oidcAuthenticator */
        $oidcAuthenticator = $container->get(OidcAuthenticator::class);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->attributes->add(['context' => $portal->getId()]);
        $oidcAuthenticator->authenticate($request);

        $this->assertSame('identifier', $account->getUsername());
        $this->assertSame('email', $account->getEmail());
        $this->assertSame('firstname', $account->getFirstname());
        $this->assertSame('lastname', $account->getLastname());
        $this->assertSame('display', $account->getDisplayName());
    }

    /**
     * This will test the identification by email if the corresponding setting is used in the portal.
     * The username must be updated after logging in, if the mail address matches.
     */
    public function testAccountIsUpdatedIdentifiedByEmail(): void
    {
        self::bootKernel();

        $oidcSource = AuthSourceOIDCFactory::createOne();
        $oidcSource->setUseEmailAsIdentifier(true);
        $portal = PortalFactory::createOne([
            'authSources' => [$oidcSource],
        ]);
        $account = AccountFactory::createOne([
            'username' => 'oldidentifier',
            'email' => 'e@mail.de',
            'portal' => $portal,
            'authSource' => $oidcSource,
        ]);

        self::bootKernel();
        $container = self::getContainer();

        // Mock AccountManager
        $accountManager = $this->createMock(AccountManager::class);
        $accountManager->expects(self::once())
            ->method('propagateAccountDataToProfiles')
            ->with($this->isInstanceOf(Account::class), $this->isTrue(), $this->isInstanceOf(Account::class));
        $container->set(AccountManager::class, $accountManager);

        // Mock AuthorizationCodeFlow
        $codeFlow = $this->createMock(AuthorizationCodeFlow::class);
        $codeFlow->expects(self::once())
            ->method('authenticate')
            ->willReturn(new UserInfo(
                'newidentifier',
                'e@mail.de',
                'firstname',
                'lastname',
                'display'
            ));
        $container->set(AuthorizationCodeFlow::class, $codeFlow);

        $account = AccountFactory::find($account->getId());

        /** @var OidcAuthenticator $oidcAuthenticator */
        $oidcAuthenticator = $container->get(OidcAuthenticator::class);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->attributes->add(['context' => $portal->getId()]);
        $oidcAuthenticator->authenticate($request);

        $this->assertSame('newidentifier', $account->getUsername());
        $this->assertSame('e@mail.de', $account->getEmail());
        $this->assertSame('firstname', $account->getFirstname());
        $this->assertSame('lastname', $account->getLastname());
        $this->assertSame('display', $account->getDisplayName());
    }

    /**
     * If identification by email is used, make sure an account can still update its mail address. The username must
     * remain the primary identification criteria. Identification my email must remain second.
     */
    public function testAccountEmailIsUpdatedIdentifiedByEmail(): void
    {
        self::bootKernel();

        $oidcSource = AuthSourceOIDCFactory::createOne();
        $oidcSource->setUseEmailAsIdentifier(true);
        $portal = PortalFactory::createOne([
            'authSources' => [$oidcSource],
        ]);
        $account = AccountFactory::createOne([
            'username' => 'identifier',
            'email' => 'old@mail.de',
            'portal' => $portal,
            'authSource' => $oidcSource,
        ]);

        self::bootKernel();
        $container = self::getContainer();

        // Mock AccountManager
        $accountManager = $this->createMock(AccountManager::class);
        $accountManager->expects(self::once())
            ->method('propagateAccountDataToProfiles')
            ->with($this->isInstanceOf(Account::class), $this->isFalse(), $this->isNull());
        $container->set(AccountManager::class, $accountManager);

        // Mock AuthorizationCodeFlow
        $codeFlow = $this->createMock(AuthorizationCodeFlow::class);
        $codeFlow->expects(self::once())
            ->method('authenticate')
            ->willReturn(new UserInfo(
                'identifier',
                'new@mail.de',
                'firstname',
                'lastname',
                'display'
            ));
        $container->set(AuthorizationCodeFlow::class, $codeFlow);

        $account = AccountFactory::find($account->getId());

        /** @var OidcAuthenticator $oidcAuthenticator */
        $oidcAuthenticator = $container->get(OidcAuthenticator::class);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->attributes->add(['context' => $portal->getId()]);
        $oidcAuthenticator->authenticate($request);

        $this->assertSame('identifier', $account->getUsername());
        $this->assertSame('new@mail.de', $account->getEmail());
        $this->assertSame('firstname', $account->getFirstname());
        $this->assertSame('lastname', $account->getLastname());
        $this->assertSame('display', $account->getDisplayName());
    }
}
