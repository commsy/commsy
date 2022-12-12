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

use cs_discussionarticle_item;
use cs_item;
use Symfony\Component\Routing\RouterInterface;

class DeleteDiscussionArticle implements DeleteInterface
{
    public function __construct(private RouterInterface $router)
    {
    }

    public function delete(cs_item $item): void
    {
        $item->delete();
    }

    /**
     * @return string|null
     */
    public function getRedirectRoute(cs_item $item)
    {
        /** @var cs_discussionarticle_item $discussionArticle */
        $discussionArticle = $item;

        return $this->router->generate('app_discussion_detail', [
            'roomId' => $discussionArticle->getContextID(),
            'itemId' => $discussionArticle->getLinkedItem()->getItemID(),
        ]);
    }
}
