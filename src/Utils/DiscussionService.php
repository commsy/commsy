<?php
namespace App\Utils;

use Symfony\Component\Form\Form;

use App\Services\LegacyEnvironment;
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
     * @return \cs_discussion_item[]
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
     * @return \cs_discussion_item[]
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
            $this->discussionManager->showNoNotActivatedEntries();
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
    
    public function getDiscussion($itemId)
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
    
    public function showNoNotActivatedEntries()
    {
        $this->discussionManager->showNoNotActivatedEntries();
    }

    public function buildArticleTree($articleList, $root = null)
    {
        $tree = [];

        $article = $articleList->getFirst();
        while ($article) {

            $base =& $tree;
            $expLevel = explode('.', $article->getPosition());
            foreach ($expLevel as $level) {
                $base =& $base['children'][$level];
            }

            $base['item'] = $article;

            $article = $articleList->getNext();
        }

        return $tree;
    }
}
