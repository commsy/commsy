<?php

namespace App\Controller;

use App\Entity\Account;
use App\Form\DataTransformer\PortfolioTransformer;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use App\Utils\ItemService;
use App\Utils\PortfolioService;
use App\Form\Type\AnnotationType;
use App\Form\Type\PortfolioEditCategoryType;
use App\Form\Type\PortfolioType;
use App\Utils\ReaderService;
use App\Utils\UserService;
use cs_user_item;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security as CoreSecurity;

/**
 * Class CalendarController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class PortfolioController extends AbstractController
{

    private PortfolioService $portfolioService;

    private PortfolioTransformer $transformer;

    private ReaderService $readerService;

    private ItemService $itemService;

    private UserService $userService;

    private CategoryService $categoryService;

    private \cs_environment $legacyEnvironment;

    private CoreSecurity $security;

    /**
     * PortfolioController constructor.
     * @param PortfolioService $portfolioService
     * @param PortfolioTransformer $transformer
     * @param ReaderService $readerService
     * @param ItemService $itemService
     * @param UserService $userService
     * @param CategoryService $categoryService
     * @param LegacyEnvironment $legacyEnvironment
     */
    public function __construct(PortfolioService $portfolioService,
                                PortfolioTransformer $transformer,
                                ReaderService $readerService,
                                ItemService $itemService,
                                UserService $userService,
                                CategoryService $categoryService,
                                LegacyEnvironment $legacyEnvironment,
                                CoreSecurity $coreSecurity)
    {
        $this->portfolioService = $portfolioService;
        $this->transformer = $transformer;
        $this->readerService = $readerService;
        $this->itemService = $itemService;
        $this->userService = $userService;
        $this->categoryService = $categoryService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->security = $coreSecurity;
    }


    /**
     * @Route("/room/{roomId}/portfolio/")
     * @Template()
     * @param Request $request
     * @param int $roomId
     * @return array
     */
    public function indexAction(
        Request $request,
        int $roomId
    ) {
        $portfolioId = 'none';
        if ($request->query->has('portfolioId')) {
            $portfolioId = $request->query->get('portfolioId');
        }

        return [
            'portfolioId' => $portfolioId,
            'roomId' => $roomId,
        ];
    }

    /**
     * @Route("/room/{roomId}/portfolio/{portfolioId}", requirements={
     *     "portfolioId": "\d+"
     * }))
     * @Template()
     * @param int $roomId
     * @param int|null $portfolioId
     * @return array
     */
    public function portfolioAction(
        int $roomId,
        int $portfolioId = null
    ) {
        $portfolio = $this->portfolioService->getPortfolio($portfolioId);

        $linkItemIds = [];
        foreach ($portfolio['links'] as $linkArray) {
            foreach ($linkArray as $link) {
                $linkItemIds[] = $link['itemId'];
            }
        }
        $linkItemIds = array_unique($linkItemIds);

        $linkPositions = [];
        foreach ($linkItemIds as $linkItemId) {
            foreach ($portfolio['tags'] as $firstTag) {
                foreach ($portfolio['tags'] as $secondTag) {
                    if ($firstTag['t_id'] != $secondTag['t_id']) {
                        $foundFirstTag = false;
                        $foundSecondTag = false;
                        foreach ($portfolio['links'] as $tagId => $linkArray) {
                            if ($tagId == $firstTag['t_id'] || $tagId == $secondTag['t_id']) {
                                foreach ($linkArray as $link) {
                                    if ($linkItemId = $link['itemId']) {
                                        if ($tagId == $firstTag['t_id']) {
                                            $foundFirstTag = true;
                                        }
                                        if ($tagId == $secondTag['t_id']) {
                                            $foundSecondTag = true;
                                        }
                                    }
                                }
                            }
                        }
                        if ($foundFirstTag && $foundSecondTag) {
                            $positionFound = false;
                            if (isset($linkPositions[$linkItemId])) {
                                foreach ($linkPositions[$linkItemId] as $tempPosition) {
                                    if (($tempPosition[0] == $firstTag['t_id'] && $tempPosition[1] == $secondTag['t_id']) || ($tempPosition[0] == $secondTag['t_id'] && $tempPosition[1] == $firstTag['t_id'])) {
                                        $positionFound = true;
                                    }
                                }
                            }
                            if (!$positionFound) {
                                $linkPositions[$linkItemId][] = [$firstTag['t_id'], $secondTag['t_id']];
                            }
                        }
                    }
                }
            }
        }

        /** @var Account $account */
        $account = $this->security->getUser();
        $user = $this->userService->getPortalUser($account);

        $external = false;
        if ($user->getRelatedPrivateRoomUserItem()->getItemId() != $portfolio['creatorId']) {
            $external = true;
        }

        return array(
            'roomId' => $roomId,
            'portfolioId' => $portfolioId,
            'portfolio' => $portfolio,
            'linkPositions' => $linkPositions,
            'creator_fullname' => $portfolio['creator'],
            'external' => $external
        );
    }

    /**
     * @Route("/room/{roomId}/portfolio/portfoliosource/{source}")
     * @Template()
     * @param int $roomId
     * @param string|null $source
     * @return array
     */
    public function tabsAction(
        int $roomId,
        string $source = null
    ) {
        $portfolioList = $this->portfolioService->getPortfolioList();

        $portfolios = [];
        $myPortfolios = true;
        if ($source == 'my-portfolios') {
            $portfolios = $portfolioList['myPortfolios'];
        } else if ($source == "activated-portfolios") {
            $portfolios = $portfolioList['activatedPortfolios'];
            $myPortfolios = false;
        }

        return array(
            'roomId' => $roomId,
            'portfolios' => $portfolios,
            'myPortfolios' => $myPortfolios,
        );
    }

    /**
     * @Route("/room/{roomId}/portfolio/{portfolioId}/detail/{firstTagId}/{secondTagId}")
     * @Template()
     * @param int $roomId
     * @param int $portfolioId
     * @param int $firstTagId
     * @param int $secondTagId
     * @return array
     */
    public function detailAction(
        int $roomId,
        int $portfolioId,
        int $firstTagId,
        int $secondTagId
    ) {
        $portfolio = $this->portfolioService->getPortfolio($portfolioId);

        $items = [];
        foreach ($portfolio['links'] as $tempFirstTagId => $firstEntries) {
            foreach ($portfolio['links'] as $tempSecondTagId => $secondEntries) {
                if ($tempFirstTagId == $firstTagId && $tempSecondTagId == $secondTagId) {
                    foreach ($firstEntries as $firstEntry) {
                        foreach ($secondEntries as $secondEntry) {
                            if ($firstEntry['itemId'] == $secondEntry['itemId']) {
                                $items[] = $this->itemService->getTypedItem($firstEntry['itemId']);
                            }
                        }
                    }
                }
            }
        }

        /** @var Account $account */
        $account = $this->security->getUser();
        $user = $this->userService->getPortalUser($account);

        $readerList = array();
        foreach ($items as $item) {
            if ($item != null) {
                $relatedUser = $user->getRelatedUserItemInContext($item->getContextId());
                if ($relatedUser) {
                    $readerList[$item->getItemId()] = $this->readerService->getChangeStatusForUserByID($item->getItemId(), $relatedUser->getItemId());
                }
            }
        }

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        return [
            'roomId' => $roomId,
            'items' => $items,
            'feedList' => $items,
            'readerList' => $readerList,
            'firstTag' => $this->categoryService->getTag($firstTagId),
            'secondTag' => $this->categoryService->getTag($secondTagId),
            'annotationForm' => $form->createView(),
            'portfolio' => $portfolio,
            'portfolioId' => $portfolioId,
        ];
    }

    /**
     * Create new portfolios and edit existing ones
     *
     * @Route("/room/{roomId}/portfolio/{portfolioId}/edit")
     * @Template()
     * @param Request $request
     * @param int $roomId
     * @param string $portfolioId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        int $roomId,
        string $portfolioId
    ) {

        // when creating a new item, return a redirect to the edit form (portfolio draft)
        if ($portfolioId === 'new') {
            $portfolioItem = $this->portfolioService->getNewItem();
            $portfolioItem->save();
            return $this->redirectToRoute('app_portfolio_edit', [
                'roomId' => $roomId,
                'portfolioId' => $portfolioItem->getItemId(),
            ]);
        }

        $item = $this->itemService->getItem($portfolioId);

        $portfolioManager = $this->legacyEnvironment->getPortfolioManager();
        $portfolioItem = $portfolioManager->getItem($portfolioId);

        $formData = $this->transformer->transform($portfolioItem);

        $form = $this->createForm(PortfolioType::class, $formData, [
            'item' => $item,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $portfolioItem = $this->transformer->applyTransformation($portfolioItem, $form->getData());
                $portfolioItem->save();

                // ensure portfolio is now longer in draft state after saving
                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();

                    $formData = $form->getData();
                    if (isset($formData['from_template'])) {
                        if ($formData['from_template'] != 'none') {
                            $this->portfolioService->prepareFromTemplate((int) $formData['from_template'], $portfolioItem);
                        }
                    }
                }

                return $this->redirectToRoute('app_portfolio_index', [
                    'roomId' => $roomId,
                    'portfolioId' => $portfolioId,
                ]);
            }  else if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('app_portfolio_index', [
                    'roomId' => $roomId,
                ]);
            } else if ($form->has('delete') && $form->get('delete')->isClicked()) {
                $portfolioManager->delete($portfolioId);
                return $this->redirectToRoute('app_portfolio_index', [
                    'roomId' => $roomId,
                ]);
            }
        }

        return [
            'form' => $form->createView(),
            'item' => $item,
            'portfolio' => $portfolioItem,
        ];
    }

    /**
     * @Route("/room/{roomId}/portfolio/{portfolioId}/editcategory/{position}/{categoryId}/")
     * @Template()
     * @param Request $request
     * @param int $roomId
     * @param int $portfolioId
     * @param string $position
     * @param int $categoryId
     * @return array|RedirectResponse
     */
    public function editcategoryAction(
        Request $request,
        int $roomId,
        int $portfolioId,
        string $position,
        int $categoryId
    ) {
        $portfolioService = $this->get(PortfolioService::class);
        $portfolio = $portfolioService->getPortfolio($portfolioId);

        $formData = [
            'delete-category' => $categoryId,
        ];

        if (is_numeric($categoryId)) {
            $formData['categories'] = [$categoryId];
        }

        $roomTags = $this->categoryService->getTags($roomId);
//        $disabledCategories = $this->getDisabledTags($roomTags, $categoryId, $portfolio);

        $form = $this->createForm(PortfolioEditCategoryType::class, $formData, array(
            'categories' => $roomTags,
            'categoryId' => $categoryId,
            'portfolioId' => $portfolioId,
//            'disabledCategories' => $disabledCategories,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            if ($form->get('save')->isClicked()) {

                $tagId = $formData['categories'][0];

                if ($categoryId == 'add') {
                    // add new row or column

                    $index = 1;
                    foreach ($portfolio['tags'] as $tag) {
                        if ($position == 'row') {
                            if ($tag['column'] == 0) {
                                $index++;
                            }
                        } else {
                            if ($tag['row'] == 0) {
                                $index++;
                            }
                        }
                    }

                    $portfolioService->addTagToPortfolio($portfolioId, $tagId, $position, $index, $formData['description']);

                } else {
                    // edit row or column
                    $portfolioService->replaceTagForPortfolio($portfolioId, $tagId, $categoryId, $formData['description']);
                }

            } else if ($form->has('delete') && $form->get('delete')->isClicked()) {
                $tagId = $categoryId;

                $portfolioTags = $portfolioService->getPortfolioTags($portfolioId);

                // get the tag we want to delete
                $deleteTag = null;
                foreach ($portfolioTags as $portfolioTag) {
                    if ($portfolioTag["t_id"] == $tagId) {
                        $deleteTag = $portfolioTag;
                        break;
                    }
                }

                // determe if this is a row or column tag
                $isRow = false;
                if ($deleteTag["row"] > 0) $isRow = true;

                // delete the tag
                $portfolioService->deletePortfolioTag($portfolioId, $tagId);

                // if there are rows or column after this tag, we need to decrease their positions
                foreach ($portfolioTags as $portfolioTag) {
                    if ($isRow) {
                        if ($portfolioTag["row"] > $deleteTag["row"]) {
                            $portfolioService->updatePortfolioTagPosition($portfolioId, $portfolioTag["t_id"], $portfolioTag["row"] - 1, 0);
                        }
                    } else {
                        if ($portfolioTag["column"] > $deleteTag["column"]) {
                            $portfolioService->updatePortfolioTagPosition($portfolioId, $portfolioTag["t_id"], 0, $portfolioTag["column"] - 1);
                        }
                    }
                }
            }

            return $this->redirectToRoute('app_portfolio_index', array('roomId' => $roomId, 'portfolioId' => $portfolioId));
        }

        return [
            'form' => $form->createView(),
            'categoryId' => $categoryId,
        ];
    }

    /**
     * @Route("/room/{roomId}/portfolio/{portfolioId}/stopActivation")
     * @param int $roomId
     * @param int $portfolioId
     * @return RedirectResponse
     */
    public function stopActivation(
        int $roomId,
        int $portfolioId
    ) {
        $portfolioManager = $this->legacyEnvironment->getPortfolioManager();

        $portfolio = $portfolioManager->getItem($portfolioId);

        if (!$portfolio) {
            throw new NotFoundHttpException('Portfolio not found');
        }

        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        $portfolioManager->removeExternalViewer($portfolio->getItemID(), $currentUser->getUserID());

        return $this->redirectToRoute('app_portfolio_index', [
            'roomId' => $roomId,
            'portfolioId' => $portfolioId
        ]);
    }

    private function getDisabledTags(
        $roomTags,
        $orientation,
        $portfolio
    ) {
        $result = [];
        foreach ($roomTags as $roomTag) {
            $usedCategories = [];
            foreach ($portfolio['tags'] as $tag) {
                if ($orientation == 'row') {
                    if ($tag['row'] == 0) {
                        $usedCategories[] = $tag['t_id'];
                    }
                } else {
                    if ($tag['column'] == 0) {
                        $usedCategories[] = $tag['t_id'];
                    }
                }
            }
            $result[$roomTag['item_id']] = false;
            if (in_array($roomTag['item_id'], $usedCategories)) {
                $result[$roomTag['item_id']] = true;
            }

            if (!empty($roomTag['children'])) {
                $result = $result + $this->getDisabledTags($roomTag['children'], $orientation, $portfolio);
            }
        }
        return $result;
    }
}
