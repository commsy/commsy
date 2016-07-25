<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\JsonResponse;

use CommsyBundle\Form\Type\ProjectType;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use CommsyBundle\Filter\ProjectFilterType;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormError;

class ProjectController extends Controller
{    
    /**
     * @Route("/room/{roomId}/project/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'date', Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(ProjectFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_project_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $projectService = $this->get('commsy_legacy.project_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $dateService->setFilterConditions($filterForm);
        }

        // get material list from manager service 
        $projects = $projectService->getListProjects($roomId, $max, $start, $sort);

        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = array();
        foreach ($projects as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
        }

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $currentUser = $legacyEnvironment->getCurrentUser();

        return array(
            'roomId' => $roomId,
            'projects' => $projects,
            'readerList' => $readerList,
            'currentUser' => $currentUser
        );
    }
    
    /**
     * @Route("/room/{roomId}/project")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(ProjectFilterType::class, $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_project_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $projectService = $this->get('commsy_legacy.project_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $projectService->setFilterConditions($filterForm);
        }

        $itemsCountArray = $projectService->getCountArray($roomId);

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'project',
            'itemsCountArray' => $itemsCountArray
        );
    }
    
    
    /**
     * @Route("/room/{roomId}/project/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($itemId);
        
        $currentUser = $legacyEnvironment->getCurrentUser();
        
        return array(
            'roomId' => $roomId,
            'item' => $roomItem,
            'currentUser' => $currentUser
        );
    }
    
    function memberStatus ($item) {
        $status = 'closed';
        
        $current_user = $this->_environment->getCurrentUserItem();
         if ($current_user->isRoot()) {
            $may_enter = true;
         } elseif ( !empty($room_user) ) {
            $may_enter = $item->mayEnter($room_user);
         } else {
            $may_enter = false;
         }
         //$html .= '<div style="float:right; width:15em; padding:5px; vertical-align: middle; text-align: center;">'.LF;

         // Eintritt erlaubt
         if ($may_enter) {
            //$actionCurl = curl( $item->getItemID(),
            //                 'home',
            //                 'index',
            //                 '');
            //if (!$this->isPrintableView()) {
            //   $html .= '<a class="room_window" href="'.$actionCurl.'"><img alt="door" src="images/door_open_large.gif"/></a>'.BRLF;
            //} else {
               $html .= '<img alt="door" src="images/door_open_large.gif" style="vertical-align: large;"/>'.BRLF;
            //}
            if ($item->isOpen()) {
               //$actionCurl = curl( $item->getItemID(),
               //                 'home',
               //                 'index',
               //                 '');
               //$html .= '<div style="margin-top: 5px; padding:3px; text-align:left;">';
               //if (!$this->isPrintableView()) {
               //  $html .= '<div style="padding-top:5px; text-align: center;">'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_ENTER').'</a></div>'.LF;
               //} else {
                  $html .= '<div style="padding-top:5px; text-align: center;">'.$this->_translator->getMessage('CONTEXT_ENTER').'</div>'.LF;
               //}
            } else {
               $html .= '<div style="padding-top:3px; text-align: center;"><span class="disabled">'.$this->_translator->getMessage('CONTEXT_JOIN').'</span></div>'.LF;
            }
            //$html .= '</div>';

         } elseif ( $item->isLocked() ) {
            $html .= '<img alt="door" src="images/door_closed_large.gif" style="vertical-align: middle; "/>'.LF;
         //Um Erlaubnis gefragt
         } elseif(!empty($room_user) and $room_user->isRequested()) {
            $html .= '<img alt="door" src="images/door_closed_large.gif" style="vertical-align: large; "/>'.LF;
            //$html .= '<div style="xborder: 2px solid '.$color_array['tabs_background'].'; margin-top: 5px; padding:3px; text-align:left;">';
            //$html .= '<div style="padding-top:0px; text-align: center;"><p style=" margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">'.$this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED_YET').'</p></div>'.LF;
           //$html.= '</div>';

         //Erlaubnis verweigert
         } elseif(!empty($room_user) and $room_user->isRejected()) {
            $html .= '<img alt="door" src="images/door_closed_large.gif" style="vertical-align: large; "/>'.LF;
            //$html .= '<div style="xborder: 2px solid '.$color_array['tabs_background'].'; margin-top: 5px; padding:3px; text-align:left;">';
            //$html .= '<div style="padding-top:0px; text-align: center;"><p style=" margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">'.$this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED').'</p></div>'.LF;
           //$html.= '</div>';

         // noch nicht angemeldet als Mitglied im Raum
         } else {
            $html .= '<img alt="door" src="images/door_closed_large.gif" style="vertical-align: middle text-align:left;"/>'.BRLF;
            //$html .= '<div style="xborder: 2px solid '.$color_array['tabs_background'].'; margin-top: 5px; padding:3px; text-align:center;">';
            $current_user_item_read = $this->_environment->getCurrentUserItem();
            if ( $item->isOpen()
                 and !$current_user_item_read->isOnlyReadUser()
               ) {
               if ( $this->_environment->inPortal() ) {
                  $params['account'] = 'member';
                  $params['room_id'] = $this->_item->getItemID();
                  $actionCurl = curl( $this->_environment->getCurrentPortalID(),
                                      'home',
                                      'index',
                                      $params,
                                      '');
               } else {
                  $params['account'] = 'member';
                  $params['iid'] = $this->_item->getItemID();
                  $actionCurl = curl( $this->_environment->getCurrentContextID(),
                                      $this->_environment->getCurrentModule(),
                                      $this->_environment->getCurrentFunction(),
                                      $params,
                                      '');
               }
               if (!$this->isPrintableView()) {
                  $html .= '<div style="padding-top:5px; text-align: center;">'.'<a class="room_window" href="'.$actionCurl.'">'.$this->_translator->getMessage('CONTEXT_JOIN').'</a></div>'.LF;
               } else {
                  $html .= '<div style="padding-top:5px; text-align: center;">'.$this->_translator->getMessage('CONTEXT_JOIN').'</div>'.LF;
               }
               unset($params);
            } else {
               $html .= '<div style="padding-top:3px; text-align: center;"><span class="disabled">'.$this->_translator->getMessage('CONTEXT_JOIN').'</span></div>'.LF;
            }
            $html.= '</div>';
         }
    }
}
