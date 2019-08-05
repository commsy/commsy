<?php

namespace App\Controller;

use App\Action\Copy\CopyAction;
use App\Action\Download\DownloadAction;
use App\Event\CommsyEditEvent;
use App\Filter\DiscussionFilterType;
use App\Form\Type\DiscussionArticleType;
use App\Form\Type\DiscussionType;
use App\Services\PrintService;
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
     * @Route("/room/{roomId}/discussion/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = '', Request $request)
    {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $discussionFilter = $request->get('discussionFilter');
        if (!$discussionFilter) {
            $discussionFilter = $request->query->get('discussion_filter');
        }
        
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the material manager service
        $discussionService = $this->get('commsy_legacy.discussion_service');
        
        if ($discussionFilter) {
            $filterForm = $this->createFilterForm($roomItem);
            
            // manually bind values from the request
            $filterForm->submit($discussionFilter);
            
            // set filter conditions in discussion manager
            $discussionService->setFilterConditions($filterForm);
        }
        else {
            $discussionService->showNoNotActivatedEntries();
        }

        // get discussion list from manager service
        $discussions = $discussionService->getListDiscussions($roomId, $max, $start, $sort);

        $this->get('session')->set('sortDiscussions', $sort);

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        $allowedActions = array();
        foreach ($discussions as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save', 'delete');
            } else {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save');
            }
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $assessmentService = $this->get('commsy_legacy.assessment_service');
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
     */
    public function listAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the discussion manager service
        $discussionService = $this->get('commsy_legacy.discussion_service');
        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in discussion manager
            $discussionService->setFilterConditions($filterForm);
        }
        else {
            $discussionService->showNoNotActivatedEntries();
        }

        // get discussion list from manager service
        $itemsCountArray = $discussionService->getCountArray($roomId);

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
            'language' => $legacyEnvironment->getCurrentUser()->_environment->current_context->_data['extras']['LANGUAGE'],
            'showAssociations' => $roomItem->isAssociationShowExpanded(),
            'showCategories' => $roomItem->withTags(),
            'buzzExpanded' => $roomItem->isBuzzwordShowExpanded(),
            'catzExpanded' => $roomItem->isTagsShowExpanded(),
            'usageInfo' => $usageInfo,
            'isArchived' => $roomItem->isArchived(),
            'user' => $legacyEnvironment->getCurrentUserItem(),
        );
        
    }

    /**
     * @Route("/room/{roomId}/discussion/print/{sort}", defaults={"sort" = "none"})
     * @Template()
     */
    public function printlistAction($roomId, Request $request, $sort, PrintService $printService)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }
        
        $filterForm = $this->createFilterForm($roomItem);

        // get the material manager service
        $discussionService = $this->get('commsy_legacy.discussion_service');
        $numAllDiscussions = $discussionService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in material manager
            $discussionService->setFilterConditions($filterForm);
        }

        // get discussion list from manager service
        if ($sort != "none") {
            $discussions = $discussionService->getListDiscussions($roomId, $numAllDiscussions, 0, $sort);
        }
        elseif ($this->get('session')->get('sortDates')) {
            $discussions = $discussionService->getListDiscussions($roomId, $numAllDiscussions, 0, $this->get('session')->get('sortDiscussions'));
        }
        else {
            $discussions = $discussionService->getListDiscussions($roomId, $numAllDiscussions, 0, 'date');
        }

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerList = array();
        foreach ($discussions as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = array();
        if ($current_context->isAssessmentActive()) {
            $assessmentService = $this->get('commsy_legacy.assessment_service');
            $itemIds = array();
            foreach ($discussions as $discussion) {
                $itemIds[] = $discussion->getItemId();
            }
            $ratingList = $assessmentService->getListAverageRatings($itemIds);
        }

        // get material list from manager service
        $itemsCountArray = $discussionService->getCountArray($roomId);


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
            'language' => $legacyEnvironment->getCurrentUser()->_environment->current_context->_data['extras']['LANGUAGE'],
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
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        $infoArray = $this->getDetailInfo($roomId, $itemId);

        $alert = null;
        if ($infoArray['discussion']->isLocked()) {
            $translator = $this->get('translator');

            $alert['type'] = 'warning';
            $alert['content'] = $translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $topicService = $this->get('commsy_legacy.topic_service');
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
    
    private function getDetailInfo ($roomId, $itemId) {
        $infoArray = array();
        
        $discussionService = $this->get('commsy_legacy.discussion_service');
        $itemService = $this->get('commsy_legacy.item_service');

        $discussion = $discussionService->getDiscussion($itemId);
        $articleList = $discussion->getAllArticles();

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $readerManager = $legacyEnvironment->getReaderManager();
        $noticedManager = $legacyEnvironment->getNoticedManager();

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

            $markupService = $this->get('commsy_legacy.markup');
            $itemService = $this->get('commsy_legacy.item_service');
            $markupService->addFiles($itemService->getItemFileList($article->getItemID()));

            $article = $articleList->getNext();
        }

        $itemArray = array_merge([$discussion], $articleList->to_array());

        $current_context = $legacyEnvironment->getCurrentContextItem();

        $readerManager = $legacyEnvironment->getReaderManager();

        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setContextLimit($legacyEnvironment->getCurrentContextID());
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
        $readerService = $this->get('commsy_legacy.reader_service');
        
        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
            
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        $discussions = $discussionService->getListDiscussions($roomId);
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

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $reader_manager = $legacyEnvironment->getReaderManager();
        $noticed_manager = $legacyEnvironment->getNoticedManager();

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

        $articleTree = $this->get('commsy_legacy.discussion_service')->buildArticleTree($articleList);

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
        $infoArray['draft'] = $itemService->getItem($itemId)->isDraft();
        $infoArray['showRating'] = $current_context->isAssessmentActive();
        $infoArray['user'] = $legacyEnvironment->getCurrentUserItem();
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
     */
    public function createAction($roomId, Request $request)
    {
        $discussionService = $this->get('commsy_legacy.discussion_service');
        
        // create a new discussion
        $discussionItem = $discussionService->getNewDiscussion();
        $discussionItem->setDraftStatus(1);
        $discussionItem->setPrivateEditing('1');
        $discussionItem->save();

        return $this->redirectToRoute('app_discussion_detail', [
            'roomId' => $roomId,
            'itemId' => $discussionItem->getItemId(),
        ]);
    }
    
    /**
     * @Route("/room/{roomId}/discussion/{itemId}/print")
     */
    public function printAction($roomId, $itemId, PrintService $printService)
    {
        $infoArray = $this->getDetailInfo($roomId, $itemId);

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
     */
    public function createArticleAction($roomId, $itemId, Request $request)
    {
        $translator = $this->get('translator');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $discussionService = $this->get('commsy_legacy.discussion_service');
        $transformer = $this->get('commsy_legacy.transformer.discussion');

        $discussion = $discussionService->getDiscussion($itemId);

        $articleList = $discussion->getAllArticles();

        // calculate new position
        if ($request->query->has('answerTo')) {
            // get parent position
            $parentId = $request->query->get('answerTo');
            $daManager = $legacyEnvironment->getDiscussionArticlesManager();
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

        $article = $discussionService->getNewArticle();
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
            'placeholderText' => '['.$translator->trans('insert title').']',
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
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
            'parentId' => $parentId,
        ];
    }
    
        /**
     * @Route("/room/{roomId}/discussion/{itemId}/editarticles")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'discussion')")
     */
    public function editArticlesAction($roomId, $itemId, Request $request)
    {
        $discussionService = $this->get('commsy_legacy.discussion_service');

        $discussion = $discussionService->getDiscussion($itemId);

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
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $discussionService = $this->get('commsy_legacy.discussion_service');

        $translator = $this->get('translator');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();
        
        $formData = array();
        $discussionItem = NULL;

        $isDraft = $item->isDraft();

        $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
        $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();

        $transformer = NULL;

        if ($item->getItemType() == 'discussion') {
            $transformer = $this->get('commsy_legacy.transformer.discussion');
        } else if ($item->getItemType() == 'discarticle') {
            $transformer = $this->get('commsy_legacy.transformer.discarticle');
        }

        $itemController = $this->get('commsy.item_controller');
        if ($item->getItemType() == 'discussion') {
            // get discussion from DiscussionService
            $discussionItem = $discussionService->getDiscussion($itemId);
            if (!$discussionItem) {
                throw $this->createNotFoundException('No discussion found for id ' . $itemId);
            }
            $formData = $transformer->transform($discussionItem);
            $formData['categoriesMandatory'] = $categoriesMandatory;
            $formData['hashtagsMandatory'] = $hashtagsMandatory;
            $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
            $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId, $legacyEnvironment);
            $formData['draft'] = $isDraft;
            $formData['language'] = $legacyEnvironment->getCurrentUser()->_environment->current_context->_data['extras']['LANGUAGE'];
            $form = $this->createForm(DiscussionType::class, $formData, array(
                'action' => $this->generateUrl('app_discussion_edit', array(
                    'roomId' => $roomId,
                    'itemId' => $itemId,
                )),
                'placeholderText' => '['.$translator->trans('insert title').']',
                'categoryMappingOptions' => [
                    'categories' => $itemController->getCategories($roomId, $this->get('commsy_legacy.category_service'))
                ],
                'hashtagMappingOptions' => [
                    'hashtags' => $itemController->getHashtags($roomId, $legacyEnvironment),
                    'hashTagPlaceholderText' => $translator->trans('Hashtag', [], 'hashtag'),
                    'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId])
                ],

            ));
        } else if ($item->getItemType() == 'discarticle') {
            // get section from DiscussionService
            $discussionArticleItem = $discussionService->getArticle($itemId);
            if (!$discussionArticleItem) {
                throw $this->createNotFoundException('No discussion article found for id ' . $itemId);
            }
            $formData = $transformer->transform($discussionArticleItem);
            $form = $this->createForm(DiscussionArticleType::class, $formData, array(
                'placeholderText' => '['.$translator->trans('insert title').']',
                'categories' => $itemController->getCategories($roomId, $this->get('commsy_legacy.category_service')),
                'hashtags' => $itemController->getHashtags($roomId, $legacyEnvironment),
                'hashTagPlaceholderText' => $translator->trans('Hashtag', [], 'hashtag'),
                'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
                'language' => $legacyEnvironment->getCurrentUser()->_environment->current_context->_data['extras']['LANGUAGE'],
            ));
        }
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                if ($item->getItemType() == 'discussion') {
                    $discussionItem = $transformer->applyTransformation($discussionItem, $form->getData());
                    // update modifier
                    $discussionItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

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
                    $discussionArticleItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
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
            $this->get('event_dispatcher')->dispatch('commsy.edit', new CommsyEditEvent($discussionItem));
        } else {
            $discussionItem = $discussionService->getDiscussion($discussionArticleItem->getDiscussionID());
            $this->get('event_dispatcher')->dispatch('commsy.edit', new CommsyEditEvent($discussionItem));
        }
        return array(
            'form' => $form->createView(),
            'discussion' => $discussionItem,
            'isDraft' => $isDraft,
            'showHashtags' => $hashtagsMandatory,
            'showCategories' => $categoriesMandatory,
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
            'language' => $legacyEnvironment->getCurrentUser()->_environment->current_context->_data['extras']['LANGUAGE'],
        );
    }
    
    /**
     * @Route("/room/{roomId}/discussion/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId) and is_granted('RUBRIC_SEE', 'discussion')")
     */
    public function saveAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $discussionService = $this->get('commsy_legacy.discussion_service');
        
        if ($item->getItemType() == 'discussion') {
            $typedItem = $discussionService->getDiscussion($itemId);
        } else if ($item->getItemType() == 'discarticle') {
            $typedItem = $discussionService->getArticle($itemId);
        }
        
        $itemArray = array($typedItem);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $readerManager = $legacyEnvironment->getReaderManager();
        
        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setContextLimit($legacyEnvironment->getCurrentContextID());
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
        $readerService = $this->get('commsy_legacy.reader_service');
        
        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
            
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }

        if ($item->getItemType() == 'discussion') {
            $this->get('event_dispatcher')->dispatch('commsy.save', new CommsyEditEvent($typedItem));
        } else {
            $discussionItem = $discussionService->getDiscussion($typedItem->getDiscussionID());
            $this->get('event_dispatcher')->dispatch('commsy.save', new CommsyEditEvent($discussionItem));
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
     **/
    public function ratingAction($roomId, $itemId, $vote, Request $request)
    {
        $discussionService = $this->get('commsy_legacy.discussion_service');
        $discussion = $discussionService->getDiscussion($itemId);
        
        $assessmentService = $this->get('commsy_legacy.assessment_service');
        if ($vote != 'remove') {
            $assessmentService->rateItem($discussion, $vote);
        } else {
            $assessmentService->removeRating($discussion);
        }
        
        $assessmentService = $this->get('commsy_legacy.assessment_service');
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
     */
    public function saveArticleAction($roomId, $itemId, Request $request)
    {
        $discussionService = $this->get('commsy_legacy.discussion_service');
        $transformer = $this->get('commsy_legacy.transformer.discussion');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);

        $translator = $this->get('translator');

        // get section
        $article = $discussionService->getArticle($itemId);

        $formData = $transformer->transform($article);

        $form = $this->createForm(DiscussionArticleType::class, $formData, array(
            'action' => $this->generateUrl('app_discussion_savearticle', array('roomId' => $roomId, 'itemId' => $article->getItemID())),
            'placeholderText' => '['.$translator->trans('insert title').']',
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                // update title
                $article->setTitle($form->getData()['title']);

                if ($form->getData()['permission']) {
                    $article->setPrivateEditing('0');
                } else {
                    $article->setPrivateEditing('1');
                }

                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }

                // update modifier
                $article->setModificatorItem($legacyEnvironment->getCurrentUserItem());

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
     * @throws \Exception
     */
    public function downloadAction($roomId, Request $request)
    {
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
     * @throws \Exception
     */
    public function xhrMarkReadAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.mark_read.generic');
        return $action->execute($room, $items);

    }

    /**
     * @Route("/room/{roomId}/discussion/xhr/copy", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrCopyAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get(CopyAction::class);
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/discussion/xhr/delete", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrDeleteAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.delete.generic');
        return $action->execute($room, $items);
    }

    /**
     * @param \cs_room_item $room
     * @return FormInterface
     */
    private function createFilterForm($room)
    {
        // setup filter form default values
        $defaultFilterValues = [
            'hide-deactivated-entries' => true,
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
     * @param \cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return \cs_discussion_item[]
     */
    public function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = [])
    {
        // get the discussion manager service
        $discussionService = $this->get('commsy_legacy.discussion_service');

        if ($selectAll) {
            if ($request->query->has('discussion_filter')) {
                $currentFilter = $request->query->get('discussion_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $discussionService->setFilterConditions($filterForm);
            } else {
                $discussionService->showNoNotActivatedEntries();
            }

            return $discussionService->getListDiscussions($roomItem->getItemID());
        } else {
            return $discussionService->getDiscussionsById($roomItem->getItemID(), $itemIds);
        }
    }
}
