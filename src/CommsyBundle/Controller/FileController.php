<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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

        if (file_exists($rootDir.$file->getDiskFileName())) {
            $content = file_get_contents($rootDir.$file->getDiskFileName());
        } else {
            throw $this->createNotFoundException('The requested file does not exist');   
        }
        $response = new Response($content, Response::HTTP_OK, array('content-type' => $file->getMime()));
        
        if ($disposition == 'inline') {
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE,$file->getFileName());
        } else {
            $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$file->getFileName());   
        }
        $response->headers->set('Content-Disposition', $contentDisposition);
        
        return $response;
    }

    /**
    * @Route("/room/{roomId}/background/", name="getBackground")
    */
    public function getBackgroundImageAction($roomId)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        $filename = $roomItem->getBGImageFilename();

        if($filename == ''){
            $themesDir = $this->getParameter("themes_directory");
            $completePath = $themesDir . "/" . $roomItem->getColorArray()['schema'] . "/bg.jpg";
        }
        else{
            $filepath = $this->getParameter('files_directory') . "/" .  $roomService->getRoomFileDirectory($roomId);
            $completePath = $filepath . "/" . $filename;
        }

        // if(file_exists($completePath)){
        //     error_log($completePath);
        // }

        $content = file_get_contents($completePath);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $completePath);

        $response = new Response($content, Response::HTTP_OK, array('content-type' => $mimeType));
        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$filename);
        $response->headers->set('Content-Disposition', $contentDisposition);

        return $response;
    }
}
