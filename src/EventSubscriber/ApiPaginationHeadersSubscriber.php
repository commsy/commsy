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

namespace App\EventSubscriber;

use ApiPlatform\State\Pagination\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Says on a paginated list which page it is and where the others are.
 *
 * The interface answers in plain JSON, whose body is the bare list with no room
 * for it, so the page goes into the headers: `Link` (RFC 8288) with first,
 * prev, next and last, and the number of entries in all as `X-Total-Count`.
 */
final readonly class ApiPaginationHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $page = $request->attributes->get('data');

        if (!$page instanceof PaginatorInterface || !$response->isSuccessful()) {
            return;
        }

        $current = (int) $page->getCurrentPage();
        $last = max(1, (int) $page->getLastPage());

        $pages = ['first' => 1];
        if ($current > 1) {
            $pages['prev'] = min($current - 1, $last);
        }
        if ($current < $last) {
            $pages['next'] = $current + 1;
        }
        $pages['last'] = $last;

        $links = [];
        foreach ($pages as $relation => $number) {
            $links[] = sprintf('<%s>; rel="%s"', $this->pageUrl($request, $number), $relation);
        }

        $response->headers->set('Link', implode(', ', $links), false);
        $response->headers->set('X-Total-Count', (string) (int) $page->getTotalItems());
    }

    /**
     * Relative, so it holds behind a proxy; every other query parameter is kept.
     */
    private function pageUrl(Request $request, int $page): string
    {
        return $request->getBaseUrl().$request->getPathInfo().'?'
            .http_build_query(['page' => $page] + $request->query->all());
    }
}
