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
use App\Rubric\Annotation\AnnotationDeleter;
use App\Services\LegacyEnvironment;
use App\Utils\AnnotationService;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ITEM_ENTER', subject: 'roomId')]
class AnnotationController extends AbstractController
{
    #[Route(path: '/room/{roomId}/annotation/feed/{linkedItemId}/{start}')]
    public function feed(
        AnnotationService $annotationService,
        ItemService $itemService,
        ReaderService $readerService,
        int $roomId,
        int $linkedItemId,
        int $max = 10,
        int $start = 0
    ): Response {
        // get annotation list from manager service
        $annotations = $annotationService->getListAnnotations($roomId, $linkedItemId, $max, $start);

        $readerList = $readerService->getChangeStatusForItems(...$annotations);

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
    public function feedPrint(
        AnnotationService $annotationService,
        ReaderService $readerService,
        int $roomId,
        int $linkedItemId,
        int $max = 10,
        int $start = 0
    ): Response {
        // get annotation list from manager service
        $annotations = $annotationService->getListAnnotations($roomId, $linkedItemId, $max, $start);

        $readerList = $readerService->getChangeStatusForItems(...$annotations);

        return $this->render('annotation/feed_print.html.twig', [
            'roomId' => $roomId,
            'annotations' => $annotations,
            'readerList' => $readerList,
        ]);
    }

    #[Route(path: '/room/{roomId}/annotation/{itemId}/edit', methods: ['GET', 'POST'])]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function edit(
        ItemService $itemService,
        AnnotationTransformer $transformer,
        ReaderService $readerService,
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
                $item = $transformer->applyTransformation($item, $form->getData());
                $item->save();

                $readerService->markItemWithVersionAsRead($item);
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
    public function success(
        ItemService $itemService,
        /** @noinspection PhpUnusedParameterInspection This argument is used as a subject for the #[IsGranted] attribute */
        int $roomId,
        int $itemId
    ): Response {
        $item = $itemService->getTypedItem($itemId);

        return $this->render('annotation/success.html.twig', [
            'annotation' => $item,
        ]);
    }

    #[Route(path: '/room/{roomId}/annotation/{itemId}/create', methods: ['POST'])]
    #[IsGranted('ITEM_ANNOTATE', subject: 'itemId')]
    public function create(
        ItemService $itemService,
        AnnotationService $annotationService,
        Request $request,
        int $roomId,
        int $itemId,
        ?int $firstTagId = null,
        ?int $secondTagId = null
    ): RedirectResponse {
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

                return $this->redirectToRoute('app_'.$itemType.'_detail', $routeArray);
            }
        }

        return $this->redirectToRoute('app_'.$itemType.'_detail', ['roomId' => $roomId, 'itemId' => $itemId]);
    }

    #[Route(path: '/room/{roomId}/annotation/{itemId}/delete', methods: ['GET'])]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function delete(
        AnnotationDeleter $annotationDeleter,
        LegacyEnvironment $legacyEnvironment,
        int $itemId,
        /** @noinspection PhpUnusedParameterInspection */
        int $roomId
    ): JsonResponse {
        $deleterId = (int) $legacyEnvironment->getEnvironment()->getCurrentUserItem()?->getItemID();
        $annotationDeleter->deleteItem($itemId, $deleterId);

        $response = new JsonResponse();
        $response->setData([
            'deleted' => true,
        ]);

        return $response;
    }
}
