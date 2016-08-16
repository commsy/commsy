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
use CommsyBundle\Form\Type\AdditionalSettingsType;
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
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);
	
        // $room = $this->getDoctrine()
        //     ->getRepository('CommsyBundle:Room')
        //     ->find($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.general_settings');
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
            // TODO: add constraintGroup so that 'room_image' is mandatory when 'custom_image' is selected (or load previous custom image, if present)

            $room_image_data = $form['room_image']->getData();

            if($room_image_data['choice'] == 'custom_image') {
                if(!is_null($room_image_data['room_image_data'])){
                    $saveDir = $this->getParameter('files_directory') . "/" . $roomService->getRoomFileDirectory($roomId);
                    if(!is_dir($saveDir)){
                        mkdir($saveDir, 0777, true);
                    }
                    $file = $room_image_data['room_image_upload'];
                    $fileName = "";
                    // case 1: file was send as "input file" via "room_image_upload" field (legacy case; does not occur with current client configuration)
                    if(!is_null($file)){
                        $extension = $file->guessExtension();
                        if(!$extension) {
                            $extension = "bin";
                        }
                        $fileName = 'custom_bg_image.'.$extension;
                        $file->move($saveDir, $fileName);
                        //$file->move($saveDir, $file->getClientOriginalName());
                    }
                    // case 2: file was send as base64 string via hidden "room_image_data" text field
                    else{
                        $data = $room_image_data['room_image_data'];
                        list($fileName, $type, $date) = explode(";", $data);
                        list(, $data) = explode(",", $data);
                        list(, $extension) = explode("/", $type);
                        $data = base64_decode($data);
                        //$fileName = 'custom_bg_image.'.$extension;
                        $fileName = "cid" . $roomId . "_bgimage_" . $fileName;
                        $absoluteFilepath = $saveDir . "/" . $fileName;
                        file_put_contents($absoluteFilepath, $data);
                    }
                    $roomItem->setBGImageFilename($fileName);
                }
            }
            else{
                $roomItem->setBGImageFilename('');
            }

            $roomItem->save();

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
            return $this->redirectToRoute('commsy_settings_general', ["roomId" => $roomId]);
        }

        $backgroundImageCustom = $this->generateUrl("getBackground", array('roomId' => $roomId, 'imageType' => 'custom'));
        $backgroundImageTheme = $this->generateUrl("getBackground", array('roomId' => $roomId, 'imageType' => 'theme'));

        return array(
            'form' => $form->createView(),
            'bgImageFilepathCustom' => $backgroundImageCustom,
            'bgImageFilepathTheme' => $backgroundImageTheme,
        );
    }

    /**
     * @Route("/room/{roomId}/settings/moderation")
     * @Template
     * @Security("is_granted('MODERATOR')")
     */
    public function moderationAction($roomId, Request $request)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.moderation_settings');
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
     * @Route("/room/{roomId}/settings/additional")
     * @Template
     * @Security("is_granted('MODERATOR')")
     */
    public function additionalAction($roomId, Request $request)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);
        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.additional_settings');
        $roomData = $transformer->transform($roomItem);

        $form = $this->createForm(AdditionalSettingsType::class, $roomData, array(
            'roomId' => $roomId,
            // TODO: add new task status choices for this particular room as array parameter!
            'newStatus' => $roomData['tasks']['additional_status'],
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
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.appearance_settings');
        $roomData = $transformer->transform($roomItem);

        // get the configured LiipThemeBundle themes
        $themeArray = $this->container->getParameter('liip_theme.themes');

        $form = $this->createForm(AppearanceSettingsType::class, $roomData, array(
            'roomId' => $roomId,
            'themes' => $themeArray,
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());

            $roomItem->save();

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
            return $this->redirectToRoute('commsy_settings_appearance', ["roomId" => $roomId]);
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
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('No room found for id ' . $roomId);
        }

        $transformer = $this->get('commsy_legacy.transformer.extension_settings');
        $roomData = $transformer->transform($roomItem);

        // get the configured LiipThemeBundle themes
        $mediaWikiUrl = $this->container->getParameter('commsy.mediawiki.url');

        $form = $this->createForm(ExtensionSettingsType::class, $roomData, array(
            'roomId' => $roomId,
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {

            $roomItem = $transformer->applyTransformation($roomItem, $form->getData());
            //$roomItem->save();

            // persist
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($room);
            // $em->flush();
            //return $this->redirectToRoute('commsy_settings_extensions', ["roomId" => $roomId]);            
        }

        return array(
            'form' => $form->createView()
        );
    }
}
