<?php
namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class UploadController extends Controller
{
    /**
     * @Route("/room/{roomId}/upload/{itemId}")
     */
    public function uploadAction($roomId, $itemId = NULL, Request $request)
    {
        $response = new JsonResponse();

        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getItem($itemId);
        $fileService = $this->get('commsy.file_service');
        
        $files = $request->files->all();

        $saveFileIds = false;
        $fileIds = array();

        foreach ($files['files'] as $file) {
            if ($itemId) {
                /*
                    check type of item:
                    user    ->  user image
                    room    ->  room icon
                    portal  ->  portal icon
                    <other> ->  attachment to item
                    
                    $file is an instance of Symfony\Component\HttpFoundation\File\UploadedFile
                    Array
                    (
                        [0] => Symfony\Component\HttpFoundation\File\UploadedFile Object
                            (
                                [test:Symfony\Component\HttpFoundation\File\UploadedFile:private] => 
                                [originalName:Symfony\Component\HttpFoundation\File\UploadedFile:private] => box_checked.png
                                [mimeType:Symfony\Component\HttpFoundation\File\UploadedFile:private] => image/png
                                [size:Symfony\Component\HttpFoundation\File\UploadedFile:private] => 2329
                                [error:Symfony\Component\HttpFoundation\File\UploadedFile:private] => 0
                                [pathName:SplFileInfo:private] => /tmp/phpjoYeVn
                                [fileName:SplFileInfo:private] => phpjoYeVn
                            )
                    )
                */
                
                if ($item->getItemType() == 'user') {
                    $srcfile = $file->getPathname();
    				$targetfile = $srcfile . "_converted";
    				// resize image to a maximum width of 150px and keep ratio
    	            $size = getimagesize($srcfile);
    	            $x_orig= $size[0];
    	            $y_orig= $size[1];
    	            //$verhaeltnis = $x_orig/$y_orig;
    	            $verhaeltnis = $y_orig/$x_orig;
    	            $max_width = 150;
    	            //$ratio = 1.618; // Goldener Schnitt
    	            //$ratio = 1.5;   // 2:3
    	            //$ratio = 1.334; // 3:4
    	            $ratio = 1;       // 1:1
    	            if($verhaeltnis < $ratio){
    	               // Breiter als 1:$ratio
    	               $source_width = ($size[1] * $max_width) / ($max_width * $ratio);
    	               $source_height = $size[1];
    	               $source_x = ($size[0] - $source_width) / 2;
    	               $source_y = 0;
    	            } else {
    	               // Höher als 1:$ratio
    	               $source_width = $size[0];
    	               $source_height = ($size[0] * ($max_width * $ratio)) / ($max_width);
    	               $source_x = 0;
    	               $source_y = ($size[1] - $source_height) / 2;
    	            }
    	            switch ($size[2]) {
    	                  case '1':
    	                     $im = imagecreatefromgif($srcfile);
    	                     break;
    	                  case '2':
    	                     $im = imagecreatefromjpeg($srcfile);
    	                     break;
    	                  case '3':
    	                     $im = imagecreatefrompng($srcfile);
    	                     break;
    	            }
                    $newimg = imagecreatetruecolor($max_width,($max_width * $ratio));
                    imagecopyresampled($newimg, $im, 0, 0, $source_x, $source_y, $max_width, ceil($max_width * $ratio), $source_width, $source_height);
                    imagepng($newimg,$targetfile);
                    imagedestroy($im);
                    imagedestroy($newimg);
    
    				// determ new file name
    				$environment = $this->get("commsy_legacy.environment")->getEnvironment();
    				$userService = $this->get("commsy.user_service");
    				$userItem = $userService->getUser($itemId);
    				$filename = 'cid' . $environment->getCurrentContextID() . '_' . $userItem->getUserID() . '.png';
    				
    				// copy file and set picture
                    $discService = $this->get('commsy_legacy.disc_service');
    				$discService->copyFile($targetfile, $filename, true);
    				$userItem->setPicture($filename);
    				$userItem->save();
    				
    				$response->setData(array(
                        'userImage' => $this->generateUrl('commsy_user_image', array(
                            'roomId' => $roomId,
                            'itemId' => $itemId
                        ))
                    ));
    				
                } else if ($item->getItemType() == 'room') {
                    
                } else if ($item->getItemType() == 'portal') {
                    
                } else {
                    $saveFileIds = true;
                    
					$fileItem = $fileService->getNewFile();
					
					$fileItem->setTempKey($file->getPathname());
					
					$fileData = array();
                    $fileData['tmp_name'] = $file->getPathname();
                    $fileData['name'] = $file->getClientOriginalName();
					$fileItem->setPostFile($fileData);
					
					$fileItem->save();
                    $fileIds[] = $fileItem->getFileId();
                }
            }
        }
        
        if ($saveFileIds) {
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
            $tempManager = $legacyEnvironment->getManager($item->getItemType());
            $tempItem = $tempManager->getItem($item->getItemId());
            
            $responseData = array();
            foreach ($fileIds as $fileId) {
                $tempFile = $fileService->getFile($fileId);
                $responseData[$fileId] = $tempFile->getFilename().' ('.$tempFile->getCreationDate().')';
            }
            
            $response->setData(array(
                'fileIds' => $responseData,
            ));
            
            $oldFileIds = $tempItem->getFileIDArray();
            
            $fileIds = array_merge($oldFileIds, $fileIds);
            
            $tempItem->setFileIDArray($fileIds);
            
            $tempItem->save();
        }
        
        return $response;
    }
    
    /**
     * @Route("/room/{roomId}/upload/{itemId}/form")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function uploadFormAction($roomId, $itemId, Request $request)
    {
        // get material from MaterialService
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('No item found for id ' . $itemId);
        }

        $uploadData = array();

        $fileService = $this->get('commsy.file_service');
        $oldFileIds = $item->getFileIDArray();
        $optionsData = array();
        $uploadData['oldFiles'] = array();
        $optionsData['oldFiles'] = array();
        foreach ($oldFileIds as $oldFileId) {
            $tempFile = $fileService->getFile($oldFileId);
            $uploadData['oldFiles'][] = $oldFileId;
            $optionsData['oldFiles'][$oldFileId] = $tempFile->getFilename().' ('.$tempFile->getCreationDate().')';
        }

        $form = $this->createForm('upload', $uploadData, array(
            'uploadUrl' => $this->generateUrl('commsy_upload_upload', array(
                'roomId' => $roomId,
                'itemId' => $itemId
            )),
            'oldFiles' => $optionsData['oldFiles'],
        ));
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $formData = $form->getData();
    
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                $tempManager = $legacyEnvironment->getManager($item->getItemType());
                $tempItem = $tempManager->getItem($item->getItemId());
    
                $oldFileIds = $tempItem->getFileIDArray();
                
                $tempItem->setFileIDArray($formData['oldFiles']);
                
                $tempItem->save();
    
                $deleteFileIds = array_diff($oldFileIds, $formData['oldFiles']);
                
                foreach ($deleteFileIds as $deleteFileId) {
                    $tempFile = $fileService->getFile($deleteFileId);
                    $tempFile->delete();
                }
                
                // persist
                // $em = $this->getDoctrine()->getManager();
                // $em->persist($room);
                // $em->flush();
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            
            return $this->redirectToRoute('commsy_upload_uploadsave', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'form' => $form->createView()
        );
    }
    
    /**
     * @Route("/room/{roomId}/upload/{itemId}/saveupload")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function uploadSaveAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy.item_service');
        $item = $itemService->getItem($itemId);
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $tempManager = $legacyEnvironment->getManager($item->getItemType());
        $tempItem = $tempManager->getItem($item->getItemId());
        
        return array(
            'roomId' => $roomId,
            'item' => $tempItem
        );
    }
}
