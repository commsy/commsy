<?php

namespace CommsyBundle\Controller;

use CommsyBundle\Form\Type\AnnotationType;
use CommsyBundle\Form\Type\PortfolioEditCategoryType;
use CommsyBundle\Form\Type\PortfolioType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CalendarController
 * @package CommsyBundle\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class PortfolioController extends Controller
{
    /**
     * @Route("/room/{roomId}/portfolio/")
     * @Template()
     */
    public function indexAction($roomId, Request $request)
    {
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
     */
    public function portfolioAction($roomId, $portfolioId = null, Request $request)
    {
        $portfolioService = $this->get('commsy_legacy.portfolio_service');
        $portfolio = $portfolioService->getPortfolio($portfolioId);

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

        $userService = $this->get("commsy_legacy.user_service");

        $user = $userService->getPortalUserFromSessionId();

        $external = false;
        if ($user->getRelatedPrivateRoomUserItem()->getItemId() != $portfolio['creatorId']) {
            $external = true;
        }

        return array(
            'roomId' => $roomId,
            'portfolioId' => $portfolioId,
            'portfolio' => $portfolio,
            'linkPositions' => $linkPositions,
            'user' => $user,
            'external' => $external
        );
    }

    /**
     * @Route("/room/{roomId}/portfolio/portfoliosource/{source}")
     * @Template()
     */
    public function tabsAction($roomId, $source = null, Request $request)
    {
        $portfolioService = $this->get('commsy_legacy.portfolio_service');
        $portfolioList = $portfolioService->getPortfolioList();

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
     */
    public function detailAction($roomId, $portfolioId, $firstTagId, $secondTagId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $portfolioService = $this->get('commsy_legacy.portfolio_service');
        $portfolio = $portfolioService->getPortfolio($portfolioId);

        $items = [];
        foreach ($portfolio['links'] as $tempFirstTagId => $firstEntries) {
            foreach ($portfolio['links'] as $tempSecondTagId => $secondEntries) {
                if ($tempFirstTagId == $firstTagId && $tempSecondTagId == $secondTagId) {
                    foreach ($firstEntries as $firstEntry) {
                        foreach ($secondEntries as $secondEntry) {
                            if ($firstEntry['itemId'] == $secondEntry['itemId']) {
                                $items[] = $itemService->getTypedItem($firstEntry['itemId']);
                            }
                        }
                    }
                }
            }
        }

        $readerService = $this->get('commsy_legacy.reader_service');
        $userService = $this->get("commsy_legacy.user_service");
        $user = $userService->getPortalUserFromSessionId();

        $readerList = array();
        foreach ($items as $item) {
            if ($item != null) {
                $relatedUser = $user->getRelatedUserItemInContext($item->getContextId());
                if ($relatedUser) {
                    $readerList[$item->getItemId()] = $readerService->getChangeStatusForUserByID($item->getItemId(), $relatedUser->getItemId());
                }
            }
        }

        $categoryService = $this->get('commsy_legacy.category_service');

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        return [
            'roomId' => $roomId,
            'items' => $items,
            'feedList' => $items,
            'readerList' => $readerList,
            'firstTag' => $categoryService->getTag($firstTagId),
            'secondTag' => $categoryService->getTag($secondTagId),
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
     */
    public function editAction($roomId, $portfolioId, Request $request)
    {
        $portfolioService = $this->get('commsy_legacy.portfolio_service');

        // when creating a new item, return a redirect to the edit form (portfolio draft)
        if ($portfolioId === 'new') {
            $portfolioItem = $portfolioService->getNewItem();
            $portfolioItem->save();
            return $this->redirectToRoute('commsy_portfolio_edit', [
                'roomId' => $roomId,
                'portfolioId' => $portfolioItem->getItemId(),
            ]);
        }

        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($portfolioId);

        $portfolioManager = $this->get('commsy_legacy.environment')->getEnvironment()->getPortfolioManager();
        $portfolioItem = $portfolioManager->getItem($portfolioId);

        $transformer = $this->get('commsy_legacy.transformer.portfolio');
        $formData = $transformer->transform($portfolioItem);

        $form = $this->createForm(PortfolioType::class, $formData, [
            'item' => $item,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $portfolioItem = $transformer->applyTransformation($portfolioItem, $form->getData());
                $portfolioItem->save();

                // ensure portfolio is now longer in draft state after saving
                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }

                return $this->redirectToRoute('commsy_portfolio_index', [
                    'roomId' => $roomId,
                    'portfolioId' => $portfolioId,
                ]);
            }  else if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('commsy_portfolio_index', [
                    'roomId' => $roomId,
                ]);
            } else if ($form->has('delete') && $form->get('delete')->isClicked()) {
                $portfolioManager->delete($portfolioId);
                return $this->redirectToRoute('commsy_portfolio_index', [
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
     */
    public function editcategoryAction($roomId, $portfolioId, $position, $categoryId, Request $request)
    {
        $portfolioService = $this->get('commsy_legacy.portfolio_service');
        $portfolio = $portfolioService->getPortfolio($portfolioId);

        $formData = [
            'delete-category' => $categoryId,
        ];

        if (is_numeric($categoryId)) {
            $formData['categories'] = [$categoryId];
        }

        $categoryService = $this->get('commsy_legacy.category_service');
        $roomTags = $categoryService->getTags($roomId);
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

            return $this->redirectToRoute('commsy_portfolio_index', array('roomId' => $roomId, 'portfolioId' => $portfolioId));
        }

        return [
            'form' => $form->createView(),
            'categoryId' => $categoryId,
        ];
    }

    private function getDisabledTags($roomTags, $orientation, $portfolio) {
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
