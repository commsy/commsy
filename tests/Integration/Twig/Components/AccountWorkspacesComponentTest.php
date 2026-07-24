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

declare(strict_types=1);

namespace Tests\Integration\Twig\Components;

use App\Entity\Account;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Smoke-pins {@see \App\Twig\Components\AccountWorkspacesComponent}:
 * the component must mount cleanly with an Account prop and survive
 * writable-prop updates for all four room filters. Catches regressions
 * in the LiveProp wiring or in `UserRepository::findAllByRoomStatus`.
 *
 * Pure live-component (no AbstractController, no form trait, no
 * security calls during mount) — therefore the lightest possible
 * baseline for our component test suite.
 */
#[WithStory(AccountStory::class)]
final class AccountWorkspacesComponentTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    private Account $account;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->account = AccountStory::get('account');
    }

    public function testMountsWithAccountProp(): void
    {
        $component = $this->createLiveComponent(
            name: 'AccountWorkspacesComponent',
            data: ['account' => $this->account],
        );

        $rendered = $component->render();
        self::assertNotEmpty((string) $rendered, 'initial render must produce non-empty output');
    }

    /**
     * Values mirror the actual `<option value="...">` set rendered by
     * components/AccountWorkspacesComponent.html.twig — anything else
     * would only exercise our test harness, not the Production code.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function filterProvider(): iterable
    {
        yield 'filterArchived only'        => ['filterArchived', 'only'];
        yield 'filterArchived except'      => ['filterArchived', 'except'];
        yield 'filterLocked only'          => ['filterLocked', 'only'];
        yield 'filterLocked except'        => ['filterLocked', 'except'];
        yield 'filterType project'         => ['filterType', 'project'];
        yield 'filterType grouproom'       => ['filterType', 'grouproom'];
        yield 'filterType community'       => ['filterType', 'community'];
        yield 'filterType userroom'        => ['filterType', 'userroom'];
        yield 'filterUserStatus moderator' => ['filterUserStatus', '3'];
        yield 'filterUserStatus user'      => ['filterUserStatus', '2'];

        // Defense-in-depth: an unexpected value (e.g. URL-edited
        // request) must not crash the page. Pinning UserRepository's
        // explicit-enum guard on filterLocked — the previous
        // `!== 'all'` form bound the :statusValues parameter without
        // a matching placeholder and crashed Doctrine.
        yield 'filterLocked invalid → no crash' => ['filterLocked', 'invalid-value-from-bad-url'];
    }

    #[DataProvider('filterProvider')]
    public function testWritableFilterPropTriggersReRender(string $prop, string $value): void
    {
        $component = $this->createLiveComponent(
            name: 'AccountWorkspacesComponent',
            data: ['account' => $this->account],
        );

        $component->set($prop, $value);

        self::assertSame(
            $value,
            $component->component()->{$prop},
            sprintf('%s must be applied to the live component instance', $prop),
        );

        $rendered = $component->render();
        self::assertNotEmpty((string) $rendered, sprintf('re-render after %s update must succeed', $prop));
    }
}
