<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Filter\CopyFilterType;
use CommsyBundle\Form\Type\AnnotationType;
use CommsyBundle\Form\Type\AnnouncementType;

use \ZipArchive;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


class CopyController extends Controller
{
    /**
     * @Route("/room/{roomId}/announcement/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0,  $sort = 'date', Request $request)
    {
        // extract current filter from parameter bag (embedded controller call)
        // or from query paramters (AJAX)
        $copyFilter = $request->get('copyFilter');
        if (!$copyFilter) {
            $copyFilter = $request->query->get('copy_filter');
        }
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // get the announcement manager service
        $copyService = $this->get('commsy.copy_service');

        if ($copyFilter) {
            // setup filter form
            
            $roomService = $this->get('commsy_legacy.room_service');
            $rubrics = $roomService->getRubricInformation($roomId);
            $rubrics = array_combine($rubrics, $rubrics);
            
            $defaultFilterValues = array(
            );
            $filterForm = $this->createForm(CopyFilterType::class, $defaultFilterValues, array(
                'action' => $this->generateUrl('commsy_copy_list', array(
                    'roomId' => $roomId,
                )),
                'rubrics' => $rubrics,
            ));
    
            // manually bind values from the request
            $filterForm->submit($copyFilter);
    
            // apply filter
            $copyService->setFilterConditions($filterForm);
        } else {
            
        }

        // get announcement list from manager service 
        $entries = $copyService->getListEntries($roomId, $max, $start, $sort);

        $readerService = $this->get('commsy_legacy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentContext = $legacyEnvironment->getCurrentContextItem();

        $roomService = $this->get('commsy_legacy.room_service');
        $rubrics = $roomService->getRubricInformation($roomId);

        $stackRubrics = ['date', 'material', 'discussion', 'todo'];

        $readerList = array();
        $allowedActions = array();
        foreach ($entries as $item) {
            if (in_array($item->getItemType(), $rubrics)) {
                $allowedActions[$item->getItemID()][] = 'insert';
            }
            if (in_array($item->getItemType(), $stackRubrics)) {
                $allowedActions[$item->getItemID()][] = 'insertStack';
            }
            $allowedActions[$item->getItemID()][] = 'remove';
        }

        return array(
            'roomId' => $roomId,
            'entries' => $entries,
            'allowedActions' => $allowedActions,
       );
    }
    
    /**
     * @Route("/room/{roomId}/copy")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $roomService = $this->get('commsy_legacy.room_service');
        $rubrics = $roomService->getRubricInformation($roomId);
        $rubrics = array_combine($rubrics, $rubrics);

        $defaultFilterValues = array(
        );
        $filterForm = $this->createForm(CopyFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_copy_list', array(
                'roomId' => $roomId,
            )),
            'rubrics' => $rubrics,
        ));

        // get the announcement manager service
        $copyService = $this->get('commsy.copy_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in announcement manager
            $copyService->setFilterConditions($filterForm);
        }

        // get announcement list from manager service 
        $itemsCountArray = $copyService->getCountArray($roomId);
        
        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'copy',
            'itemsCountArray' => $itemsCountArray,
            'usageInfo' => null,
        );
    }

    /**
     * @Route("/room/{roomId}/announcement/feedaction")
     */
    public function feedActionAction($roomId, Request $request)
    {
        $translator = $this->get('translator');
        
        $action = $request->request->get('act');
        
        $selectedIds = $request->request->get('data');
        if (!is_array($selectedIds)) {
            $selectedIds = json_decode($selectedIds);
        }
        
        $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bolt\'></i> '.$translator->trans('action error');
        
        $result = [];
        
        if ($action == 'insert') {
            
            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> '.$translator->transChoice('marked %count% entries as read',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'insertStack') {

            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-copy\'></i> '.$translator->transChoice('%count% copied entries',count($selectedIds), array('%count%' => count($selectedIds)));
        } else if ($action == 'remove') {

            $message = '<i class=\'uk-icon-justify uk-icon-medium uk-icon-copy\'></i> '.$translator->transChoice('%count% copied entries',count($selectedIds), array('%count%' => count($selectedIds)));
        } 
        
        return new JsonResponse([
            'message' => $message,
            'timeout' => '5550',
            'layout' => 'cs-notify-message',
            'data' => $result,
        ]);
    }

}
