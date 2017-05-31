<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use CommsyBundle\Filter\MediaLibraryFilterType;

class MediaLibraryController extends Controller
{
    /**
     * @Route("/room/{roomId}/medialibrary")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createForm(MediaLibraryFilterType::class, [], [
            'action' => $this->generateUrl('commsy_medialibrary_list', [
                'roomId' => $roomId,
            ]),
        ]);

        $repository = $this->getDoctrine()->getRepository('CommsyBundle:Files');

        $query = $repository->createQueryBuilder('f')
            ->select('count(f.filesId)')
            ->where('f.deleterId IS NULL')
            ->andWhere('f.deletionDate IS NULL')
            ->andWhere('f.contextId = :contextId')
            ->setParameter('contextId', $roomId)
            ->getQuery();

        // TODO: implement
        $itemsCountArray = [
            'count' => $query->getSingleScalarResult(),
            'countAll' => $query->getSingleScalarResult(),
        ];


        return [
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'medialibrary',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
//            'showHashTags' => $roomItem->withBuzzwords(),
//            'showCategories' => $roomItem->withTags(),
//            'usageInfo' => $usageInfo,
        ];
    }

    /**
     * @Route("/room/{roomId}/medialibrary/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'date', Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('CommsyBundle:Files');

        $query = $repository->createQueryBuilder('f')
            ->where('f.deleterId IS NULL')
            ->andWhere('f.deletionDate IS NULL')
            ->andWhere('f.contextId = :contextId')
            ->setParameter('contextId', $roomId)
            ->setFirstResult($start)
            ->setMaxResults($max)
            ->getQuery();

        $files = $query->getResult();

        return [
            'roomId' => $roomId,
            'files' => $files,
//            'readerList' => $readerList,
//            'showRating' => $current_context->isAssessmentActive(),
//            'ratingList' => $ratingList,
//            'allowedActions' => $allowedActions,
        ];
    }
}