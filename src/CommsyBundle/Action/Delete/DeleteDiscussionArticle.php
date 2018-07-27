<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 16.07.18
 * Time: 23:22
 */

namespace CommsyBundle\Action\Delete;


use Symfony\Component\Routing\RouterInterface;

class DeleteDiscussionArticle implements DeleteInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param \cs_item $item
     */
    public function delete(\cs_item $item): void
    {
        $item->delete();
    }

    /**
     * @param \cs_item $item
     * @return string|null
     */
    public function getRedirectRoute(\cs_item $item)
    {
        /** @var \cs_discussionarticle_item $discussionArticle */
        $discussionArticle = $item;

        return $this->router->generate('commsy_discussion_detail', [
            'roomId' => $discussionArticle->getContextID(),
            'itemId' => $discussionArticle->getLinkedItem()->getItemID(),
        ]);
    }
}