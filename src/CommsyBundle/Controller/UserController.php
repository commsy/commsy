<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use CommsyBundle\Filter\UserFilterType;

class UserController extends Controller
{
    /**
     * @Route("/room/{roomId}/user")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $form = $this->createForm(new UserFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_user_list', array('roomId' => $roomId)),
            'method' => 'GET',
        ));

        // check query for form data
        if ($request->query->has($form->getName())) {
            // manually bind values from the request
            $form->submit($request->query->get($form->getName()));
        }

        return array(
            'roomId' => $roomId,
            'form' => $form->createView(),
        );
    }
    
    /**
     * @Route("/room/{roomId}/user/feed/{start}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $form = $this->createForm(new UserFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_user_list', array('roomId' => $roomId)),
            'method' => 'GET',
        ));

        // check query for form data
        if ($request->query->has($form->getName())) {
            // manually bind values from the request
            $form->submit($request->query->get($form->getName()));
        }

        // get the material manager service
        $userService = $this->get('commsy.user_service');

        // set filter conditions in material manager
        $userService->setFilterConditions($form);

        // get material list from manager service 
        $users = $userService->getListUsers($roomId, $max, $start);

        $readerService = $this->get('commsy.reader_service');

        $readerList = array();
        foreach ($users as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
        }

        return array(
            'roomId' => $roomId,
            'users' => $users,
            'readerList' => $readerList
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
        $rootDir = $this->get('kernel')->getRootDir().'/../';

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
            $path = $kernel->locateResource('@CommsyBundle/Resources/public/images/user_unknown.gif');          
            $content = file_get_contents($path);
        }
        $response = new Response($content, Response::HTTP_OK, array('content-type' => 'image'));
        
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE,$file);

        $response->headers->set('Content-Disposition', $contentDisposition);
        
        return $response;
    }
}
