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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Tests\Factory\AccountFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * A live action is its own HTTP endpoint, and the firewall lets /_components
 * through as PUBLIC_ACCESS — authentication is therefore the component's own
 * business. These tests state that from the outside: nobody signed in, nothing
 * happens.
 *
 * Reading is a different matter and deliberately not guarded here: the account
 * travels in the signed props, so a render can only ever show what the page that
 * minted those props was already allowed to show.
 */
final class NotificationComponentAuthorizationTest extends WebTestCase
{
    use Factories;
    use InteractsWithLiveComponents;
    use ResetDatabase;

    public function testTheBellRefusesToDecideForAnonymousCallers(): void
    {
        $component = $this->componentFor('NotificationBell');

        $this->expectException(AccessDeniedException::class);
        $component->call('accept', ['id' => 180]);
    }

    public function testTheBellRefusesToMarkReadForAnonymousCallers(): void
    {
        $component = $this->componentFor('NotificationBell');

        $this->expectException(AccessDeniedException::class);
        $component->call('markAllRead');
    }

    public function testThePanelRefusesToMarkReadForAnonymousCallers(): void
    {
        $component = $this->componentFor('NotificationPanel');

        $this->expectException(AccessDeniedException::class);
        $component->call('markAllRead');
    }

    private function componentFor(string $name): \Symfony\UX\LiveComponent\Test\TestLiveComponent
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->disableReboot();

        $account = AccountFactory::createOne([
            'activityState' => Account::ACTIVITY_ACTIVE,
            'locked' => false,
        ]);

        // No login: the component is built as a page would, but the call
        // arrives without a session.
        return $this->createLiveComponent($name, ['account' => $account], $client);
    }
}
