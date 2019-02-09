<?php

namespace CommsyBundle\Controller;

use Commsy\LegacyBundle\Utils\PortfolioService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Form\Type\AnnotationType;

/**
 * Class AnnotationController
 * @package CommsyBundle\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class AnnotationController extends Controller
{
    /**
     * @Route("/room/{roomId}/annotation/feed/{linkedItemId}/{start}/{firstTagId}/{secondTagId}")
     * @Template()
     */
    public function feedAction($roomId, $linkedItemId, $max = 10, $start = 0, $firstTagId = null, $secondTagId = null, Request $request)
    {
        // get the annotation manager service
        $annotationService = $this->get('commsy_legacy.annotation_service');

        // get annotation list from manager service 
        $annotations = $annotationService->getListAnnotations($roomId, $linkedItemId, $max, $start);

        if ($firstTagId && $secondTagId) {
            $portfolioService = $this->get(PortfolioService::class);
            $cellCoordinates = $portfolioService->getCellCoordinatesForTagIds($linkedItemId, $firstTagId, $secondTagId);
            if (!empty($cellCoordinates)) {
                $itemService = $this->get('commsy_legacy.item_service');

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

        $readerService = $this->get('commsy_legacy.reader_service');

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
     */
    public function feedPrintAction($roomId, $linkedItemId, $max = 10, $start = 0, Request $request)
    {
        // get the annotation manager service
        $annotationService = $this->get('commsy_legacy.annotation_service');

        // get annotation list from manager service 
        $annotations = $annotationService->getListAnnotations($roomId, $linkedItemId, $max, $start);

        $readerService = $this->get('commsy_legacy.reader_service');

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
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        $transformer = $this->get('commsy_legacy.transformer.annotation');

        $formData = $transformer->transform($item);

        $form = $this->createForm(AnnotationType::class, $formData);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                $readerManager = $legacyEnvironment->getReaderManager();
                $noticedManager = $legacyEnvironment->getNoticedManager();

                $item = $transformer->applyTransformation($item, $form->getData());
                $item->save();

                $readerManager->markRead($itemId, 0);
                $noticedManager->markNoticed($itemId, 0);
            }

            return $this->redirectToRoute('commsy_annotation_success', [
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
     */
    public function successAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
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
     */
    public function createAction($roomId, $itemId, $firstTagId = null, $secondTagId = null, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        $annotationService = $this->get('commsy_legacy.annotation_service');

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

                return $this->redirectToRoute('commsy_'.$itemType.'_detail', $routeArray);
            }
        }
        return $this->redirectToRoute('commsy_'.$itemType.'_detail', array('roomId' => $roomId, 'itemId' => $itemId));
    }

    /**
     * @Route("/room/{roomId}/annotation/{itemId}/delete")
     * @Method({"GET"})
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function deleteAction($roomId, $itemId, Request $request)
    {

        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        $item->delete();

        $response = new JsonResponse();

        $response->setData([
            'deleted' => true,
        ]);

        return $response;
    }
}
