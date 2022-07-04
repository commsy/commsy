<?php
namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_discussion_item;
use cs_discussionarticle_item;
use Symfony\Component\Form\FormInterface;

class DiscussionService
{
    private $legacyEnvironment;

    private $discussionManager;
    
    private $discussionArticleManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->discussionManager = $this->legacyEnvironment->getDiscussionManager();
        $this->discussionManager->reset();
        
        $this->discussionArticleManager = $this->legacyEnvironment->getDiscussionArticlesManager();
        $this->discussionArticleManager->reset();
    }

    /**
     * @param integer $roomId
     * @param integer $max
     * @param integer $start
     * @param string $sort
     * @return cs_discussion_item[]
     */
    public function getListDiscussions($roomId, $max = NULL, $start = NULL, $sort = NULL)
    {
        $this->discussionManager->setContextLimit($roomId);
        if ($max !== NULL && $start !== NULL) {
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
     * @param integer $roomId
     * @param integer[] $ids
     * @return cs_discussion_item[]
     */
    public function getDiscussionsById($roomId, $ids) {
        $this->discussionManager->setContextLimit($roomId);
        $this->discussionManager->setIDArrayLimit($ids);

        $this->discussionManager->select();
        $discussionList = $this->discussionManager->get();

        return $discussionList->to_array();
    }

    public function getCountArray($roomId)
    {
        $this->discussionManager->setContextLimit($roomId);
        $this->discussionManager->select();
        $countDiscussion = array();
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
            if ($formData['hide-deactivated-entries'] === 'only_activated') {
                $this->discussionManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
            } else if ($formData['hide-deactivated-entries'] === 'only_deactivated') {
                $this->discussionManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_DEACTIVATED);
            } else if ($formData['hide-deactivated-entries'] === 'all') {
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
    
    public function getDiscussion($itemId): ?cs_discussion_item
    {
        return $this->discussionManager->getItem($itemId);
    }
    
    public function getArticle($itemId)
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
            /** @var cs_discussionarticle_item $article */
            $base =& $tree;
            $expLevel = explode('.', $article->getPosition());
            foreach ($expLevel as $level) {
                $base =& $base['children'][$level];
            }

            $base['item'] = $article;
        }
        if (empty($tree)) {
            $tree['children'] = [];
        }
        return $tree;
    }
}
