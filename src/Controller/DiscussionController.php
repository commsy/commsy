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

use App\Action\Activate\ActivateAction;
use App\Action\Activate\DeactivateAction;
use App\Action\Delete\DeleteAction;
use App\Action\Download\DownloadAction;
use App\Action\Mark\CategorizeAction;
use App\Action\Mark\HashtagAction;
use App\Action\Mark\MarkAction;
use App\Action\MarkRead\MarkReadAction;
use App\Event\CommsyEditEvent;
use App\Filter\DiscussionFilterType;
use App\Form\DataTransformer\DiscussionarticleTransformer;
use App\Form\DataTransformer\DiscussionTransformer;
use App\Form\Type\DiscussionAnswerType;
use App\Form\Type\DiscussionType;
use App\Security\Authorization\Voter\ItemVoter;
use App\Services\LegacyMarkup;
use App\Services\PrintService;
use App\Utils\AssessmentService;
use App\Utils\CategoryService;
use App\Utils\DiscussionService;
use App\Utils\LabelService;
use App\Utils\TopicService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Class DiscussionController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
#[IsGranted('RUBRIC_DISCUSSION')]
class DiscussionController extends BaseController
{
    private DiscussionService $discussionService;
    private SessionInterface $session;

    #[Required]
    public function setDiscussionService(DiscussionService $discussionService): void
    {
        $this->discussionService = $discussionService;
    }

    #[Required]
    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }

    #[Route(path: '/room/{roomId}/discussion/feed/{start}/{sort}')]
    public function feed(
        Request $request,
        AssessmentService $assessmentService,
        int $roomId,
        int $max = 10,
        int $start = 0,
        string $sort = ''
    ): Response {
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
        } else {
            $this->discussionService->hideDeactivatedEntries();
        }

        if (empty($sort)) {
            $sort = $this->session->get('sortDiscussions', 'latest');
        }
        $this->session->set('sortDiscussions', $sort);

        // get discussion list from manager service
        $discussions = $this->discussionService->getListDiscussions($roomId, $max, $start, $sort);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = [];
        $allowedActions = [];
        foreach ($discussions as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = ['markread', 'mark', 'categorize', 'hashtag', 'activate', 'deactivate', 'save', 'delete'];
            } else {
                $allowedActions[$item->getItemID()] = ['markread', 'mark', 'save'];
            }
        }

        $ratingList = [];
        if ($current_context->isAssessmentActive()) {
            $itemIds = [];
            foreach ($discussions as $discussion) {
                $itemIds[] = $discussion->getItemId();
            }
            $ratingList = $assessmentService->getListAverageRatings($itemIds);
        }

        return $this->render('discussion/feed.html.twig', [
            'roomId' => $roomId,
            'discussions' => $discussions,
            'readerList' => $readerList,
            'showRating' => $current_context->isAssessmentActive(),
            'showWorkflow' => $current_context->withWorkflow(),
            'ratingList' => $ratingList,
            'allowedActions' => $allowedActions,
        ]);
    }

    #[Route(path: '/room/{roomId}/discussion')]
    public function list(
        Request $request,
        int $roomId
    ): Response {
        $roomItem = $this->getRoom($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $sort = $this->session->get('sortDiscussions', 'latest');

        // get the discussion manager service
        $filterForm = $this->createFilterForm($roomItem);

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in discussion manager
            $this->discussionService->setFilterConditions($filterForm);
        } else {
            $this->discussionService->hideDeactivatedEntries();
        }

        // get discussion list from manager service
        $itemsCountArray = $this->discussionService->getCountArray($roomId);

        $usageInfo = false;
        if ('' != $roomItem->getUsageInfoTextForRubricInForm('discussion')) {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('discussion');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('discussion');
        }

        return $this->render('discussion/list.html.twig', [
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
            'isArchived' => $roomItem->getArchived(),
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
            'sort' => $sort,
        ]);
    }

    #[Route(path: '/room/{roomId}/discussion/print/{sort}', defaults: ['sort' => 'none'])]
    public function printlist(
        Request $request,
        AssessmentService $assessmentService,
        PrintService $printService,
        int $roomId,
        string $sort
    ): Response {
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
        if ('none' === $sort || empty($sort)) {
            $sort = $this->session->get('sortDiscussions', 'latest');
        }
        $discussions = $this->discussionService->getListDiscussions($roomId, $numAllDiscussions, 0, $sort);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $readerList = [];
        foreach ($discussions as $item) {
            $readerList[$item->getItemId()] = $this->readerService->getChangeStatus($item->getItemId());
        }

        $ratingList = [];
        if ($current_context->isAssessmentActive()) {
            $itemIds = [];
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

    #[Route(path: '/room/{roomId}/discussion/{itemId}', requirements: ['itemId' => '\d+'])]
    #[IsGranted('ITEM_SEE', subject: 'itemId')]
    public function detail(
        Request $request,
        TopicService $topicService,
        LegacyMarkup $legacyMarkup,
        int $roomId,
        int $itemId,
        AssessmentService $assessmentService,
        CategoryService $categoryService
    ): Response {
        $infoArray = $this->getDetailInfo($roomId, $itemId, $legacyMarkup, $assessmentService, $categoryService);

        $alert = null;
        if (!$this->isGranted(ItemVoter::EDIT_LOCK, $itemId)) {
            $alert['type'] = 'warning';
            $alert['content'] = $this->translator->trans('item is locked', [], 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        return $this->render('discussion/detail.html.twig', [
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
        ]);
    }

    private function getDetailInfo(
        $roomId,
        $itemId,
        LegacyMarkup $legacyMarkup,
        AssessmentService $assessmentService,
        CategoryService $categoryService
    ) {
        $infoArray = [];

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
        foreach ($articleList as $article) {
            /** @var \cs_discussionarticle_item $article */
            $latestReader = $readerManager->getLatestReader($article->getItemID());
            if (empty($latestReader) || $latestReader['read_date'] < $article->getModificationDate()) {
                $readerManager->markRead($article->getItemID(), 0);
            }

            $latestNoticed = $noticedManager->getLatestNoticed($article->getItemID());
            if (empty($latestNoticed) || $latestNoticed['read_date'] < $article->getModificationDate()) {
                $noticedManager->markNoticed($article->getItemID(), 0);
            }

            $legacyMarkup->addFiles($this->itemService->getItemFileList($article->getItemID()));
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

        $id_array = $user_list->getIDArray();
        $readerManager->getLatestReaderByUserIDArray($id_array, $discussion->getItemID());
        foreach ($user_list as $user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($discussion->getItemID(),
                $user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $discussion->getModificationDate()) {
                    ++$read_count;
                    ++$read_since_modification_count;
                } else {
                    ++$read_count;
                }
            }
        }

        $readerList = [];
        $modifierList = [];
        foreach ($itemArray as $item) {
            $reader = $this->readerService->getLatestReader($item->getItemId());
            if (empty($reader)) {
                $readerList[$item->getItemId()] = 'new';
            } elseif ($reader['read_date'] < $item->getModificationDate()) {
                $readerList[$item->getItemId()] = 'changed';
            }

            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        $discussions = $this->discussionService->getListDiscussions($roomId);
        $discussionList = [];
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
                    ++$counterBefore;
                }
                $discussionList[] = $tempDiscussion;
                if ($tempDiscussion->getItemID() == $discussion->getItemID()) {
                    $foundDiscussion = true;
                }
                if (!$foundDiscussion) {
                    $prevItemId = $tempDiscussion->getItemId();
                }
                ++$counterPosition;
            } else {
                if ($counterAfter < 5) {
                    $discussionList[] = $tempDiscussion;
                    ++$counterAfter;
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
                $lastItemId = $discussions[sizeof($discussions) - 1]->getItemId();
            }
        }

        $ratingDetail = [];
        if ($current_context->isAssessmentActive()) {
            $ratingDetail = $assessmentService->getRatingDetail($discussion);
            $ratingAverageDetail = $assessmentService->getAverageRatingDetail($discussion);
            $ratingOwnDetail = $assessmentService->getOwnRatingDetail($discussion);
        }

        $reader_manager = $this->legacyEnvironment->getReaderManager();
        $noticed_manager = $this->legacyEnvironment->getNoticedManager();

        $item = $discussion;
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if (empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if (empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        $categories = [];
        if ($current_context->withTags()) {
            $roomCategories = $categoryService->getTags($roomId);
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

    private function getTagDetailArray($baseCategories, $itemCategories)
    {
        $result = [];
        $tempResult = [];
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
                        $result[] = ['title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult];
                    } else {
                        $result[] = ['title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id']];
                    }
                    $foundCategory = true;
                }
            }
            if (!$foundCategory) {
                if ($addCategory) {
                    $result[] = ['title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult];
                }
            }
            $tempResult = [];
            $addCategory = false;
        }

        return $result;
    }

    #[Route(path: '/room/{roomId}/discussion/create')]
    #[IsGranted('ITEM_NEW')]
    public function create(
        int $roomId
    ): RedirectResponse {
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

    #[Route(path: '/room/{roomId}/discussion/{itemId}/print')]
    public function print(
        PrintService $printService,
        LegacyMarkup $legacyMarkup,
        int $roomId,
        int $itemId,
        AssessmentService $assessmentService,
        CategoryService $categoryService
    ): Response {
        $infoArray = $this->getDetailInfo($roomId, $itemId, $legacyMarkup, $assessmentService, $categoryService);

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

    #[Route(path: '/room/{roomId}/discussion/{itemId}/answerform')]
    #[IsGranted('ITEM_NEW')]
    public function answerRoot(
        int $roomId,
        int $itemId,
        Request $request
    ): Response
    {
        $discussion = $this->discussionService->getDiscussion($itemId);
        if (!$discussion || $discussion->isDraft()) {
            throw $this->createNotFoundException();
        }

        $answer = $this->discussionService->getNewArticle();
        $form = $this->createForm(DiscussionAnswerType::class, $answer, [
            'action' => $this->generateUrl('app_discussion_answerroot', [
                'roomId' => $roomId,
                'itemId' => $discussion->getItemID(),
            ]),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $newPosition = $this->discussionService->calculateNewPosition($discussion, 0);

                $answer->setDiscussionID($itemId);
                $answer->setPosition($newPosition);
                $answer->setPrivateEditing(false);
                $answer->save();
            }

            return $this->redirectToRoute('app_discussion_detail', [
                'roomId' => $roomId,
                'itemId' => $itemId,
                '_fragment' => "answer_id_{$answer->getItemID()}",
            ]);
        }

        return $this->render('discussion/create_answer.html.twig', [
            'form' => $form->createView(),
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
            'parentId' => 0,
            'withUpload' => false,
        ]);
    }

    #[Route(path: '/room/{roomId}/discussion/{itemId}/createanswer')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function createAnswer(
        int $roomId,
        int $itemId,
        Request $request,
        DiscussionarticleTransformer $transformer
    ): Response {
        $discussion = $this->discussionService->getDiscussion($itemId);
        if (!$discussion) {
            throw $this->createNotFoundException();
        }

        $parentId = $request->query->get('answerTo', 0);
        $newPosition = $this->discussionService->calculateNewPosition($discussion, $parentId);

        $article = $this->discussionService->getNewArticle();
        $article->setDraftStatus(1);
        $article->setDiscussionID($itemId);
        $article->setPosition($newPosition);
        $article->setPrivateEditing(false);
        $article->save();

        $formData = $transformer->transform($article);
        $form = $this->createForm(DiscussionAnswerType::class, $formData, [
            'action' => $this->generateUrl('app_discussion_editanswer', [
                'roomId' => $roomId,
                'itemId' => $article->getItemID(),
            ]),
        ]);

        return $this->render('discussion/create_answer.html.twig', [
            'form' => $form->createView(),
            'article' => $article,
            'user' => $this->legacyEnvironment->getCurrentUserItem(),
            'parentId' => $parentId,
            'withUpload' => true,
        ]);
    }

    #[Route(path: '/room/{roomId}/discussion/{itemId}/editanswer')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function editAnswer(
        Request $request,
        DiscussionarticleTransformer $transformer,
        int $roomId,
        int $itemId
    ): RedirectResponse {
        $item = $this->itemService->getItem($itemId);
        $article = $this->discussionService->getArticle($itemId);
        $formData = $transformer->transform($article);

        $form = $this->createForm(DiscussionAnswerType::class, $formData, [
            'action' => $this->generateUrl('app_discussion_editanswer', [
                'roomId' => $roomId,
                'itemId' => $article->getItemID(),
            ]),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $article = $transformer->applyTransformation($article, $form->getData());

                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }

                // update modifier
                $article->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                $article->save();
            } else {
                if ($form->get('cancel')->isClicked()) {
                    $article->delete();
                }
            }
        }

        return $this->redirectToRoute('app_discussion_detail', [
            'roomId' => $roomId,
            'itemId' => $article->getDiscussionID(),
            '_fragment' => "answer_id_{$article->getItemID()}",
        ]);
    }

    #[Route(path: '/room/{roomId}/discussion/{itemId}/edit')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function edit(
        Request $request,
        CategoryService $categoryService,
        LabelService $labelService,
        DiscussionTransformer $discussionTransformer,
        DiscussionarticleTransformer $discussionarticleTransformer,
        int $roomId,
        int $itemId
    ): Response {
        $form = null;
        $item = $this->itemService->getItem($itemId);

        $current_context = $this->legacyEnvironment->getCurrentContextItem();

        $discussionItem = null;
        $discussionArticleItem = null;

        $isDraft = $item->isDraft();

        if ('discussion' == $item->getItemType()) {
            // get discussion from DiscussionService
            /** @var \cs_discussion_item $discussionItem */
            $discussionItem = $this->discussionService->getDiscussion($itemId);
            $discussionItem->setDraftStatus($isDraft);
            if (!$discussionItem) {
                throw $this->createNotFoundException('No discussion found for id ' . $itemId);
            }
            $formData = $discussionTransformer->transform($discussionItem);
            $formData['category_mapping']['categories'] = $labelService->getLinkedCategoryIds($item);
            $formData['hashtag_mapping']['hashtags'] = $labelService->getLinkedHashtagIds($itemId, $roomId);
            $formData['draft'] = $isDraft;
            $form = $this->createForm(DiscussionType::class, $formData, ['action' => $this->generateUrl('app_discussion_edit', ['roomId' => $roomId, 'itemId' => $itemId]), 'placeholderText' => '[' . $this->translator->trans('insert title') . ']', 'categoryMappingOptions' => [
                'categories' => $labelService->getCategories($roomId),
                'categoryPlaceholderText' => $this->translator->trans('New category', [], 'category'),
                'categoryEditUrl' => $this->generateUrl('app_category_add', ['roomId' => $roomId]),
            ], 'hashtagMappingOptions' => [
                'hashtags' => $labelService->getHashtags($roomId),
                'hashTagPlaceholderText' => $this->translator->trans('New hashtag', [], 'hashtag'),
                'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId]),
            ], 'room' => $current_context]);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                if ('discussion' == $item->getItemType()) {
                    $discussionItem = $discussionTransformer->applyTransformation($discussionItem, $form->getData());
                    // update modifier
                    $discussionItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());

                    // set linked hashtags and categories
                    $formData = $form->getData();
                    if ($form->has('category_mapping')) {
                        $categoryIds = $formData['category_mapping']['categories'] ?? [];

                        if (isset($formData['category_mapping']['newCategory'])) {
                            $newCategoryTitle = $formData['category_mapping']['newCategory'];
                            $newCategory = $categoryService->addTag($newCategoryTitle, $roomId);
                            $categoryIds[] = $newCategory->getItemID();
                        }

                        if (!empty($categoryIds)) {
                            $discussionItem->setTagListByID($categoryIds);
                        }
                    }
                    if ($form->has('hashtag_mapping')) {
                        $hashtagIds = $formData['hashtag_mapping']['hashtags'] ?? [];
                        if (isset($formData['hashtag_mapping']['newHashtag'])) {
                            $newHashtagTitle = $formData['hashtag_mapping']['newHashtag'];
                            $newHashtag = $labelService->getNewHashtag($newHashtagTitle, $roomId);
                            $hashtagIds[] = $newHashtag->getItemID();
                        }

                        if (!empty($hashtagIds)) {
                            $discussionItem->setBuzzwordListByID($hashtagIds);
                        }
                    }

                    $discussionItem->save();
                } else {
                    if ('discarticle' == $item->getItemType()) {
                        $discussionArticleItem = $discussionarticleTransformer->applyTransformation($discussionArticleItem,
                            $form->getData());
                        // update modifier
                        $discussionArticleItem->setModificatorItem($this->legacyEnvironment->getCurrentUserItem());
                        $discussionArticleItem->save();
                    }
                }
            }

            return $this->redirectToRoute('app_discussion_save', ['roomId' => $roomId, 'itemId' => $itemId]);
        }

        if ('discussion' == $item->getItemType()) {
            $this->eventDispatcher->dispatch(new CommsyEditEvent($discussionItem), CommsyEditEvent::EDIT);
        } else {
            $discussionItem = $this->discussionService->getDiscussion($discussionArticleItem->getDiscussionID());
            $this->eventDispatcher->dispatch(new CommsyEditEvent($discussionItem), CommsyEditEvent::EDIT);
        }

        return $this->render('discussion/edit.html.twig', ['form' => $form->createView(), 'discussion' => $discussionItem, 'discussionArticle' => $discussionArticleItem, 'isDraft' => $isDraft, 'currentUser' => $this->legacyEnvironment->getCurrentUserItem()]);
    }

    #[Route(path: '/room/{roomId}/discussion/{itemId}/save')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function save(
        int $roomId,
        int $itemId
    ): Response {
        $typedItem = null;
        $item = $this->itemService->getItem($itemId);

        if ('discussion' == $item->getItemType()) {
            $typedItem = $this->discussionService->getDiscussion($itemId);
        } else {
            if ('discarticle' == $item->getItemType()) {
                $typedItem = $this->discussionService->getArticle($itemId);
            }
        }

        $itemArray = [$typedItem];
        $modifierList = [];
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
        $id_array = [];
        while ($current_user) {
            $id_array[] = $current_user->getItemID();
            $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array, $typedItem->getItemID());
        $current_user = $user_list->getFirst();
        while ($current_user) {
            $current_reader = $readerManager->getLatestReaderForUserByID($typedItem->getItemID(),
                $current_user->getItemID());
            if (!empty($current_reader)) {
                if ($current_reader['read_date'] >= $typedItem->getModificationDate()) {
                    ++$read_count;
                    ++$read_since_modification_count;
                } else {
                    ++$read_count;
                }
            }
            $current_user = $user_list->getNext();
        }

        $readerList = [];
        $modifierList = [];
        foreach ($itemArray as $item) {
            $reader = $this->readerService->getLatestReader($item->getItemId());
            if (empty($reader)) {
                $readerList[$item->getItemId()] = 'new';
            } elseif ($reader['read_date'] < $item->getModificationDate()) {
                $readerList[$item->getItemId()] = 'changed';
            }

            $modifierList[$item->getItemId()] = $this->itemService->getAdditionalEditorsForItem($item);
        }

        if ('discussion' == $item->getItemType()) {
            $this->eventDispatcher->dispatch(new CommsyEditEvent($typedItem), CommsyEditEvent::SAVE);
        } else {
            $discussionItem = $this->discussionService->getDiscussion($typedItem->getDiscussionID());
            $this->eventDispatcher->dispatch(new CommsyEditEvent($discussionItem), CommsyEditEvent::SAVE);
        }

        return $this->render('discussion/save.html.twig', [
            'roomId' => $roomId,
            'item' => $typedItem,
            'modifierList' => $modifierList,
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
        ]);
    }

    #[Route(path: '/room/{roomId}/discussion/{itemId}/rating/{vote}')]
    public function rating(
        AssessmentService $assessmentService,
        int $roomId,
        int $itemId,
        $vote
    ): Response {
        $discussion = $this->discussionService->getDiscussion($itemId);
        if ('remove' != $vote) {
            $assessmentService->rateItem($discussion, $vote);
        } else {
            $assessmentService->removeRating($discussion);
        }
        $ratingDetail = $assessmentService->getRatingDetail($discussion);
        $ratingAverageDetail = $assessmentService->getAverageRatingDetail($discussion);
        $ratingOwnDetail = $assessmentService->getOwnRatingDetail($discussion);

        return $this->render('discussion/rating.html.twig', [
            'roomId' => $roomId,
            'discussion' => $discussion,
            'ratingArray' => [
                'ratingDetail' => $ratingDetail,
                'ratingAverageDetail' => $ratingAverageDetail,
                'ratingOwnDetail' => $ratingOwnDetail,
            ],
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/discussion/download')]
    public function download(
        Request $request,
        DownloadAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    // ##################################################################################################
    // # XHR Action requests
    // ##################################################################################################
    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/discussion/xhr/markread', condition: 'request.isXmlHttpRequest()')]
    public function xhrMarkRead(
        Request $request,
        MarkReadAction $markReadAction,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $markReadAction->execute($room, $items);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/discussion/xhr/mark', condition: 'request.isXmlHttpRequest()')]
    public function xhrMark(
        Request $request,
        MarkAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/discussion/xhr/categorize', condition: 'request.isXmlHttpRequest()')]
    public function xhrCategorize(
        Request $request,
        CategorizeAction $action,
        int $roomId
    ): Response {
        return parent::handleCategoryActionOptions($request, $action, $roomId);
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/discussion/xhr/hashtag', condition: 'request.isXmlHttpRequest()')]
    public function xhrHashtag(
        Request $request,
        HashtagAction $action,
        int $roomId
    ): Response {
        return parent::handleHashtagActionOptions($request, $action, $roomId);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/discussion/xhr/activate', condition: 'request.isXmlHttpRequest()')]
    public function xhrActivate(
        Request $request,
        ActivateAction $action,
        $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/discussion/xhr/deactivate', condition: 'request.isXmlHttpRequest()')]
    public function xhrDeactivate(
        Request $request,
        DeactivateAction $action,
        $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/room/{roomId}/discussion/xhr/delete', condition: 'request.isXmlHttpRequest()')]
    public function xhrDelete(
        Request $request,
        DeleteAction $action,
        int $roomId
    ): Response {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        return $action->execute($room, $items);
    }

    /**
     * @return FormInterface
     */
    private function createFilterForm(
        \cs_room_item $room
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
     * @param \cs_room_item $roomItem
     * @param bool          $selectAll
     * @param int[]         $itemIds
     *
     * @return \cs_discussion_item[]
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
