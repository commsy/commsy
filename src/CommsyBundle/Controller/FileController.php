<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FileController extends Controller
{
    /**
     * @Route("/file/{fileId}/{disposition}")
     */
    public function getFileAction($fileId, $disposition = 'attachment')
    {
        $fileService = $this->get('commsy_legacy.file_service');
        $file = $fileService->getFile($fileId);
        $rootDir = $this->get('kernel')->getRootDir().'/';

        // fix for archived rooms
        if (!$file->getPortalID()) {
            $roomService = $this->get('commsy_legacy.room_service');
            $roomItem = $roomService->getArchivedRoomItem($file->getContextID());

            if ($roomItem) {
                $file->setPortalID($roomItem->getContextId());
            }
        }
        // ~fix for archived rooms

        if (file_exists($rootDir.$file->getDiskFileName())) {
            $content = file_get_contents($rootDir.$file->getDiskFileName());
        } else {
            throw $this->createNotFoundException('The requested file does not exist');   
        }
        $response = new Response($content, Response::HTTP_OK, array('content-type' => $file->getMime()));
        
        if ($disposition == 'inline') {
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE,$file->getFileName(), mb_convert_encoding ($file->getFileName(), 'US-ASCII', 'UTF-8'));
        } else {
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$file->getFileName(), mb_convert_encoding ($file->getFileName(), 'US-ASCII', 'UTF-8'));
        }
        $response->headers->set('Content-Disposition', $contentDisposition);
        
        return $response;
    }


    /**
     * @Route("/room/{roomId}/logo", name="getLogo")
     */
    public function getLogoAction($roomId)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        $fileName = $roomItem->getLogoFilename();
        $filePath = $this->getParameter('files_directory') . "/" . $roomService->getRoomFileDirectory($roomId) . "/" . $fileName;

        if(file_exists($filePath)) {
            if(!$fileName){
                $fileName = "customBgPlaceholder.png";
                $filePath = $this->getParameter("themes_directory") . "/" . $fileName;
            }
            $content = file_get_contents($filePath);

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);

            $response = new Response($content, Response::HTTP_OK, array('content-type' => $mimeType));
            $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE,$fileName));
        }
        else {
            $response = new Response("Logo image not found!", Response::HTTP_NOT_FOUND);
        }

        return $response;
    }

    /**
    * @Route(
            "/room/{roomId}/{imageType}/background/", 
            name="getBackground", 
            defaults={"imageType": "theme"}, 
            requirements={
                "imageType": "custom|theme"
            }
        )
    */
    public function getBackgroundImageAction($roomId, $imageType)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        $filename = $roomItem->getBGImageFilename();

        $themesDir = $this->getParameter("themes_directory");

        if($imageType == 'theme'){
            $completePath = $themesDir . "/" . $roomItem->getColorArray()['schema'] . "/bg.jpg";
            if(!file_exists($completePath)){
                $completePath = $themesDir . "/" . mb_strtolower($roomItem->getColorArray()['schema']) . "/bg.jpg";
            }
        }
        elseif($imageType =='custom'){
            if($filename != ''){
                $filepath = $this->getParameter('files_directory') . "/" .  $roomService->getRoomFileDirectory($roomId);
                $completePath = $filepath . "/" . $filename;
            }
            else{
                $completePath = $themesDir . "/customBgPlaceholder.png";
            }
        }

        if(file_exists($completePath)){
            $content = file_get_contents($completePath);

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $completePath);

            $response = new Response($content, Response::HTTP_OK, array('content-type' => $mimeType));
            $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE,$filename));
        }
        else{
            $response = new Response("Background image not found!", Response::HTTP_NOT_FOUND);
        }

        return $response;
    }

    /**
     * @Route("/theme/{theme}/background", name="getThemeBackground")
     */
    public function getThemeBackgroundAction($theme)
    {
        $themesDir = $this->getParameter("themes_directory");
        $filePath = $themesDir . "/" . $theme . "/bg.jpg";

        if(file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);

            $response = new Response($content, Response::HTTP_OK, array('content-type' => $mimeType));
            $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE,"bg.jpg"));
        }
        else {
            $response = new Response("Could not find background image for selected theme!", Response::HTTP_NOT_FOUND);
        }
        return $response;
    }
}
