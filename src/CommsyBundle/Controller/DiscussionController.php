<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Filter\DiscussionFilterType;

class DiscussionController extends Controller
{
    /**
     * @Route("/room/{roomId}/dicussion/feed/{start}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(new DiscussionFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_discussion_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $discussionService = $this->get('commsy_legacy.discussion_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $discussionService->setFilterConditions($filterForm);
        }

        // get material list from manager service 
        $discussions = $discussionService->getListDiscussions($roomId, $max, $start);

        $readerService = $this->get('commsy.reader_service');

        $readerList = array();
        foreach ($discussions as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
        }

        return array(
            'roomId' => $roomId,
            'discussions' => $discussions,
            'readerList' => $readerList
        );
    }
    
    /**
     * @Route("/room/{roomId}/discussion")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(new DiscussionFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_discussion_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $discussionService = $this->get('commsy_legacy.discussion_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $discussionService->setFilterConditions($filterForm);
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
        );
    }
    
    /**
     * @Route("/room/{roomId}/discussion/{itemId}")
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        $discussionService = $this->get('commsy_legacy.discussion_service');
        $itemService = $this->get('commsy.item_service');
        
        $discussion = $discussionService->getDiscussion($itemId);
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $discussion;
        $reader_manager = $legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if(empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed_manager = $legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if(empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }


        $discussionArticleList = $discussion->getAllArticles()->to_array();
        
        $itemArray = array($discussion);
        $itemArray = array_merge($itemArray, $discussionArticleList);

        $readerService = $this->get('commsy.reader_service');
        
        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
            
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }
        
        return array(
            'roomId' => $roomId,
            'discussion' => $discussionService->getDiscussion($itemId),
            'discussionArticleList' => $discussionArticleList,
            'readerList' => $readerList,
            'modifierList' => $modifierList
        );
    }
}
