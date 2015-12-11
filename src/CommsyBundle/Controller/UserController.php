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

use CommsyBundle\Filter\UserFilterType;

use \ZipArchive;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;


class UserController extends Controller
{
    /**
     * @Route("/room/{roomId}/user/feed/{start}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        // setup filter form
        $defaultFilterValues = array(
            'activated' => true,
        );
        $filterForm = $this->createForm(new UserFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_user_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => false,
            'hasCategories' => false,
        ));

        // get the user manager service
        $userService = $this->get('commsy.user_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in user manager
            $userService->setFilterConditions($filterForm);
        }

        // get user list from manager service 
        $users = $userService->getListUsers($roomId, $max, $start);
        $readerService = $this->get('commsy.reader_service');
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();


        $readerList = array();
        foreach ($users as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }


        return array(
            'roomId' => $roomId,
            'users' => $users,
            'readerList' => $readerList,
            'showRating' => false,
       );
    }
    
    /**
     * @Route("/room/{roomId}/user")
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



       // get the user manager service
        $userService = $this->get('commsy.user_service');
        $defaultFilterValues = array(
            'activated' => true,
        );
        $filterForm = $this->createForm(new UserFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_user_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => false,
            'hasCategories' => false,
        ));

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in user manager
            $userService->setFilterConditions($filterForm);
        }

        // get user list from manager service 
        $itemsCountArray = $userService->getCountArray($roomId);




        // setup filter form
        $defaultFilterValues = array(
            'activated' => true,
        );
        $filterForm = $this->createForm(new UserFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_user_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => false,
            'hasCategories' => false,
        ));

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();


        // get the user manager service
        $userService = $this->get('commsy.user_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in user manager
            $userService->setFilterConditions($filterForm);
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'user',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
            'showHashTags' => false,
            'showCategories' => false,
        );
    }


    
    /**
     * @Route("/room/{roomId}/user/{itemId}")
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        // get room user list
        $userService = $this->get("commsy.user_service");
        $user = $userService->getUser($itemId);
        
        return array(
            'roomId' => $roomId,
            'user' => $user
        );
    }
    
    /**
     * @Route("/room/{roomId}/user/{itemId}/image")
     */
    public function imageAction($roomId, $itemId)
    {
        $userService = $this->get('commsy.user_service');
        $user = $userService->getUser($itemId);
        
        $file = $user->getPicture();
        $rootDir = $this->get('kernel')->getRootDir().'/';

        $environment = $this->get("commsy_legacy.environment")->getEnvironment();
        $disc_manager = $environment->getDiscManager();
        $disc_manager->setContextID($roomId);
        $portal_id = $environment->getCurrentPortalID();
        if ( isset($portal_id) and !empty($portal_id) ) {
            $disc_manager->setPortalID($portal_id);
        } else {
            $context_item = $this->getContextItem();
            if ( isset($context_item) ) {
                $portal_item = $context_item->getContextItem();
                if ( isset($portal_item) ) {
                    $disc_manager->setPortalID($portal_item->getItemID());
                    unset($portal_item);
                }
                unset($context_item);
            }
        }
        $filePath = $disc_manager->getFilePath().$file;

        $foundUserImage = true;
        if (file_exists($rootDir.$filePath)) {
            $content = file_get_contents($rootDir.$filePath);
            if (!$content) {
                $foundUserImage = false;
            }
        } else {
            $foundUserImage = false;   
        }
        if (!$foundUserImage) {
            $kernel = $this->get('kernel');
            $path = $kernel->getRootDir() . '/Resources/assets/img/user_unknown.gif';     
            $content = file_get_contents($path);
        }
        $response = new Response($content, Response::HTTP_OK, array('content-type' => 'image'));
        
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE,$file);

        $response->headers->set('Content-Disposition', $contentDisposition);
        
        return $response;
    }

    /**
     * @Route("/room/{roomId}/user/{itemId}/rooms/{start}")
     * @Template("CommsyBundle:Menu:room_list.html.twig")
     */
    public function roomsAction($roomId, $itemId, Request $request, $max = 10, $start = 0)
    {
        $userService = $this->get('commsy.user_service');
        $user = $userService->getUser($itemId);

        // Room list feed
        $rooms = $userService->getRoomList($user);

        return array('roomList' => $rooms);


    }
}
