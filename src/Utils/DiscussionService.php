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

namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_discussion_manager;
use cs_discussionarticles_manager;
use cs_environment;
use Symfony\Component\Form\FormInterface;

class DiscussionService
{
    private readonly cs_environment $legacyEnvironment;

    private readonly cs_discussion_manager $discussionManager;

    private readonly cs_discussionarticles_manager $discussionArticleManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->discussionManager = $this->legacyEnvironment->getDiscussionManager();
        $this->discussionManager->reset();

        $this->discussionArticleManager = $this->legacyEnvironment->getDiscussionArticlesManager();
        $this->discussionArticleManager->reset();
    }

    /**
     * @param int    $roomId
     * @param int    $max
     * @param int    $start
     * @param string $sort
     *
     * @return \cs_discussion_item[]
     */
    public function getListDiscussions($roomId, $max = null, $start = null, $sort = null): array
    {
        $this->discussionManager->setContextLimit($roomId);
        if (null !== $max && null !== $start) {
            $this->discussionManager->setIntervalLimit($start, $max);
        }

        if ($sort) {
            $this->discussionManager->setSortOrder($sort);
        }

        $this->discussionManager->select();
        $discussionList = $this->discussionManager->get();

        return $discussionList->to_array();
    }

    /**
     * @param int   $roomId
     * @param int[] $ids
     *
     * @return \cs_discussion_item[]
     */
    public function getDiscussionsById($roomId, $ids): array
    {
        $this->discussionManager->setContextLimit($roomId);
        $this->discussionManager->setIDArrayLimit($ids);

        $this->discussionManager->select();
        $discussionList = $this->discussionManager->get();

        return $discussionList->to_array();
    }

    public function getCountArray($roomId)
    {
        $countDiscussionArray = [];
        $this->discussionManager->setContextLimit($roomId);
        $this->discussionManager->select();
        $countDiscussionArray['count'] = sizeof($this->discussionManager->get()->to_array());
        $this->discussionManager->resetLimits();
        $this->discussionManager->select();
        $countDiscussionArray['countAll'] = $this->discussionManager->getCountAll();

        return $countDiscussionArray;
    }

    public function setFilterConditions(FormInterface $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['hide-deactivated-entries']) {
            if ('only_activated' === $formData['hide-deactivated-entries']) {
                $this->discussionManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
            } elseif ('only_deactivated' === $formData['hide-deactivated-entries']) {
                $this->discussionManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_DEACTIVATED);
            } elseif ('all' === $formData['hide-deactivated-entries']) {
                $this->discussionManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ACTIVATED_DEACTIVATED);
            }
        }

        // rubrics
        if ($formData['rubrics']) {
            // group
            if (isset($formData['rubrics']['group'])) {
                $relatedLabel = $formData['rubrics']['group'];
                $this->discussionManager->setGroupLimit($relatedLabel->getItemId());
            }

            // topic
            if (isset($formData['rubrics']['topic'])) {
                $relatedLabel = $formData['rubrics']['topic'];
                $this->discussionManager->setTopicLimit($relatedLabel->getItemId());
            }
        }

        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemId();
                $this->discussionManager->setBuzzwordLimit($itemId);
            }
        }

        // category
        if (isset($formData['category'])) {
            if (isset($formData['category']['category'])) {
                $categories = $formData['category']['category'];

                if (!empty($categories)) {
                    $this->discussionManager->setTagArrayLimit($categories);
                }
            }
        }
    }

    public function getDiscussion($itemId): ?\cs_discussion_item
    {
        return $this->discussionManager->getItem($itemId);
    }

    public function getArticle($itemId): ?\cs_discussionarticle_item
    {
        return $this->discussionArticleManager->getItem($itemId);
    }

    public function getNewDiscussion()
    {
        $discussion = $this->discussionManager->getNewItem();
        $discussion->setDiscussionType('threaded');

        return $discussion;
    }

    public function getNewArticle()
    {
        return $this->discussionArticleManager->getNewItem();
    }

    public function hideDeactivatedEntries()
    {
        $this->discussionManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    }

    public function buildArticleTree($articleList, $root = null): array
    {
        $tree = [];

        foreach ($articleList as $article) {
            /** @var \cs_discussionarticle_item $article */
            $base = &$tree;
            $expLevel = explode('.', (string) $article->getPosition());
            foreach ($expLevel as $level) {
                $base = &$base['children'][$level];
            }

            $base['item'] = $article;
        }

        return $tree;
    }

    public function calculateNewPosition(\cs_discussion_item $discussion, int $parentId): string
    {
        $parentPosition = 0;
        if (0 !== $parentId) {
            $parent = $this->discussionArticleManager->getItem($parentId);
            $parentPosition = $parent->getPosition();
        }

        /**
         * TODO: Instead of iteration all articles to find the latest in the parents branch
         * it would be much better to ask only for all childs of an article or directly
         * for the latest position.
         */
        $numParentDots = substr_count((string) $parentPosition, '.');
        $newRelativeNumericPosition = 1;
        foreach ($discussion->getAllArticles() as $article) {
            $position = $article->getPosition();

            $numDots = substr_count((string) $position, '.');

            if (0 == $parentPosition) {
                if (0 == $numDots) {
                    // compare against our latest stored position
                    if (sprintf('%1$04d', $newRelativeNumericPosition) <= $position) {
                        $newRelativeNumericPosition = $position + 1;
                    }
                }
            } else {
                // if the parent position is one level above the child ones and
                // the position string is start of the child position
                if ($numDots == $numParentDots + 1 && str_starts_with((string) $position, (string) $parentPosition)) {
                    // extract the last position part
                    $positionExp = explode('.', (string) $position);
                    $lastPositionPart = $positionExp[sizeof($positionExp) - 1];

                    // compare against our latest stored position
                    if (sprintf('%1$04d', $newRelativeNumericPosition) <= $lastPositionPart) {
                        $newRelativeNumericPosition = $lastPositionPart + 1;
                    }
                }
            }
        }

        // new position is relative to the parent position
        return ((0 != $parentPosition) ? $parentPosition . '.' : '') .
            sprintf('%1$04d', $newRelativeNumericPosition);
    }
}
