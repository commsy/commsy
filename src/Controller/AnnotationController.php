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

use App\Form\DataTransformer\AnnotationTransformer;
use App\Form\Type\AnnotationType;
use App\Services\LegacyEnvironment;
use App\Utils\AnnotationService;
use App\Utils\ItemService;
use App\Utils\PortfolioService;
use App\Utils\ReaderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Class AnnotationController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
class AnnotationController extends AbstractController
{
    #[Route(path: '/room/{roomId}/annotation/feed/{linkedItemId}/{start}/{firstTagId}/{secondTagId}')]
    public function feedAction(
        AnnotationService $annotationService,
        ItemService $itemService,
        ReaderService $readerService,
        PortfolioService $portfolioService,
        int $roomId,
        int $linkedItemId,
        int $max = 10,
        int $start = 0,
        int $firstTagId = null,
        int $secondTagId = null
    ): Response {
        // get annotation list from manager service
        $annotations = $annotationService->getListAnnotations($roomId, $linkedItemId, $max, $start);

        if ($firstTagId && $secondTagId) {
            $cellCoordinates = $portfolioService->getCellCoordinatesForTagIds($linkedItemId, $firstTagId, $secondTagId);
            if (!empty($cellCoordinates)) {
                $annotationIds = $portfolioService->getAnnotationIdsForPortfolioCell($linkedItemId, $cellCoordinates[0], $cellCoordinates[1]);
                $portfolioAnnotations = [];
                if ($annotationIds) {
                    foreach ($annotationIds as $annotationId) {
                        $portfolioAnnotations[] = $itemService->getTypedItem($annotationId);
                    }
                }
                $annotations = $portfolioAnnotations;
            }
        }

        $readerList = [];
        foreach ($annotations as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }
        /**
         * For first show annotations no read and after mark read.
         */
        $itemAnnotation = $itemService->getItem($linkedItemId);
        $annotationList = $itemAnnotation->getAnnotationList();
        $annotationService->markAnnotationsReadedAndNoticed($annotationList);

        return $this->render('annotation/feed.html.twig', [
            'roomId' => $roomId,
            'annotations' => $annotations,
            'readerList' => $readerList,
        ]);
    }

    #[Route(path: '/room/{roomId}/annotation/feed/{linkedItemId}/{start}')]
    public function feedPrintAction(
        AnnotationService $annotationService,
        ReaderService $readerService,
        int $roomId,
        int $linkedItemId,
        int $max = 10,
        int $start = 0
    ): Response {
        // get annotation list from manager service
        $annotations = $annotationService->getListAnnotations($roomId, $linkedItemId, $max, $start);

        $readerList = [];
        foreach ($annotations as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        return $this->render('annotation/feed_print.html.twig', [
            'roomId' => $roomId,
            'annotations' => $annotations,
            'readerList' => $readerList,
        ]);
    }

    #[Route(path: '/room/{roomId}/annotation/{itemId}/edit', methods: ['GET', 'POST'])]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function editAction(
        ItemService $itemService,
        AnnotationTransformer $transformer,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId,
        Request $request
    ): Response {
        $item = $itemService->getTypedItem($itemId);
        $formData = $transformer->transform($item);
        $form = $this->createForm(AnnotationType::class, $formData);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $legacyEnvironment = $environment->getEnvironment();
                $readerManager = $legacyEnvironment->getReaderManager();
                $noticedManager = $legacyEnvironment->getNoticedManager();

                $item = $transformer->applyTransformation($item, $form->getData());
                $item->save();

                $readerManager->markRead($itemId, 0);
                $noticedManager->markNoticed($itemId, 0);
            }

            return $this->redirectToRoute('app_annotation_success', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        return $this->render('annotation/edit.html.twig', [
            'itemId' => $itemId,
            'form' => $form,
        ]);
    }

    #[Route(path: '/room/{roomId}/annotation/{itemId}/success', methods: ['GET'])]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function successAction(
        ItemService $itemService,
        int $itemId
    ): Response {
        $item = $itemService->getTypedItem($itemId);

        return $this->render('annotation/success.html.twig', [
            'annotation' => $item,
        ]);
    }

    /**
     * @return RedirectResponse
     */
    #[Route(path: '/room/{roomId}/annotation/{itemId}/create/{firstTagId}/{secondTagId}', methods: ['POST'])]
    #[IsGranted('ITEM_ANNOTATE', subject: 'itemId')]
    public function createAction(
        ItemService $itemService,
        AnnotationService $annotationService,
        Request $request,
        PortfolioService $portfolioService,
        int $roomId,
        int $itemId,
        int $firstTagId = null,
        int $secondTagId = null
    ): Response {
        $item = $itemService->getTypedItem($itemId);
        $itemType = $item->getItemType();

        $form = $this->createForm(AnnotationType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $data = $form->getData();

                // create new annotation
                $annotationId = $annotationService->addAnnotation($roomId, $itemId, $data['description']);

                $routeArray = [];
                $routeArray['roomId'] = $roomId;
                $routeArray['itemId'] = $itemId;
                $routeArray['_fragment'] = 'description'.$annotationId;
                if ('portfolio' == $itemType) {
                    $routeArray['portfolioId'] = $itemId;
                    $routeArray['firstTagId'] = $firstTagId;
                    $routeArray['secondTagId'] = $secondTagId;

                    $cellCoordinates = $portfolioService->getCellCoordinatesForTagIds($itemId, $firstTagId, $secondTagId);
                    if (!empty($cellCoordinates)) {
                        $portfolioService->setPortfolioAnnotation($itemId, $annotationId, $cellCoordinates[0], $cellCoordinates[1]);
                    }
                }

                return $this->redirectToRoute('app_'.$itemType.'_detail', $routeArray);
            }
            if ($form->get('cancel')->isClicked()) {
                if ('portfolio' == $itemType) {
                    return $this->redirectToRoute('app_portfolio_index', [
                        'roomId' => $roomId,
                    ]);
                }
            }
        }

        return $this->redirectToRoute('app_'.$itemType.'_detail', ['roomId' => $roomId, 'itemId' => $itemId]);
    }

    /**
     * @return JsonResponse
     */
    #[Route(path: '/room/{roomId}/annotation/{itemId}/delete', methods: ['GET'])]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function deleteAction(
        ItemService $itemService,
        int $itemId
    ): Response {
        $item = $itemService->getTypedItem($itemId);
        $item->delete();
        $response = new JsonResponse();
        $response->setData([
            'deleted' => true,
        ]);

        return $response;
    }
}
