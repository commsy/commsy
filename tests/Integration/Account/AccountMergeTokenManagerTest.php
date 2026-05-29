<?php

declare(strict_types=1);

namespace Tests\Integration\Account;

use App\Account\AccountMergeTokenManager;
use App\Entity\AccountMergeToken;
use App\Repository\AccountMergeTokenRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AccountFactory;

/**
 * Covers the account-merge token lifecycle: creation (raw token returned,
 * hash persisted), validation, expiry, and single-use consumption.
 */
class AccountMergeTokenManagerTest extends KernelTestCase
{
    public function testCreateReturnsRawTokenAndPersistsHashedRecord(): void
    {
        self::bootKernel();
        $manager = $this->manager();
        $from = AccountFactory::createOne();
        $into = AccountFactory::createOne();

        $raw = $manager->create($from, $into);

        self::assertSame(64, \strlen($raw));
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $raw);

        $token = $manager->findValid($raw);
        self::assertInstanceOf(AccountMergeToken::class, $token);
        self::assertSame($from->getId(), $token->getFromAccount()->getId());
        self::assertSame($into->getId(), $token->getIntoAccount()->getId());

        // Stored as a hash, never in clear.
        self::assertSame(hash('sha256', $raw), $token->getTokenHash());
        self::assertNotSame($raw, $token->getTokenHash());
        self::assertGreaterThan(new \DateTimeImmutable(), $token->getExpiresAt());
    }

    public function testConsumeIsSingleUse(): void
    {
        self::bootKernel();
        $manager = $this->manager();
        $raw = $manager->create(AccountFactory::createOne(), AccountFactory::createOne());

        $token = $manager->findValid($raw);
        self::assertNotNull($token);

        $manager->consume($token);

        self::assertNull($manager->findValid($raw), 'token must not validate twice');
        self::assertSame(0, $this->repository()->count([]));
    }

    public function testExpiredTokenIsRejectedAndRemoved(): void
    {
        self::bootKernel();
        $manager = $this->manager();
        $repository = $this->repository();

        $past = (new \DateTimeImmutable())->sub(new \DateInterval('PT1M'));
        $repository->save(new AccountMergeToken(
            hash('sha256', 'expired-raw'),
            AccountFactory::createOne(),
            AccountFactory::createOne(),
            $past,
            $past,
        ));

        self::assertNull($manager->findValid('expired-raw'), 'expired token must not validate');
        self::assertSame(0, $repository->count([]), 'expired token must be removed on access');
    }

    public function testUnknownTokenReturnsNull(): void
    {
        self::bootKernel();
        $manager = $this->manager();

        self::assertNull($manager->findValid('does-not-exist'));
        self::assertNull($manager->findValid(''));
    }

    private function manager(): AccountMergeTokenManager
    {
        return self::getContainer()->get(AccountMergeTokenManager::class);
    }

    private function repository(): AccountMergeTokenRepository
    {
        return self::getContainer()->get(AccountMergeTokenRepository::class);
    }
}
