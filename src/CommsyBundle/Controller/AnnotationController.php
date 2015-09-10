<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

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
}
