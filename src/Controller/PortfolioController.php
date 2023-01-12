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

use App\Entity\Account;
use App\Form\DataTransformer\PortfolioTransformer;
use App\Form\Type\AnnotationType;
use App\Form\Type\PortfolioEditCategoryType;
use App\Form\Type\PortfolioType;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use App\Utils\ItemService;
use App\Utils\PortfolioService;
use App\Utils\ReaderService;
use App\Utils\UserService;
use cs_environment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CalendarController.
 */
#[Security("is_granted('ITEM_ENTER', roomId)")]
class PortfolioController extends AbstractController
{
    private cs_environment $legacyEnvironment;

    /**
     * PortfolioController constructor.
     */
    public function __construct(private PortfolioService $portfolioService,
                                private PortfolioTransformer $transformer,
                                private ReaderService $readerService,
                                private ItemService $itemService,
                                private UserService $userService,
                                private CategoryService $categoryService,
                                LegacyEnvironment $legacyEnvironment,
                                private CoreSecurity $security)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    #[Route(path: '/room/{roomId}/portfolio/')]
    public function indexAction(
        Request $request,
        int $roomId
    ): Response {
        $portfolioId = 'none';
        if ($request->query->has('portfolioId')) {
            $portfolioId = $request->query->get('portfolioId');
        }

        return $this->render('portfolio/index.html.twig', [
            'portfolioId' => $portfolioId,
            'roomId' => $roomId,
        ]);
    }

    /**
     * @return array
     */
    #[Route(path: '/room/{roomId}/portfolio/{portfolioId}', requirements: ['portfolioId' => '\d+'])]
    public function portfolioAction(
        int $roomId,
        int $portfolioId = null,
        CoreSecurity $security
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
        $account = $security->getUser();
        $portalUser = $this->userService->getPortalUser($account);

        $external = false;
        if ($portalUser->getRelatedPrivateRoomUserItem()->getItemId() != $portfolio['creatorId']) {
            $external = true;
        }

        return $this->render('portfolio/portfolio.html.twig', [
            'roomId' => $roomId,
            'portfolioId' => $portfolioId,
            'portfolio' => $portfolio,
            'linkPositions' => $linkPositions,
            'creator_fullname' => $portfolio['creator'],
            'external' => $external
        ]);
    }

    #[Route(path: '/room/{roomId}/portfolio/portfoliosource/{source}')]
    public function tabsAction(
        int $roomId,
        string $source = null
    ): Response {
        $portfolioList = $this->portfolioService->getPortfolioList();

        $portfolios = [];
        $myPortfolios = true;
        if ('my-portfolios' == $source) {
            $portfolios = $portfolioList['myPortfolios'];
        } elseif ('activated-portfolios' == $source) {
            $portfolios = $portfolioList['activatedPortfolios'];
            $myPortfolios = false;
        }

        return $this->render('portfolio/tabs.html.twig', ['roomId' => $roomId, 'portfolios' => $portfolios, 'myPortfolios' => $myPortfolios]);
    }

    #[Route(path: '/room/{roomId}/portfolio/{portfolioId}/detail/{firstTagId}/{secondTagId}')]
    public function detailAction(
        int $roomId,
        int $portfolioId,
        int $firstTagId,
        int $secondTagId
    ): Response {
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

        $readerList = [];
        foreach ($items as $item) {
            if (null != $item) {
                $relatedUser = $user->getRelatedUserItemInContext($item->getContextId());
                if ($relatedUser) {
                    $readerList[$item->getItemId()] = $this->readerService->getChangeStatusForUserByID($item->getItemId(), $relatedUser->getItemId());
                }
            }
        }

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        return $this->render('portfolio/detail.html.twig', [
            'roomId' => $roomId,
            'items' => $items,
            'feedList' => $items,
            'readerList' => $readerList,
            'firstTag' => $this->categoryService->getTag($firstTagId),
            'secondTag' => $this->categoryService->getTag($secondTagId),
            'annotationForm' => $form->createView(),
            'portfolio' => $portfolio,
            'portfolioId' => $portfolioId,
        ]);
    }

    /**
     * Create new portfolios and edit existing ones.
     */
    #[Route(path: '/room/{roomId}/portfolio/{portfolioId}/edit')]
    public function editAction(
        Request $request,
        int $roomId,
        string $portfolioId
    ): Response {
        // when creating a new item, return a redirect to the edit form (portfolio draft)
        if (-1 == $portfolioId) { // -1 represents 'new'
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
                        if ('none' != $formData['from_template']) {
                            $this->portfolioService->prepareFromTemplate((int) $formData['from_template'], $portfolioItem);
                        }
                    }
                }

                return $this->redirectToRoute('app_portfolio_index', [
                    'roomId' => $roomId,
                    'portfolioId' => $portfolioId,
                ]);
            } elseif ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('app_portfolio_index', [
                    'roomId' => $roomId,
                ]);
            } elseif ($form->has('delete') && $form->get('delete')->isClicked()) {
                $portfolioManager->delete($portfolioId);

                return $this->redirectToRoute('app_portfolio_index', [
                    'roomId' => $roomId,
                ]);
            }
        }

        return $this->render('portfolio/edit.html.twig', [
            'form' => $form->createView(),
            'item' => $item,
            'portfolio' => $portfolioItem,
        ]);
    }

    #[Route(path: '/room/{roomId}/portfolio/{portfolioId}/editcategory/{position}/{categoryTerm}/')]
    public function editcategoryAction(
        Request $request,
        int $roomId,
        int $portfolioId,
        string $position,
        string $categoryTerm,
        PortfolioService $portfolioService,
        TranslatorInterface $translator,
        CategoryService $categoryService
    ): Response {
        $portfolio = $portfolioService->getPortfolio($portfolioId);

        $formData = [
            'delete-category' => $categoryTerm,
        ];

        if (is_numeric($categoryTerm)) {
            $formData['categories'] = [$categoryTerm];
        }

        $roomTags = $this->categoryService->getTags($roomId);
//        $disabledCategories = $this->getDisabledTags($roomTags, $categoryId, $portfolio);

        $form = $this->createForm(PortfolioEditCategoryType::class, $formData, ['categories' => $roomTags, 'categoryId' => $categoryTerm, 'portfolioId' => $portfolioId]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            if ($form->get('addCategory')->isClicked()) {
                $data = $form->getData();

                if (empty($data['title'])) {
                    // the additional error has to be added here (instead of a formtype constraint) to prevent unnamed new categories.
                    $form->addError(new FormError($translator->trans('Title may not be empty', [], 'portfolio')));

                    return $this->render('portfolio/editcategory.html.twig', [
                        'form' => $form->createView(),
                        'categoryTerm' => $categoryTerm,
                    ]);
                }

                // persist new category
                $categoryService->addTag($data['title'], $roomId);

                return $this->redirectToRoute('app_portfolio_editcategory', ['roomId' => $roomId, 'portfolioId' => $portfolioId, 'position' => $position, 'categoryTerm' => $categoryTerm]);
            }

            if ($form->get('save')->isClicked()) {
                $tagId = $formData['categories'][0];

                if ('add' == $categoryTerm) {
                    // add new row or column

                    $index = 1;
                    foreach ($portfolio['tags'] as $tag) {
                        if ('row' == $position) {
                            if (0 == $tag['column']) {
                                ++$index;
                            }
                        } else {
                            if (0 == $tag['row']) {
                                ++$index;
                            }
                        }
                    }

                    $portfolioService->addTagToPortfolio($portfolioId, $tagId, $position, $index, $formData['description']);
                } else {
                    // edit row or column
                    $portfolioService->replaceTagForPortfolio($portfolioId, $tagId, $categoryTerm, $formData['description']);
                }
            } elseif ($form->has('delete') && $form->get('delete')->isClicked()) {
                $tagId = $categoryTerm;

                $portfolioTags = $portfolioService->getPortfolioTags($portfolioId);

                // get the tag we want to delete
                $deleteTag = null;
                foreach ($portfolioTags as $portfolioTag) {
                    if ($portfolioTag['t_id'] == $tagId) {
                        $deleteTag = $portfolioTag;
                        break;
                    }
                }

                // determe if this is a row or column tag
                $isRow = false;
                if ($deleteTag['row'] > 0) {
                    $isRow = true;
                }

                // delete the tag
                $portfolioService->deletePortfolioTag($portfolioId, $tagId);

                // if there are rows or column after this tag, we need to decrease their positions
                foreach ($portfolioTags as $portfolioTag) {
                    if ($isRow) {
                        if ($portfolioTag['row'] > $deleteTag['row']) {
                            $portfolioService->updatePortfolioTagPosition($portfolioId, $portfolioTag['t_id'], $portfolioTag['row'] - 1, 0);
                        }
                    } else {
                        if ($portfolioTag['column'] > $deleteTag['column']) {
                            $portfolioService->updatePortfolioTagPosition($portfolioId, $portfolioTag['t_id'], 0, $portfolioTag['column'] - 1);
                        }
                    }
                }
            }

            return $this->redirectToRoute('app_portfolio_index', ['roomId' => $roomId, 'portfolioId' => $portfolioId]);
        }

        return $this->render('portfolio/editcategory.html.twig', [
            'form' => $form->createView(),
            'categoryTerm' => $categoryTerm,
        ]);
    }

    /**
     * @return RedirectResponse
     */
    #[Route(path: '/room/{roomId}/portfolio/{portfolioId}/stopActivation')]
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
            'portfolioId' => $portfolioId,
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
                if ('row' == $orientation) {
                    if (0 == $tag['row']) {
                        $usedCategories[] = $tag['t_id'];
                    }
                } else {
                    if (0 == $tag['column']) {
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
