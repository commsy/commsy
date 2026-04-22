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

namespace App\Action\Delete;

use App\Rubric\Discussion\DiscussionDeleter;
use App\Services\LegacyEnvironment;
use cs_discussionarticle_item;
use cs_environment;
use cs_item;
use Symfony\Component\Routing\RouterInterface;

/**
 * Thin wrapper around {@see DiscussionDeleter::deleteArticle()}.
 */
class DeleteDiscussionArticle implements DeleteInterface
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly DiscussionDeleter $discussionDeleter,
        LegacyEnvironment $legacyEnvironment,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function delete(cs_item $item): void
    {
        $deleterId = (int) $this->legacyEnvironment->getCurrentUserItem()?->getItemID();
        $this->discussionDeleter->deleteArticle((int) $item->getItemId(), $deleterId);
    }

    public function getRedirectRoute(cs_item $item): ?string
    {
        /** @var cs_discussionarticle_item $discussionArticle */
        $discussionArticle = $item;

        return $this->router->generate('app_discussion_detail', [
            'roomId' => $discussionArticle->getContextID(),
            'itemId' => $discussionArticle->getLinkedItem()->getItemID(),
        ]);
    }
}
