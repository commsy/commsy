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

namespace App\Controller;

use App\Feed\CommsyFeedContentProvider;
use FeedIo\FeedIo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves the room RSS/Atom feed.
 *
 * Replaces the former debril/rss-atom-bundle StreamController: the feed is
 * built by {@see CommsyFeedContentProvider} and serialized directly with
 * feed-io ({@see FeedIo}), which needs no HTTP client for formatting.
 */
class FeedController extends AbstractController
{
    #[Route('/rss/{contextId}', name: 'app_rss', defaults: ['format' => 'rss'])]
    public function feed(Request $request, CommsyFeedContentProvider $provider, FeedIo $feedIo): Response
    {
        $feed = $provider->getFeed($request);

        $format = $request->get('format', 'rss');
        if (!in_array($format, ['rss', 'atom'], true)) {
            $format = 'rss';
        }

        $content = $feedIo->format($feed, $format);
        $contentType = 'atom' === $format ? 'application/atom+xml' : 'application/rss+xml';

        $response = new Response($content, Response::HTTP_OK, ['Content-Type' => $contentType]);
        $lastModified = $feed->getLastModified();
        if ($lastModified instanceof \DateTimeInterface) {
            $response->setLastModified(\DateTimeImmutable::createFromInterface($lastModified));
        }

        return $response;
    }
}
