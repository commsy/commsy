<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use CommsyBundle\Form\Type\AnnotationType;

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
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
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

        $readerService = $this->get('commsy.reader_service');

        $readerList = array();
        foreach ($annotations as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
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
                $item = $transformer->applyTransformation($item, $form->getData());
                $item->save();
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
     * @Security("is_granted('ITEM_EDIT', itemId)")
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
                $annotationService->addAnnotation($roomId, $itemId, $data['description']);
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
}
