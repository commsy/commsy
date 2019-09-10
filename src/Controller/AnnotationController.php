<?php

namespace App\Controller;

use App\Services\LegacyEnvironment;
use App\Utils\PortfolioService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Form\Type\AnnotationType;
use App\Utils\AnnotationService;
use App\Utils\ItemService;
use App\Utils\ReaderService;

/**
 * Class AnnotationController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class AnnotationController extends AbstractController
{
    /**
     * @Route("/room/{roomId}/annotation/feed/{linkedItemId}/{start}/{firstTagId}/{secondTagId}")
     * @Template()
     * @param $roomId
     * @param $linkedItemId
     * @param int $max
     * @param int $start
     * @param null $firstTagId
     * @param null $secondTagId
     * @param Request $request
     * @param AnnotationService $annotationService
     * @param ItemService $itemService
     * @param ReaderService $readerService
     * @return array
     */
    public function feedAction($roomId, $linkedItemId, $max = 10, $start = 0, $firstTagId = null, $secondTagId = null, Request $request, AnnotationService $annotationService, ItemService $itemService, ReaderService $readerService)
    {
        // get annotation list from manager service 
        $annotations = $annotationService->getListAnnotations($roomId, $linkedItemId, $max, $start);

        if ($firstTagId && $secondTagId) {
            $portfolioService = $this->get(PortfolioService::class);
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


        return [
            'roomId' => $roomId,
            'annotations' => $annotations,
            'readerList' => $readerList,
        ];
    }

    /**
     * @Route("/room/{roomId}/annotation/feed/{linkedItemId}/{start}")
     * @Template()
     * @param $roomId
     * @param $linkedItemId
     * @param int $max
     * @param int $start
     * @param Request $request
     * @param AnnotationService $annotationService
     * @param ReaderService $readerService
     * @return array
     */
    public function feedPrintAction($roomId, $linkedItemId, $max = 10, $start = 0, Request $request, AnnotationService $annotationService, ReaderService $readerService)
    {
        // get annotation list from manager service 
        $annotations = $annotationService->getListAnnotations($roomId, $linkedItemId, $max, $start);

        $readerList = [];
        foreach ($annotations as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        return [
            'roomId' => $roomId,
            'annotations' => $annotations,
            'readerList' => $readerList,
        ];
    }

    /**
     * @Route("/room/{roomId}/annotation/{itemId}/edit")
     * @Method({"GET", "POST"})
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     * @param $roomId
     * @param $itemId
     * @param Request $request
     * @param ItemService $itemService
     * @param LegacyEnvironment $environment
     * @return array|RedirectResponse
     */
    public function editAction($roomId, $itemId, Request $request, ItemService $itemService, LegacyEnvironment $environment)
    {
        $item = $itemService->getTypedItem($itemId);

        $transformer = $this->get('commsy_legacy.transformer.annotation');

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

        return [
            'itemId' => $itemId,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/room/{roomId}/annotation/{itemId}/success")
     * @Method({"GET"})
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     * @param $roomId
     * @param $itemId
     * @param Request $request
     * @param ItemService $itemService
     * @return array
     */
    public function successAction($roomId, $itemId, Request $request, ItemService $itemService)
    {
        $item = $itemService->getTypedItem($itemId);
        return [
            'annotation' => $item,
        ];
    }

    /**
     * @Route("/room/{roomId}/annotation/{itemId}/create/{firstTagId}/{secondTagId}")
     * @Method({"POST"})
     * @Template()
     * @Security("is_granted('ITEM_ANNOTATE', itemId)")
     * @param $roomId
     * @param $itemId
     * @param null $firstTagId
     * @param null $secondTagId
     * @param Request $request
     * @param ItemService $itemService
     * @param AnnotationService $annotationService
     * @return RedirectResponse
     */
    public function createAction($roomId, $itemId, $firstTagId = null, $secondTagId = null, Request $request, ItemService $itemService, AnnotationService $annotationService)
    {
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
                $routeArray['_fragment'] = 'description' . $annotationId;
                if ($itemType == 'portfolio') {
                    $routeArray['portfolioId'] = $itemId;
                    $routeArray['firstTagId'] = $firstTagId;
                    $routeArray['secondTagId'] = $secondTagId;

                    $portfolioService = $this->get(PortfolioService::class);
                    $cellCoordinates = $portfolioService->getCellCoordinatesForTagIds($itemId, $firstTagId, $secondTagId);
                    if (!empty($cellCoordinates)) {
                        $portfolioService->setPortfolioAnnotation($itemId, $annotationId, $cellCoordinates[0], $cellCoordinates[1]);
                    }
                }

                return $this->redirectToRoute('app_'.$itemType.'_detail', $routeArray);
            }
        }
        return $this->redirectToRoute('app_'.$itemType.'_detail', array('roomId' => $roomId, 'itemId' => $itemId));
    }

    /**
     * @Route("/room/{roomId}/annotation/{itemId}/delete")
     * @Method({"GET"})
     * @Security("is_granted('ITEM_EDIT', itemId)")
     * @param $roomId
     * @param $itemId
     * @param Request $request
     * @param ItemService $itemService
     * @return JsonResponse
     */
    public function deleteAction($roomId, $itemId, Request $request, ItemService $itemService)
    {
        $item = $itemService->getTypedItem($itemId);
        $item->delete();
        $response = new JsonResponse();
        $response->setData([
            'deleted' => true,
        ]);
        return $response;
    }
}
