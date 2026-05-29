<?php

declare(strict_types=1);

namespace Tests\Integration\Hash;

use App\Entity\Hash;
use App\Hash\HashManager;
use App\Repository\HashRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Covers the account-merge token lifecycle on the shared Hash row:
 * creation, validation, expiry, and single-use consumption.
 */
class HashManagerMergeTokenTest extends KernelTestCase
{
    private const USER_ID = 990001;
    private const FROM_ACCOUNT_ID = 111;
    private const INTO_ACCOUNT_ID = 222;

    public function testCreateMergeHashStoresTokenAndAccounts(): void
    {
        self::bootKernel();
        $manager = $this->getHashManager();

        $token = $manager->createMergeHash(self::USER_ID, self::FROM_ACCOUNT_ID, self::INTO_ACCOUNT_ID);

        self::assertSame(64, \strlen($token));
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);

        $hash = $manager->findValidMergeHash($token);
        self::assertInstanceOf(Hash::class, $hash);
        self::assertSame(self::FROM_ACCOUNT_ID, $hash->getMergeFromAccountId());
        self::assertSame(self::INTO_ACCOUNT_ID, $hash->getMergeIntoAccountId());
        self::assertNotNull($hash->getMergeExpiresAt());
        self::assertGreaterThan(new \DateTimeImmutable(), $hash->getMergeExpiresAt());
    }

    public function testConsumeMergeHashIsSingleUseAndKeepsFeedHashes(): void
    {
        self::bootKernel();
        $manager = $this->getHashManager();

        $token = $manager->createMergeHash(self::USER_ID, self::FROM_ACCOUNT_ID, self::INTO_ACCOUNT_ID);
        $hash = $manager->findValidMergeHash($token);
        self::assertInstanceOf(Hash::class, $hash);

        // Feed hashes must remain untouched by the merge flow.
        $rssBefore = $hash->getRss();
        $icalBefore = $hash->getIcal();

        $manager->consumeMergeHash($hash);

        self::assertNull($manager->findValidMergeHash($token), 'token must not validate twice');

        $reloaded = $this->getHashRepository()->findByUserId(self::USER_ID);
        self::assertInstanceOf(Hash::class, $reloaded, 'hash row itself must survive consumption');
        self::assertNull($reloaded->getMergeToken());
        self::assertNull($reloaded->getMergeFromAccountId());
        self::assertNull($reloaded->getMergeIntoAccountId());
        self::assertNull($reloaded->getMergeExpiresAt());
        self::assertSame($rssBefore, $reloaded->getRss());
        self::assertSame($icalBefore, $reloaded->getIcal());
    }

    public function testExpiredTokenIsRejectedAndCleared(): void
    {
        self::bootKernel();
        $manager = $this->getHashManager();
        $repository = $this->getHashRepository();

        $token = $manager->createMergeHash(self::USER_ID, self::FROM_ACCOUNT_ID, self::INTO_ACCOUNT_ID);

        // Force the token into the past.
        $hash = $repository->findByUserId(self::USER_ID);
        $hash->setMergeExpiresAt((new \DateTimeImmutable())->sub(new \DateInterval('PT1M')));
        $repository->save($hash);

        self::assertNull($manager->findValidMergeHash($token), 'expired token must not validate');

        $reloaded = $repository->findByUserId(self::USER_ID);
        self::assertNull($reloaded->getMergeToken(), 'expired token must be cleared on access');
    }

    public function testUnknownTokenReturnsNull(): void
    {
        self::bootKernel();
        $manager = $this->getHashManager();

        self::assertNull($manager->findValidMergeHash('does-not-exist'));
        self::assertNull($manager->findValidMergeHash(''));
    }

    private function getHashManager(): HashManager
    {
        return self::getContainer()->get(HashManager::class);
    }

    private function getHashRepository(): HashRepository
    {
        return self::getContainer()->get(HashRepository::class);
    }
}
