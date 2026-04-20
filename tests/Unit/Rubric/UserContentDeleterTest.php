<?php

namespace Tests\Unit\Rubric;

use App\Account\AccountSetting;
use App\Account\AccountSettingsManager;
use App\Assessment\AssessmentDeleter;
use App\Entity\Account;
use App\Entity\Portal;
use App\Rubric\DeletionStrategy;
use App\Rubric\UserContentDeleter;
use App\User\UserDeletionHelper;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class UserContentDeleterTest extends TestCase
{
    private AccountSettingsManager $settingsManager;
    private UserContentDeleter $deleter;

    protected function setUp(): void
    {
        $this->settingsManager = $this->createMock(AccountSettingsManager::class);
        $connection = $this->createMock(Connection::class);

        $this->deleter = new UserContentDeleter(
            new \ArrayIterator([]),
            new \ArrayIterator([]),
            $this->settingsManager,
            $connection,
            $this->createMock(UserDeletionHelper::class),
            $this->createMock(AssessmentDeleter::class),
        );
    }

    public function testResolveStrategyReturnsKeepItemsWhenAccountIsNull(): void
    {
        $this->assertSame(
            DeletionStrategy::KEEP_ITEMS,
            $this->deleter->resolveStrategy(null)
        );
    }

    public function testResolveStrategyReturnsKeepItemsWhenPortalIsNull(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getPortal')->willReturn(null);

        $this->assertSame(
            DeletionStrategy::KEEP_ITEMS,
            $this->deleter->resolveStrategy($account)
        );
    }

    public function testResolveStrategyUsesPortalDefaultWhenUserChoiceNotAllowed(): void
    {
        $portal = new Portal();
        $portal->setAllowUserDefinedDeletionStrategy(false);
        $portal->setCascadingUserDeletionStrategy(true);

        $account = $this->createMock(Account::class);
        $account->method('getPortal')->willReturn($portal);

        $this->assertSame(
            DeletionStrategy::CASCADE_ITEMS,
            $this->deleter->resolveStrategy($account)
        );
    }

    public function testResolveStrategyUsesPortalDefaultKeepWhenUserChoiceNotAllowed(): void
    {
        $portal = new Portal();
        $portal->setAllowUserDefinedDeletionStrategy(false);
        $portal->setCascadingUserDeletionStrategy(false);

        $account = $this->createMock(Account::class);
        $account->method('getPortal')->willReturn($portal);

        $this->assertSame(
            DeletionStrategy::KEEP_ITEMS,
            $this->deleter->resolveStrategy($account)
        );
    }

    public function testResolveStrategyUsesUserPreferenceWhenAllowed(): void
    {
        $portal = new Portal();
        $portal->setAllowUserDefinedDeletionStrategy(true);
        $portal->setCascadingUserDeletionStrategy(false); // portal default is keep

        $account = $this->createMock(Account::class);
        $account->method('getPortal')->willReturn($portal);

        $this->settingsManager->method('getSetting')
            ->with($account, AccountSetting::USER_DELETION_CASCADING_ITEMS)
            ->willReturn(['enabled' => true]);

        $this->assertSame(
            DeletionStrategy::CASCADE_ITEMS,
            $this->deleter->resolveStrategy($account)
        );
    }

    public function testResolveStrategyUsesUserPreferenceKeepWhenAllowed(): void
    {
        $portal = new Portal();
        $portal->setAllowUserDefinedDeletionStrategy(true);
        $portal->setCascadingUserDeletionStrategy(true); // portal default is cascade

        $account = $this->createMock(Account::class);
        $account->method('getPortal')->willReturn($portal);

        $this->settingsManager->method('getSetting')
            ->with($account, AccountSetting::USER_DELETION_CASCADING_ITEMS)
            ->willReturn(['enabled' => false]);

        $this->assertSame(
            DeletionStrategy::KEEP_ITEMS,
            $this->deleter->resolveStrategy($account)
        );
    }
}
