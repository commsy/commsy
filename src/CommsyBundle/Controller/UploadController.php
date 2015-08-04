<?php
namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class UploadController extends Controller
{
    /**
     * @Route("/room/{roomId}/upload")
     */
    public function uploadAction($roomId, Request $request)
    {
        $files = $request->files->all();

        foreach ($files as $file) {
            // file is an instance of Symfony\Component\HttpFoundation\File\UploadedFile
            var_dump($file);
        }
    }
}
