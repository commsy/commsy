<?php

namespace App\Controller;

use App\Action\Copy\CopyAction;
use App\Action\Delete\DeleteAction;
use App\Action\Download\DownloadAction;
use App\Action\MarkRead\MarkReadGeneric;
use App\Event\CommsyEditEvent;
use App\Filter\DiscussionFilterType;
use App\Form\DataTransformer\DiscussionarticleTransformer;
use App\Form\DataTransformer\DiscussionTransformer;
use App\Form\DataTransformer\ItemTransformer;
use App\Form\Type\DiscussionArticleType;
use App\Form\Type\DiscussionType;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AssessmentService;
use App\Utils\CategoryService;
use App\Utils\DiscussionService;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use App\Utils\TopicService;
use cs_discussion_item;
use cs_room_item;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DiscussionController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId) and is_granted('RUBRIC_SEE', 'discussion')")
 */
class DiscussionController extends BaseController
{
    /**
     * @var DiscussionService
     */
    private DiscussionService $discussionService;

    /**
     * @required
     * @param DiscussionService $discussionService
     */
    public function setDiscussionService(DiscussionService $discussionService): void
    {
        $this->discussionService = $discussionService;
    }



    /**
     * @Route("/room/{roomId}/discussion/feed/{start}/{sort}")
     * @Template()
     * @param Request $request
     * @param AssessmentService $assessmentService
     * @param int $roomId
     * @param int $max
     * @param int $start
     * @param string $sort
     * @return array
     */
    public function feedAction(
        Request $request,
        AssessmentService $assessmentService,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = 'latest'
    ) {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $discussionFilter = $request->get('discussionFilter');
        if (!$discussionFilter) {
            $discussionFilter = $request->query->get('discussion_filter');
        }
        
        $roomItem = $this->getRoom($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        if ($discussionFilter) {
            $filterForm = $this->createFilterForm($roomItem);
            
            // manually bind values from the request
            $filterForm->submit($discussionFilter);
            
            // set filter conditions in discussion manager
            $this->discussionService->setFilterConditions($filterForm);
        }
        else {
            $this->discussionService->hideDeactivatedEntries();
        }

        // get discussion list from manager service
        $discussions = $this->discussionService->getListDiscussions($roomId, $max, $start, $sort);

        $this->get('session')->set('sortDiscussions', $sort);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        $allowedActions = array();
        foreach ($discussions as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save', 'delete');
            } else {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save');
            }
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $itemIds = array();
            foreach ($discussions as $discussion) {
                $itemIds[] = $discussion->getItemId();
            }
            $ratingList = $assessmentService->getListAverageRatings($itemIds);
        }

        return array(
            'roomId' => $roomId,
            'discussions' => $discussions,
            'readerList' => $readerList,
            'showRating' => $current_context->isAssessmentActive(),
            'showWorkflow' => $current_context->withWorkflow(),
            'ratingList' => $ratingList,
            'allowedActions' => $allowedActions,
        );
    }

    /**
     * @Route("/room/{roomId}/discussion")
     * @Template()
     * @param Request $request
     * @param int $roomId
     * @return array
     */
    public function listAction(
        Request $request,
        int $roomId
    ) {
        $roomItem = $this->getRoom($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the discussion manager service
        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in discussion manager
            $this->discussionService->setFilterConditions($filterForm);
        }
        else {
            $this->discussionService->hideDeactivatedEntries();
        }

        // get discussion list from manager service
        $itemsCountArray = $this->discussionService->getCountArray($roomId);

        $usageInfo = false;
        if ($roomItem->getUsageInfoTextForRubricInForm('discussion') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('discussion');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('discussion');
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'discussion',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => $roomItem->isAssessmentActive(),
            'showWorkflow' => $roomItem->withWorkflow(),
            'showHashTags' => $roomItem->withBuzzwords(),
            'showAssociations' => $roomItem->isAssociationShowExpanded(),
            'showCategories' => $roomItem->withTags(),
            'buzzExpanded' => $roomItem->isBuzzwordShowExpanded(),
            'catzExpanded' => $roomItem->isTagsShowExpanded(),
            'usageInfo' => $usageInfo,
            'isArchived' => $roomItem->isArchived(),
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
        );
        
    }

    /**
     * @Route("/room/{roomId}/discussion/print/{sort}", defaults={"sort" = "none"})
     * @Template()
     * @param Request $request
     * @param AssessmentService $assessmentService
     * @param PrintService $printService
     * @param int $roomId
     * @param string $sort
     * @return Response
     */
    public function printlistAction(
        Request $request,
        AssessmentService $assessmentService,
        PrintService $printService,
        int $roomId,
        string $sort
    ) {
        $roomItem = $this->getRoom($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }
        
        $filterForm = $this->createFilterForm($roomItem);

        $numAllDiscussions = $this->discussionService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in material manager
            $this->discussionService->setFilterConditions($filterForm);
        }

        // get discussion list from manager service
        if ($sort != "none") {
            $discussions = $this->discussionService->getListDiscussions($roomId, $numAllDiscussions, 0, $sort);
        }
        elseif ($this->get('session')->get('sortDates')) {
            $discussions = $this->discussionService->getListDiscussions($roomId, $numAllDiscussions, 0, $this->get('session')->get('sortDiscussions'));
        }
        else {
            $discussions = $this->discussionService->getListDiscussions($roomId, $numAllDiscussions, 0, 'date');
        }

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        foreach ($discussions as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $itemIds = array();
            foreach ($discussions as $discussion) {
                $itemIds[] = $discussion->getItemId();
            }
            $ratingList = $assessmentService->getListAverageRatings($itemIds);
        }

        // get material list from manager service
        $itemsCountArray = $this->discussionService->getCountArray($roomId);


        $html = $this->renderView('discussion/list_print.html.twig', [
            'roomId' => $roomId,
            'discussions' => $discussions,
            'readerList' => $readerList,
            'showRating' => $current_context->isAssessmentActive(),
            'showWorkflow' => $current_context->withWorkflow(),
            'ratingList' => $ratingList,
            'module' => 'discussion',
            'itemsCountArray' => $itemsCountArray,
            'showHashTags' => $roomItem->withBuzzwords(),
            'showAssociations' => $roomItem->withAssociations(),
            'showCategories' => $roomItem->withTags(),
            'buzzExpanded' => $roomItem->isBuzzwordShowExpanded(),
            'catzExpanded' => $roomItem->isTagsShowExpanded(),
        ]);

        return $printService->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/discussion/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId) and is_granted('RUBRIC_SEE', 'discussion')")
     * @param Request $request
     * @param TopicService $topicService
     * @param LegacyMarkup $legacyMarkup
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function detailAction(
        Request $request,
        TopicService $topicService,
        LegacyMarkup $legacyMarkup,
        int $roomId,
        int $itemId
    ) {
        $infoArray = $this->getDetailInfo($roomId, $itemId, $legacyMarkup);

        $alert = null;
        if ($infoArray['discussion']->isLocked()) {
            $alert['type'] = 'warning';
            $alert['content'] = $this->translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        return [
            'roomId' => $roomId,
            'discussion' => $infoArray['discussion'],
            'articleList' => $infoArray['articleList'],
            'articleTree' => $infoArray['articleTree'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'discussionList' => $infoArray['discussionList'],
            'counterPosition' => $infoArray['counterPosition'],
            'count' => $infoArray['count'],
            'firstItemId' => $infoArray['firstItemId'],
            'prevItemId' => $infoArray['prevItemId'],
            'nextItemId' => $infoArray['nextItemId'],
            'lastItemId' => $infoArray['lastItemId'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'userCount' => $infoArray['userCount'],
            'draft' => $infoArray['draft'],
            'showRating' => $infoArray['showRating'],
            'showHashtags' => $infoArray['showHashtags'],
            'showAssociations' => $infoArray['showAssociations'],
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'showCategories' => $infoArray['showCategories'],
            'user' => $infoArray['user'],
            'ratingArray' => $infoArray['ratingArray'],
            'roomCategories' => $infoArray['roomCategories'],
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
        ];
    }
    
    private function getDetailInfo (
        $roomId,
        $itemId,
        LegacyMarkup $legacyMarkup)
    {
        $infoArray = array();

        $discussion = $this->discussionService->getDiscussion($itemId);
        $articleList = $discussion->getAllArticles();

        $readerManager = $this->legacyEnvironment->getReaderManager();
        $noticedManager = $this->legacyEnvironment->getNoticedManager();

        // mark discussion as read / noticed
        $latestReader = $readerManager->getLatestReader($discussion->getItemID());
        if (empty($latestReader) || $latestReader['read_date'] < $discussion->getModificationDate()) {
            $readerManager->markRead($discussion->getItemID(), $discussion->getVersionID());
        }

        $latestNoticed = $noticedManager->getLatestNoticed($discussion->getItemID());
        if (empty($latestNoticed) || $latestNoticed['read_date'] < $discussion->getModificationDate()) {
            $noticedManager->markNoticed($discussion->getItemID(), $discussion->getVersionID());
        }

        // mark discussion articles as read / noticed
        /** @var \cs_discussionarticle_item $article */
        $article = $articleList->getFirst();
        while ($article) {
            $latestReader = $readerManager->getLatestReader($article->getItemID());
            if (empty($latestReader) || $latestReader['read_date'] < $article->getModificationDate()) {
                $readerManager->markRead($article->getItemID(), 0);
            }

            $latestNoticed = $noticedManager->getLatestNoticed($article->getItemID());
            if (empty($latestNoticed) || $latestNoticed['read_date'] < $article->getModificationDate()) {
                $noticedManager->markNoticed($article->getItemID(), 0);
            }

            $legacyMarkup->addFiles($this->itemService->getItemFileList($article->getItemID()));

            $article = $articleList->getNext();
        }

        $itemArray = array_merge([$discussion], $articleList->to_array());

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerManager = $this->legacyEnvironment->getReaderManager();

        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $user_list = $userManager->get();
        $all_user_count = $user_list->getCount();
        $read_count = 0;
        $read_since_modification_count = 0;

        $current_user = $user_list->getFirst();
        $id_array = array();
        while ( $current_user ) {
		   $id_array[] = $current_user->getItemID();
		   $current_user = $user_list->getNext();
		}
		$readerManager->getLatestReaderByUserIDArray($id_array, $discussion->getItemID());
		$current_user = $user_list->getFirst();
		while ( $current_user ) {
	   	    $current_reader = $readerManager->getLatestReaderForUserByID($discussion->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $discussion->getModificationDate() ) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
		    $current_user = $user_list->getNext();
		}

        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $this->readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
            
            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }
        
        $discussions = $this->discussionService->getListDiscussions($roomId);
        $discussionList = array();
        $counterBefore = 0;
        $counterAfter = 0;
        $counterPosition = 0;
        $foundDiscussion = false;
        $firstItemId = false;
        $prevItemId = false;
        $nextItemId = false;
        $lastItemId = false;
        foreach ($discussions as $tempDiscussion) {
            if (!$foundDiscussion) {
                if ($counterBefore > 5) {
                    array_shift($discussionList);
                } else {
                    $counterBefore++;
                }
                $discussionList[] = $tempDiscussion;
                if ($tempDiscussion->getItemID() == $discussion->getItemID()) {
                    $foundDiscussion = true;
                }
                if (!$foundDiscussion) {
                    $prevItemId = $tempDiscussion->getItemId();
                }
                $counterPosition++;
            } else {
                if ($counterAfter < 5) {
                    $discussionList[] = $tempDiscussion;
                    $counterAfter++;
                    if (!$nextItemId) {
                        $nextItemId = $tempDiscussion->getItemId();
                    }
                } else {
                    break;
                }
            }
        }
        if (!empty($discussions)) {
            if ($prevItemId) {
                $firstItemId = $discussions[0]->getItemId();
            }
            if ($nextItemId) {
                $lastItemId = $discussions[sizeof($discussions)-1]->getItemId();
            }
        }

        $ratingDetail = array();
        if ($current_context->isAssessmentActive()) {
            $assessmentService = $this->get('commsy_legacy.assessment_service');
            $ratingDetail = $assessmentService->getRatingDetail($discussion);
            $ratingAverageDetail = $assessmentService->getAverageRatingDetail($discussion);
            $ratingOwnDetail = $assessmentService->getOwnRatingDetail($discussion);
        }

        $reader_manager = $this->legacyEnvironment->getReaderManager();
        $noticed_manager = $this->legacyEnvironment->getNoticedManager();

        $item = $discussion;
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if(empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if(empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->get('commsy_legacy.category_service')->getTags($roomId);
            $discussionCategories = $discussion->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $discussionCategories);
        }

        $articleTree = $this->discussionService->buildArticleTree($articleList);

        $infoArray['discussion'] = $discussion;
        $infoArray['articleList'] = $articleList->to_array();
        $infoArray['articleTree'] = $articleTree;
        $infoArray['readerList'] = $readerList;
        $infoArray['modifierList'] = $modifierList;
        $infoArray['discussionList'] = $discussionList;
        $infoArray['counterPosition'] = $counterPosition;
        $infoArray['count'] = sizeof($discussions);
        $infoArray['firstItemId'] = $firstItemId;
        $infoArray['prevItemId'] = $prevItemId;
        $infoArray['nextItemId'] = $nextItemId;
        $infoArray['lastItemId'] = $lastItemId;
        $infoArray['readCount'] = $read_count;
        $infoArray['readSinceModificationCount'] = $read_since_modification_count;
        $infoArray['userCount'] = $all_user_count;
        $infoArray['draft'] = $this->itemService->getItem($itemId)->isDraft();
        $infoArray['showRating'] = $current_context->isAssessmentActive();
        $infoArray['user'] = $this->legacyEnvironment->getCurrentUserItem();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();
        $infoArray['buzzExpanded'] = $current_context->isBuzzwordShowExpanded();
        $infoArray['catzExpanded'] = $current_context->isTagsShowExpanded();
        $infoArray['showAssociations'] = $current_context->isAssociationShowExpanded();
        $infoArray['ratingArray'] = $current_context->isAssessmentActive() ? [
            'ratingDetail' => $ratingDetail,
            'ratingAverageDetail' => $ratingAverageDetail,
            'ratingOwnDetail' => $ratingOwnDetail,
        ] : [];
        $infoArray['roomCategories'] = $categories;
        
        return $infoArray;
    }
    
    private function getTagDetailArray ($baseCategories, $itemCategories) {
        $result = array();
        $tempResult = array();
        $addCategory = false;
        foreach ($baseCategories as $baseCategory) {
            if (!empty($baseCategory['children'])) {
                $tempResult = $this->getTagDetailArray($baseCategory['children'], $itemCategories);
            }
            if (!empty($tempResult)) {
                $addCategory = true;
            }

            $foundCategory = false;
            foreach ($itemCategories as $itemCategory) {
                if ($baseCategory['item_id'] == $itemCategory['id']) {
                    if ($addCategory) {
                        $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult);
                    } else {
                        $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id']);
                    }
                    $foundCategory = true;
                }
            }
            if (!$foundCategory) {
                if ($addCategory) {
                    $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult);
                }
            }
            $tempResult = array();
            $addCategory = false;
        }
        return $result;
    }

    /**
     * @Route("/room/{roomId}/discussion/create")
     * @param int $roomId
     * @return RedirectResponse
     * @Security("is_granted('ITEM_EDIT', 'NEW') and is_granted('RUBRIC_SEE', 'discussion')")
     */
    public function createAction(
        int $roomId)
    {
        // create a new discussion
        $discussionItem = $this->discussionService->getNewDiscussion();
        $discussionItem->setDraftStatus(1);
        $discussionItem->setPrivateEditing('0'); // editable only by creator
        $discussionItem->save();

        return $this->redirectToRoute('app_discussion_detail', [
            'roomId' => $roomId,
            'itemId' => $discussionItem->getItemId(),
        ]);
    }

    /**
     * @Route("/room/{roomId}/discussion/{itemId}/print")
     * @param PrintService $printService
     * @param LegacyMarkup $legacyMarkup
     * @param int $roomId
     * @param int $itemId
     * @return Response
     */
    public function printAction(
        PrintService $printService,
        LegacyMarkup $legacyMarkup,
        int $roomId,
        int $itemId
    ) {
        $infoArray = $this->getDetailInfo($roomId, $itemId, $legacyMarkup);

        $html = $this->renderView('discussion/detail_print.html.twig', [
            'roomId' => $roomId,
            'discussion' => $infoArray['discussion'],
            'articleList' => $infoArray['articleList'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'discussionList' => $infoArray['discussionList'],
            'counterPosition' => $infoArray['counterPosition'],
            'count' => $infoArray['count'],
            'firstItemId' => $infoArray['firstItemId'],
            'prevItemId' => $infoArray['prevItemId'],
            'nextItemId' => $infoArray['nextItemId'],
            'lastItemId' => $infoArray['lastItemId'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'userCount' => $infoArray['userCount'],
            'draft' => $infoArray['draft'],
            'showRating' => $infoArray['showRating'],
            'showHashtags' => $infoArray['showHashtags'],
            'showAssociations' => $infoArray['showAssociations'],
            'showCategories' => $infoArray['showCategories'],
            'buzzExpanded' => $infoArray['buzzExpanded'],
            'catzExpanded' => $infoArray['catzExpanded'],
            'user' => $infoArray['user'],
            'ratingArray' => $infoArray['ratingArray'],
            'roomCategories' => $infoArray['roomCategories'],
        ]);

        return $printService->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/discussion/{itemId}/createarticle")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'discussion')")
     * @param Request $request
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function createArticleAction(
        Request $request,
        DiscussionTransformer $transformer,
        int $roomId,
        int $itemId
    ) {
        $discussion = $this->discussionService->getDiscussion($itemId);
        $articleList = $discussion->getAllArticles();

        // calculate new position
        if ($request->query->has('answerTo')) {
            // get parent position
            $parentId = $request->query->get('answerTo');
            $daManager = $this->legacyEnvironment->getDiscussionArticlesManager();
            $parentArticle = $daManager->getItem($parentId);
            $parentPosition = $parentArticle->getPosition();
        } else {
            $parentId = 0;
            $parentPosition = 0;
        }

        /**
         * TODO: Instead of iteration all articles to find the latest in the parents branch
         * it would be much better to ask only for all childs of an article or directly
         * for the latest position
         */
        $numParentDots = substr_count($parentPosition, '.');
        $article = $articleList->getFirst();
        $newRelativeNumericPosition = 1;
        while ($article) {
            $position = $article->getPosition();

            $numDots = substr_count($position, '.');

            if ($parentPosition == 0) {
                if ($numDots == 0) {
                    // compare against our latest stored position
                    if (sprintf('%1$04d', $newRelativeNumericPosition) <= $position) {
                        $newRelativeNumericPosition = $position + 1;
//                        $newRelativeNumericPosition++;
                    }
                }
            } else {
                // if the parent position is one level above the child ones and
                // the position string is start of the child position
                if ($numDots == $numParentDots + 1 && substr($position, 0, strlen($parentPosition)) == $parentPosition) {
                    // extract the last position part
                    $positionExp = explode('.', $position);
                    $lastPositionPart = $positionExp[sizeof($positionExp) - 1];

                    // compare against our latest stored position
                    if (sprintf('%1$04d', $newRelativeNumericPosition) <= $lastPositionPart) {
                        $newRelativeNumericPosition = $lastPositionPart + 1;
                    }
                }
            }

            $article = $articleList->getNext();
        }

        // new position is relative to the parent position
        $newPosition = '';
        if ($parentPosition != 0) {
            $newPosition .= $parentPosition . '.';
        }
        $newPosition .=  sprintf('%1$04d', $newRelativeNumericPosition);

        $article = $this->discussionService->getNewArticle();
        $article->setDraftStatus(1);
        $article->setDiscussionID($itemId);
        $article->setPosition($newPosition);
        $article->save();

        $formData = $transformer->transform($article);
        $form = $this->createForm(DiscussionArticleType::class, $formData, [
            'action' => $this->generateUrl('app_discussion_savearticle', [
                'roomId' => $roomId,
                'itemId' => $article->getItemID()
            ]),
            'placeholderText' => '['.$this->translator->trans('insert title').']',
        ]);

        return [
            'form' => $form->createView(),
            'articleList' => $articleList,
            'discussion' => $discussion,
            'article' => $article,
            'modifierList' => array(),
            'userCount' => 0,
            'readCount' => 0,
            'readSinceModificationCount' => 0,
            'currentUser' => $this->legacyEnvironment->getCurrentUserItem(),
            'parentId' => $parentId,
        ];
    }

    /**
     * @Route("/room/{roomId}/discussion/{itemId}/editarticles")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'discussion')")
     * @param int $itemId
     * @return array
     */
    public function editArticlesAction(
        int $itemId)
    {
        $discussion = $this->discussionService->getDiscussion($itemId);

        $articlesList = $discussion->getAllArticles()->to_array();

        return array(
            'articlesList' => $articlesList,
            'discussion' => $discussion
        );
    }

    /**
     * @Route("/room/{roomId}/discussion/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'discussion')")
     * @param Request $request
     * @param ItemController $itemController
     * @param CategoryService $categoryService
     * @param DiscussionTransformer $discussionTransformer
     * @param DiscussionarticleTransformer $discussionarticleTransformer
     * @param int $roomId
     * @param int $itemId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        ItemController $itemController,
        CategoryService $categoryService,
        DiscussionTransformer $discussionTransformer,
        DiscussionarticleTransformer $discussionarticleTransformer,
        int $roomId,
        int $itemId
    ) {
        $item = $this->itemService->getItem($itemId);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();
        
        $formData = array();
        $discussionItem = NULL;
        $discussionArticleItem = NULL;

        $isDraft = $item->isDraft();

        $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
        $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();

        $transformer = NULL;

        if ($item->getItemType() == 'discussion') {
            $transformer = $discussionTransformer;
        } else if ($item->getItemType() == 'discarticle') {
            $transformer = $discussionarticleTransformer;
        }

        if ($item->getItemType() == 'discussion') {
            // get discussion from DiscussionService
            /** @var \cs_discussion_item $discussionItem */
            $discussionItem = $this->discussionService->getDiscussion($itemId);
            $discussionItem->setDraftStatus($isDraft);
            if (!$discussionItem) {
                throw $this->createNotFoundException('No discussion found for id ' . $itemId);
            }
            $formData = $transformer->transform($discussionItem);
            $formData['categoriesMandatory'] = $categoriesMandatory;
            $formData['hashtagsMandatory'] = $hashtagsMandatory;
            $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
            $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId, $this->legacyEnvironment);
            $formData['draft'] = $isDraft;
            $form = $this->createForm(DiscussionType::class, $formData, array(
                'action' => $this->generateUrl('app_discussion_edit', array(
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                )),
                'placeholderText' => '['.$this->translator->trans('insert title').']',
                'categoryMappingOptions' => [
                    'categories' => $itemController->getCategories($roomId, $categoryService)
                ],
                'hashtagMappingOptions' => [
                    'hashtags' => $itemController->getHashtags($roomId, $this->legacyEnvironment),
                    'hashTagPlaceholderText' => $this->translator->trans('Hashtag', [], 'hashtag'),
                    'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId])
                ],

            ));
        } else if ($item->getItemType() == 'discarticle') {
            // get section from DiscussionService
            $discussionArticleItem = $this->discussionService->getArticle($itemId);
            if (!$discussionArticleItem) {
                throw $this->createNotFoundException('No discussion article found for id ' . $itemId);
            }
            $formData = $transformer->transform($discussionArticleItem);
            $form = $this->createForm(DiscussionArticleType::class, $formData, array(
                'placeholderText' => '['.$this->translator->trans('insert title').']',
                'categories' => $itemController->getCategories($roomId, $categoryService),
                'hashtags' => $itemController->getHashtags($roomId, $this->legacyEnvironment),
                'hashTagPlaceholderText' => $this->translator->trans('Hashtag', [], 'hashtag'),
                'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
            ));
        }
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                if ($item->getItemType() == 'discussion') {
                    $discussionItem = $transformer->applyTransformation($discussionItem, $form->getData());
                    // update modifier
                    $discussionItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                    // set linked hashtags and categories
                    $formData = $form->getData();
                    if ($categoriesMandatory) {
                        $discussionItem->setTagListByID($formData['category_mapping']['categories']);
                    }
                    if ($hashtagsMandatory) {
                        $discussionItem->setBuzzwordListByID($formData['hashtag_mapping']['hashtags']);
                }

                    $discussionItem->save();                
                } else if ($item->getItemType() == 'discarticle') {
                    $discussionArticleItem = $transformer->applyTransformation($discussionArticleItem, $form->getData());
                    // update modifier
                    $discussionArticleItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());
                    $discussionArticleItem->save();
                }
                
                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            return $this->redirectToRoute('app_discussion_save', array('roomId' => $roomId, 'itemId' => $itemId));
            
            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        if ($item->getItemType() == 'discussion') {
            $this->eventDispatcher->dispatch(new CommsyEditEvent($discussionItem), CommsyEditEvent::EDIT);
        } else {
            $discussionItem = $this->discussionService->getDiscussion($discussionArticleItem->getDiscussionID());
            $this->eventDispatcher->dispatch(new CommsyEditEvent($discussionItem), CommsyEditEvent::EDIT);
        }


        return array(
            'form' => $form->createView(),
            'discussion' => $discussionItem,
            'discussionArticle' => $discussionArticleItem,
            'isDraft' => $isDraft,
            'showHashtags' => $hashtagsMandatory,
            'showCategories' => $categoriesMandatory,
            'currentUser' => $this->legacyEnvironment->getCurrentUserItem(),
        );
    }

    /**
     * @Route("/room/{roomId}/discussion/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'discussion')")
     * @param int $roomId
     * @param int $itemId
     * @return array
     */
    public function saveAction(
        int $roomId,
        int $itemId
    ) {
        $item = $this->itemService->getItem($itemId);

        if ($item->getItemType() == 'discussion') {
            $typedItem = $this->discussionService->getDiscussion($itemId);
        } else if ($item->getItemType() == 'discarticle') {
            $typedItem = $this->discussionService->getArticle($itemId);
        }
        
        $itemArray = array($typedItem);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }
        
        $readerManager = $this->legacyEnvironment->getReaderManager();
        
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $user_list = $userManager->get();
        $all_user_count = $user_list->getCount();
        $read_count = 0;
        $read_since_modification_count = 0;

        $current_user = $user_list->getFirst();
        $id_array = array();
        while ( $current_user ) {
		   $id_array[] = $current_user->getItemID();
		   $current_user = $user_list->getNext();
		}
		$readerManager->getLatestReaderByUserIDArray($id_array,$typedItem->getItemID());
		$current_user = $user_list->getFirst();
		while ( $current_user ) {
	   	    $current_reader = $readerManager->getLatestReaderForUserByID($typedItem->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $typedItem->getModificationDate() ) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
		    $current_user = $user_list->getNext();
		}
        
        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $this->readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
            
            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        if ($item->getItemType() == 'discussion') {
            $this->eventDispatcher->dispatch(new CommsyEditEvent($typedItem), CommsyEditEvent::SAVE);
        } else {
            $discussionItem = $this->discussionService->getDiscussion($typedItem->getDiscussionID());
            $this->eventDispatcher->dispatch(new CommsyEditEvent($discussionItem), CommsyEditEvent::SAVE);
        }

        return array(
            'roomId' => $roomId,
            'item' => $typedItem,
            'modifierList' => $modifierList,
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
        );
    }

    /**
     * @Route("/room/{roomId}/discussion/{itemId}/rating/{vote}")
     * @Template()
     * @param AssessmentService $assessmentService
     * @param int $roomId
     * @param int $itemId
     * @param $vote
     * @return array
     */
    public function ratingAction(
        AssessmentService $assessmentService,
        int $roomId,
        int $itemId,
        $vote
    ) {
        $discussion = $this->discussionService->getDiscussion($itemId);
        if ($vote != 'remove') {
            $assessmentService->rateItem($discussion, $vote);
        } else {
            $assessmentService->removeRating($discussion);
        }
        $ratingDetail = $assessmentService->getRatingDetail($discussion);
        $ratingAverageDetail = $assessmentService->getAverageRatingDetail($discussion);
        $ratingOwnDetail = $assessmentService->getOwnRatingDetail($discussion);
        
        return array(
            'roomId' => $roomId,
            'discussion' => $discussion,
            'ratingArray' =>  array(
                'ratingDetail' => $ratingDetail,
                'ratingAverageDetail' => $ratingAverageDetail,
                'ratingOwnDetail' => $ratingOwnDetail,
            ),
        );
    }

    /**
     * @Route("/room/{roomId}/discussion/{itemId}/savearticle")
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'discussion')")
     * @param Request $request
     * @param DiscussionTransformer $transformer
     * @param int $roomId
     * @param int $itemId
     * @return RedirectResponse
     */
    public function saveArticleAction(
        Request $request,
        DiscussionTransformer $transformer,
        int $roomId,
        int $itemId
    ) {
        $item = $this->itemService->getItem($itemId);
        $article = $this->discussionService->getArticle($itemId);
        $formData = $transformer->transform($article);

        $form = $this->createForm(DiscussionArticleType::class, $formData, array(
            'action' => $this->generateUrl('app_discussion_savearticle', array('roomId' => $roomId, 'itemId' => $article->getItemID())),
            'placeholderText' => '['.$this->translator->trans('insert title').']',
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                // update title
                $article->setTitle($form->getData()['title']);

                if ($form->getData()['permission']) {
                    $article->setPrivateEditing('0'); // editable only by creator
                } else {
                    $article->setPrivateEditing('1'); // editable by everyone
                }

                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }

                // update modifier
                $article->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                $article->save();
                
            } else if ($form->get('cancel')->isClicked()) {
                // remove not saved item
                $article->delete();

                $article->save();
            }
            return $this->redirectToRoute('app_discussion_detail', array('roomId' => $roomId, 'itemId' => $article->getDiscussionID()));
        }
    }

    /**
     * @Route("/room/{roomId}/discussion/download")
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function downloadAction(
        Request $request,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get(DownloadAction::class);
        return $action->execute($room, $items);
    }

    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/discussion/xhr/markread", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function xhrMarkReadAction(
        Request $request,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.mark_read.generic');
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/discussion/xhr/copy", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function xhrCopyAction(
        Request $request,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get(CopyAction::class);
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/discussion/xhr/delete", condition="request.isXmlHttpRequest()")
     * @param Request $request
     * @param int $roomId
     * @return
     * @throws Exception
     */
    public function xhrDeleteAction(
        Request $request,
        DeleteAction $action,
        int $roomId
    ) {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @param cs_room_item $room
     * @return FormInterface
     */
    private function createFilterForm(
        cs_room_item $room
    ) {
        // setup filter form default values
        $defaultFilterValues = [
            'hide-deactivated-entries' => 'only_activated',
        ];

        return $this->createForm(DiscussionFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('app_discussion_list', [
                'roomId' => $room->getItemID(),
            ]),
            'hasHashtags' => $room->withBuzzwords(),
            'hasCategories' => $room->withTags(),
        ]);
    }

    /**
     * @param Request $request
     * @param cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return cs_discussion_item[]
     */
    public function getItemsByFilterConditions(
        Request $request,
        $roomItem,
        $selectAll,
        $itemIds = []
    ) {
        // get the discussion manager service
        if ($selectAll) {
            if ($request->query->has('discussion_filter')) {
                $currentFilter = $request->query->get('discussion_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $this->discussionService->setFilterConditions($filterForm);
            } else {
                $this->discussionService->hideDeactivatedEntries();
            }

            return $this->discussionService->getListDiscussions($roomItem->getItemID());
        } else {
            return $this->discussionService->getDiscussionsById($roomItem->getItemID(), $itemIds);
        }
    }
}
