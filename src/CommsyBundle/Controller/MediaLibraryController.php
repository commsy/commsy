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

        // TODO: implement
        $itemsCountArray = [
            'count' => 11,
            'countAll' => 22,
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
        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $fileManager = $legacyEnvironment->getFileManager();
        $file = $fileManager->getItem(1);

        $media = [];

        for ($i = 0; $i < 20; $i++) {
            $media[] = $file;
        }

        return [
            'roomId' => $roomId,
            'media' => $media,
//            'readerList' => $readerList,
//            'showRating' => $current_context->isAssessmentActive(),
//            'ratingList' => $ratingList,
//            'allowedActions' => $allowedActions,
        ];
    }
}