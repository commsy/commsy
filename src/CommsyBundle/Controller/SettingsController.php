<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Entity\Room;
use CommsyBundle\Form\Type\GeneralSettingsType;
use CommsyBundle\Form\Type\ModerationSettingsType;
use CommsyBundle\Form\Type\AppearanceSettingsType;
use CommsyBundle\Form\Type\ExtensionSettingsType;

use Ivory\CKEditorBundle\Form\Type\CKEditorType;

class SettingsController extends Controller
{
    /**
    * @Route("/room/{roomId}/settings/general")
    * @Template
    * @Security("is_granted('MODERATOR')")
    */
    public function generalAction($roomId, Request $request)
    {
        // get room from RoomService
        $roomService = $this->get('commsy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);
	
        // $room = $this->getDoctrine()
        //     ->getRepository('CommsyBundle:Room')
        //     ->find($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.room');
        $roomData = $transformer->transform($roomItem);

        $form = $this->createForm(GeneralSettingsType::class, $roomData, array(
            'roomId' => $roomId,
            'uploadUrl' => $this->generateUrl('commsy_upload_upload', array(
                'roomId' => $roomId,
            )),
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());

            // TODO: should this be used for normal file uploads (materials etc.) while bg images are saved into specific theme subfolders?
            $file = $form['room_image']->getData();
            $filesDir = $this->getParameter('files_directory');  

            $roomDir = implode( "/", array_filter(explode("\r\n", chunk_split(strval($roomId), "4")), 'strlen') );
            $saveDir = $filesDir . "/" . $roomItem->portalId . "/" . $roomDir . "_";

            if(!is_dir($saveDir)){
                mkdir($saveDir, 0777, true);
            } 

            $extension = $file->guessExtension();
            if(!$extension) {
                $extension = "bin";
            }

            $fileName = md5(uniqid()).'_bgimage.'.$extension;

            $file->move($saveDir, $fileName);
            //$file->move($saveDir, $file->getClientOriginalName());

            $roomItem->save();

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/room/{roomId}/settings/moderation")
     * @Template
     * @Security("is_granted('MODERATOR')")
     */
    public function moderationAction($roomId, Request $request)
    {
        dump($roomId);
        dump($request);
        $roomService = $this->get('commsy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.room');
        $roomData = $transformer->transform($roomItem);

        $form = $this->createForm(ModerationSettingsType::class, $roomData, array(
            'roomId' => $roomId,
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());
      
            $roomItem->save();

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        return array(
            'form' => $form->createView()
        );

    }

    /**
     * @Route("/room/{roomId}/settings/appearance")
     * @Template
     * @Security("is_granted('MODERATOR')")
     */
    public function appearanceAction($roomId, Request $request)
    {
        // get room from RoomService
        $roomService = $this->get('commsy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.room');
        $roomData = $transformer->transform($roomItem);

        // get the configured LiipThemeBundle themes
        $themeArray = $this->container->getParameter('liip_theme.themes');

        $form = $this->createForm(AppearanceSettingsType::class, $roomData, array(
            'roomId' => $roomId,
            'themes' => $themeArray,
            // 'uploadUrl' => $this->generateUrl('commsy_upload_upload', array(
            //     'roomId' => $roomId,
            // )),
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            //$roomItem = $transformer->applyTransformation($roomItem, $form->getData());

            //$roomItem->save();

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
        }

        return array(
            'form' => $form->createView()
        );
    }
    
    /**
     * @Route("/room/{roomId}/settings/extensions")
     * @Template
     * @Security("is_granted('MODERATOR')")
     */
    public function extensionsAction($roomId, Request $request)
    {
        // get room from RoomService
        $roomService = $this->get('commsy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.room');
        $roomData = $transformer->transform($roomItem);

        // get the configured LiipThemeBundle themes
        $mediaWikiUrl = $this->container->getParameter('commsy.mediawiki.url');

        $form = $this->createForm(ExtensionSettingsType::class, $roomData, array(
            'roomId' => $roomId,
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {

            if ($form->get('save')->isClicked()) {
                $formData = $form->getData();
                
                if ($formData['wikiEnabled']) {
                    if (!$roomItem->isWikiEnabled()) {
                        $roomItem->setWikiEnabled(true);
                        
                        $mediawikiService = $this->get('commsy_mediawiki.mediawiki');
                        $mediawikiService->createWiki($roomId);
                    }
                } else {
                    $roomItem->setWikiEnabled(false);
                }
                
                $roomItem->save();
            }
        }

        return array(
            'form' => $form->createView()
        );
    }
}
