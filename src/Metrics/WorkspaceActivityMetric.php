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

namespace App\Metrics;

use App\Entity\Portal;
use App\Metrics\Data\WorkspaceActivity;
use App\Repository\PortalRepository;
use App\Utils\RequestContext;
use App\Utils\RequestLogging;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class WorkspaceActivityMetric extends AbstractMetric implements MetricInterface, EventSubscriberInterface
{
    private const string CACHE_KEY = 'commsy_metrics_workspace_activity';

    /**
     * How long an activity counts as recent; older ones are of no use and
     * dropped.
     */
    private const string RECENT = 'PT5M';

    public function __construct(
        private readonly PortalRepository $portalRepository,
        private readonly RequestContext $requestContext,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return;
        }

        foreach (RequestLogging::ROOM_CONTEXT_IGNORE_REGEX_ARRAY as $regex) {
            if (preg_match($regex, $request->getUri())) {
                return;
            }
        }

        $room = $this->requestContext->fetchRoom($event->getRequest());
        if (!$room) {
            return;
        }

        /** @var Portal $portal */
        $portal = $room->getPortal();

        $cachedActivity = $this->getCachedActivity();

        // Renew the entry of this workspace, or add one
        $matchWorkspace = $cachedActivity->findFirst(fn ($key, WorkspaceActivity $el) => $el->getWorkspaceId() === $room->getItemId());
        if ($matchWorkspace) {
            $matchWorkspace->renew();
        } else {
            $cachedActivity->add(new WorkspaceActivity(
                $room->getItemId(),
                $room->getType(),
                $portal->getTitle()
            ));
        }

        // Update cache, without the activities no longer recent
        $this->saveCachedActivity($cachedActivity->filter($this->isRecent(...)));
    }

    public function update(): void
    {
        $cachedActivity = $this->getCachedActivity();

        // Filter all workspaces that have been expired
        $nonExpiredActivities = $cachedActivity->filter($this->isRecent(...));

        $this->getAdapter()->wipeData('counter', 'workspace_activity_current');

        $workspaceActivity = $this->getCollectorRegistry()->getOrRegisterCounter(
            $this->getNamespace(),
            'workspace_activity_current',
            'Recent activity of workspaces',
            ['portal', 'workspaceId', 'type']
        );

        foreach ($nonExpiredActivities as $nonExpiredActivity) {
            /** @var WorkspaceActivity $nonExpiredActivity */
            $workspaceActivity->inc([
                $nonExpiredActivity->getPortalTitle(),
                $nonExpiredActivity->getWorkspaceId(),
                $nonExpiredActivity->getWorkspaceType(),
            ]);
        }
    }

    private function isRecent(WorkspaceActivity $activity): bool
    {
        return $activity->getCached() >= (new DateTimeImmutable())->sub(new DateInterval(self::RECENT));
    }

    private function getCachedActivity(): Collection
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY);

        return $cacheItem->isHit() ? $cacheItem->get() : new ArrayCollection();
    }

    private function saveCachedActivity(Collection $activity): void
    {
        $cacheItem = $this->cache->getItem(self::CACHE_KEY);

        $cacheItem->set($activity);
        $this->cache->save($cacheItem);
    }
}
