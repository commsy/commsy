<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Entity\User;
use CommsyBundle\Form\Type\RoomProfileType;
use CommsyBundle\Form\Type\CombineProfileType;

class ProfileController extends Controller
{
    /**
    * @Route("/room/{roomId}/user/{itemId}/general")
    * @Template
    * @Security("is_granted('ITEM_EDIT', itemId)")
    */
    public function generalAction($roomId, $itemId, Request $request)
    {
        $userService = $this->get('commsy_legacy.user_service');
        $roomService = $this->get('commsy_legacy.room_service');
        $userItem = $userService->getUser($itemId);

        if (!$userItem) {
            throw $this->createNotFoundException('No user found for id ' . $itemId);
        }

        $userTransformer = $this->get('commsy_legacy.transformer.user');
        $userData = $userTransformer->transform($userItem);

        $privateRoomTransformer = $this->get('commsy_legacy.transformer.privateroom');
        $privateRoomItem = $userItem->getOwnRoom();
        $privateRoomData = $privateRoomTransformer->transform($privateRoomItem);

        $userData = array_merge($userData, $privateRoomData);

        $form = $this->createForm(RoomProfileType::class, $userData, array(
            'itemId' => $itemId,
            'uploadUrl' => $this->generateUrl('commsy_upload_upload', array(
                'roomId' => $roomId,
                'itemId' => $itemId
            )),
        ));
        
        $formCombine = $this->createForm(CombineProfileType::class, $userData, array(
            'itemId' => $itemId,
        ));
        
        if ($request->request->has('room_profile')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formData = $form->getData();

                // save profile picture if given
                if($formData['image_data']){
                    $saveDir = implode("/", array($this->getParameter('files_directory'), $roomService->getRoomFileDirectory($userItem->getContextID())));
                    $data = $formData['image_data'];
                    list($fileName, $type, $data) = explode(";", $data);
                    list(, $data) = explode(",", $data);
                    list(, $extension) = explode("/", $type);
                    $data = base64_decode($data);
                    $fileName = implode("_", array('cid'.$userItem->getContextID(), $userItem->getUserID(), $fileName));
                    $absoluteFilepath = implode("/", array($saveDir, $fileName));
                    //dump("Saving profile under '" . $absoluteFilepath . "'");
                    file_put_contents($absoluteFilepath, $data);
                    $userItem->setPicture($fileName);
                }
    
                $userItem = $userTransformer->applyTransformation($userItem, $form->getData());
                $userItem->save();

                $userList = $userItem->getRelatedUserList();
                $tempUserItem = $userList->getFirst();
                while ($tempUserItem) {
                    if ($formData['titleChangeInAllContexts']) {
                        $tempUserItem->setTitle($formData['title']);
                    }
                    if ($formData['dateOfBirthChangeInAllContexts']) {
                        $tempUserItem->setBirthday($formData['dateOfBirth']);
                    }
                    if ($formData['imageChangeInAllContexts']) {
                        $discService = $this->get('commsy_legacy.disc_service');
                        $tempFilename = $discService->copyImageFromRoomToRoom($userItem->getPicture(), $tempUserItem->getContextId());
                        if ($tempFilename) {
                            $tempUserItem->setPicture($tempFilename);
                        }
                    }
                    if ($formData['emailChangeInAllContexts']) {
                        $tempUserItem->setEmail($formData['email']);
                    }
                    if ($formData['hideEmailInAllContexts']) {
                        if ($formData['hideEmailInThisRoom']) {
                            $tempUserItem->setEmailNotVisible();
                        } else {
                            $tempUserItem->setEmailVisible();
                        }
                    }
                    if ($formData['phoneChangeInAllContexts']) {
                        $tempUserItem->setTelephone($formData['phone']);
                    }
                    if ($formData['mobileChangeInAllContexts']) {
                        $tempUserItem->setCellularphone($formData['mobile']);
                    }
                    if ($formData['streetChangeInAllContexts']) {
                        $tempUserItem->setStreet($formData['street']);
                    }
                    if ($formData['zipcodeChangeInAllContexts']) {
                        $tempUserItem->setZipcode($formData['zipcode']);
                    }
                    if ($formData['cityChangeInAllContexts']) {
                        $tempUserItem->setCity($formData['city']);
                    }
                    if ($formData['roomChangeInAllContexts']) {
                        $tempUserItem->setRoom($formData['room']);
                    }
                    if ($formData['organisationChangeInAllContexts']) {
                        $tempUserItem->setOrganisation($formData['organisation']);
                    }
                    if ($formData['positionChangeInAllContexts']) {
                        $tempUserItem->setPosition($formData['position']);
                    }
                    if ($formData['icqChangeInAllContexts']) {
                        $tempUserItem->setICQ($formData['icq']);
                    }
                    if ($formData['msnChangeInAllContexts']) {
                        $tempUserItem->setMSN($formData['msn']);
                    }
                    if ($formData['skypeChangeInAllContexts']) {
                        $tempUserItem->setSkype($formData['skype']);
                    }
                    if ($formData['yahooChangeInAllContexts']) {
                        $tempUserItem->setYahoo($formData['yahoo']);
                    }
                    if ($formData['jabberChangeInAllContexts']) {
                        $tempUserItem->setJabber($formData['jabber']);
                    }
                    if ($formData['homepageChangeInAllContexts']) {
                        $tempUserItem->setHomepage($formData['homepage']);
                    }
                    if ($formData['descriptionChangeInAllContexts']) {
                        $tempUserItem->setDescription($formData['description']);
                    }
                    $tempUserItem->save();
                    $tempUserItem = $userList->getNext();    
                }
    
                $privateRoomItem = $privateRoomTransformer->applyTransformation($privateRoomItem, $form->getData());
                
                $privateRoomItem->save();
                
                return $this->redirectToRoute('commsy_profile_general', array('roomId' => $roomId, 'itemId' => $itemId));
            }
        } else if ($request->request->has('combine_profile')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                return $this->redirectToRoute('commsy_profile_general', array('roomId' => $roomId, 'itemId' => $itemId));
            }
        }

        return array(
            'roomId' => $roomId,
            'user' => $userItem,
            'form' => $form->createView(),
            'formCombine' => $formCombine->createView()
        );
    }

    /**
    * @Route("/room/{roomId}/user/profileImage")
    * @Template
    */
    public function imageAction($roomId, Request $request)
    {
        $userService = $this->get('commsy_legacy.user_service');
        return array('user' => $userService->getCurrentUserItem());
    }

}