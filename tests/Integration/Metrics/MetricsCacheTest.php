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

namespace Tests\Integration\Metrics;

use App\Entity\Account;
use App\Metrics\AccountLoginsMetric;
use App\Metrics\Data\CachedLogin;
use App\Metrics\Data\CachedLogins;
use App\Metrics\Data\WorkspaceActivity;
use App\Metrics\WorkspaceActivityMetric;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Tests\Factory\RoomFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The metrics keep what they count in the application cache, and only what
 * is recent enough to count: a workspace once, however often it is used, and
 * no logins or activity older than the window they are counted in.
 */
#[WithStory(AccountStory::class)]
final class MetricsCacheTest extends KernelTestCase
{
    private CacheItemPoolInterface $cache;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->cache = self::getContainer()->get('cache.app');
        $this->cache->deleteItems(['commsy_metrics_workspace_activity', 'commsy_metrics_account_logins']);
    }

    public function testAWorkspaceInUseIsKeptOnceAndOldActivityIsDropped(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $room = RoomFactory::new()->project()->create(['contextId' => $account->getPortal()?->getId(), 'portal' => $account->getPortal()]);
        $this->store('commsy_metrics_workspace_activity', new ArrayCollection([$this->old(new WorkspaceActivity(1, 'project', 'Portal'))]));

        $metric = self::getContainer()->get(WorkspaceActivityMetric::class);
        $metric->onKernelTerminate($this->terminated($room->getItemId()));
        $metric->onKernelTerminate($this->terminated($room->getItemId()));

        /** @var Collection<int, WorkspaceActivity> $stored */
        $stored = $this->cache->getItem('commsy_metrics_workspace_activity')->get();
        self::assertSame([$room->getItemId()], array_values($stored->map(static fn (WorkspaceActivity $activity): int => $activity->getWorkspaceId())->toArray()));
    }

    public function testOldLoginsAreDroppedWithTheNextLogin(): void
    {
        /** @var Account $account */
        $account = AccountStory::get('account');
        $this->store('commsy_metrics_account_logins', (new CachedLogins())->setLogins(new ArrayCollection([$this->old(new CachedLogin(-1, 'Portal'))])));

        self::getContainer()->get(AccountLoginsMetric::class)->onLoginSuccess(new LoginSuccessEvent(
            $this->createStub(AuthenticatorInterface::class),
            new SelfValidatingPassport(new UserBadge($account->getUserIdentifier(), static fn (): Account => $account)),
            new UsernamePasswordToken($account, 'main', $account->getRoles()),
            new Request(),
            null,
            'main',
        ));

        /** @var CachedLogins $stored */
        $stored = $this->cache->getItem('commsy_metrics_account_logins')->get();
        self::assertSame([$account->getId()], array_values($stored->getLogins()->map(static fn (CachedLogin $login): int => $login->getAccountId())->toArray()));
    }

    private function store(string $key, mixed $value): void
    {
        $this->cache->save($this->cache->getItem($key)->set($value));
    }

    /**
     * @template T of object
     *
     * @param T $entry
     *
     * @return T
     */
    private function old(object $entry): object
    {
        (new \ReflectionProperty($entry, 'cached'))->setValue($entry, new \DateTimeImmutable('-1 hour'));

        return $entry;
    }

    private function terminated(int $roomId): TerminateEvent
    {
        $request = Request::create("/room/$roomId/material/feed");
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->attributes->set('roomId', $roomId);

        return new TerminateEvent(self::$kernel, $request, new Response());
    }
}
