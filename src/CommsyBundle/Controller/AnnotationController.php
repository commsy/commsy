<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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
     * @Route("/room/{roomId}/annotation/feed/{linkedItemId}/{start}")
     * @Template()
     */
    public function feedAction($roomId, $linkedItemId, $max = 10, $start = 0, Request $request)
    {
        // get the annotation manager service
        $annotationService = $this->get('commsy_legacy.annotation_service');

        // get annotation list from manager service 
        $annotations = $annotationService->getListAnnotations($roomId, $linkedItemId, $max, $start);

        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = array();
        foreach ($annotations as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }


        return array(
            'roomId' => $roomId,
            'annotations' => $annotations,
            'readerList' => $readerList
        );
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

        $readerList = array();
        foreach ($annotations as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }


        return array(
            'roomId' => $roomId,
            'annotations' => $annotations,
            'readerList' => $readerList
        );
    }

    /**
     * @Route("/room/{roomId}/annotation/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        $linkedItem = $item->getLinkedItem();
        $itemType = $linkedItem->getItemType();

        $transformer = $this->get('commsy_legacy.transformer.annotation');

        $formData = array();
        $formData = $transformer->transform($item);

        $form = $this->createForm(AnnotationType::class, $formData);
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                $readerManager = $legacyEnvironment->getReaderManager();
                $noticedManager = $legacyEnvironment->getNoticedManager();

                $item = $transformer->applyTransformation($item, $form->getData());
                $item->save();

                $readerManager->markRead($itemId, 0);
                $noticedManager->markNoticed($itemId, 0);
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }

            return $this->redirectToRoute('commsy_annotation_success', array('roomId' => $roomId, 'itemId' => $itemId));

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        return array(
            'itemId' => $itemId,
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/room/{roomId}/annotation/{itemId}/success")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function successAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        return array('annotation' => $item);
    }

    /**
     * @Route("/room/{roomId}/annotation/{itemId}/create")
     * @Template()
     * @Security("is_granted('ITEM_ANNOTATE', itemId)")
     */
    public function createAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        $annotationService = $this->get('commsy_legacy.annotation_service');

        $itemType = $item->getItemType();

        $form = $this->createForm(AnnotationType::class);
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $data = $form->getData();

                // create new annotation
                $annotationId = $annotationService->addAnnotation($roomId, $itemId, $data['description']);
                return $this->redirectToRoute('commsy_'.$itemType.'_detail', array('roomId' => $roomId, 'itemId' => $itemId, '_fragment' => 'description' . $annotationId));
            }
        }
        return $this->redirectToRoute('commsy_'.$itemType.'_detail', array('roomId' => $roomId, 'itemId' => $itemId));
    }

    // /**
    //  * @Route("/room/{roomId}/annotation/{itemId}/save")
    //  * @Template()
    //  * @Security("is_granted('ITEM_EDIT', itemId)")
    //  */
    // public function saveAction($roomId, $itemId, Request $request)
    // {
    //     $itemService = $this->get('commsy_legacy.item_service');
    //     $item = $itemService->getTypedItem($itemId);

    //     $annotationService = $this->get('commsy_legacy.annotation_service');

    //     $linkedItem = $item->getLinkedItem();
    //     $itemType = $linkedItem->getItemType();

    //     $form = $this->createForm(AnnotationType::class);
    //     $form->handleRequest($request);
    //     if ($form->isValid()) {
    //         if ($form->get('save')->isClicked()) {
    //             $data = $form->getData();

    //             // create new annotation
    //             $annotationService->addAnnotation($roomId, $itemId, $data['description']);
    //         }
    //     }

    //     return $this->redirectToRoute('commsy_'.$itemType.'_detail', array('roomId' => $roomId, 'itemId' => $linkedItem->getItemId()));
    // }

    /**
     * @Route("/room/{roomId}/annotation/{itemId}/delete")
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
