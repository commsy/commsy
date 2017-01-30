<?php
namespace CommsyBundle\Controller;

use CommsyBundle\Form\Model\File;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use CommsyBundle\Form\Type\UploadType;

class UploadController extends Controller
{
    /**
     * @Route("/room/{roomId}/upload/{itemId}")
     */
    public function uploadAction($roomId, $itemId = NULL, Request $request)
    {
        $response = new JsonResponse();

        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        $fileService = $this->get('commsy_legacy.file_service');

        $files = $request->files->all();

        $fileIds = array();

        if ($item) {
            foreach ($files['files'] as $file) {
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

                switch ($item->getItemType()) {
                    case 'user':
                        $srcfile = $file->getPathname();
                        $targetfile = $srcfile . "_converted";

                        // resize image to a maximum width of 150px and keep ratio
                        $size = getimagesize($srcfile);
                        $x_orig= $size[0];
                        $y_orig= $size[1];
                        $verhaeltnis = $y_orig/$x_orig;
                        $max_width = 150;
                        $ratio = 1;
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
                        $userService = $this->get("commsy_legacy.user_service");
                        $userItem = $userService->getUser($itemId);
                        $filename = 'cid' . $environment->getCurrentContextID() . '_' . $userItem->getUserID() . '.png';

                        // copy file and set picture
                        $discService = $this->get('commsy_legacy.disc_service');
                        $discService->copyFile($targetfile, $filename, true);
                        $userItem->setPicture($filename);
                        $userItem->save();

                        $response->setData([
                            'userImage' => $this->generateUrl('commsy_user_image', [
                                'roomId' => $roomId,
                                'itemId' => $itemId,
                            ])
                        ]);
                        break;

                    default:
                        $fileItem = $fileService->getNewFile();

                        $fileItem->setTempKey($file->getPathname());

                        $fileData = array();
                        $fileData['tmp_name'] = $file->getPathname();
                        $fileData['name'] = $file->getClientOriginalName();
                        $fileItem->setPostFile($fileData);

                        $fileItem->save();
                        $fileIds[] = $fileItem->getFileId();
                        break;
                }
            }
        }

        $responseData = array();
        foreach ($fileIds as $fileId) {
            $tempFile = $fileService->getFile($fileId);
            $responseData[$fileId] = $tempFile->getFilename().' ('.$tempFile->getCreationDate().')';
        }
        
        return $response->setData([
            'fileIds' => $responseData,
        ]);
    }
    
    /**
     * @Route("/room/{roomId}/upload/{itemId}/form")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function uploadFormAction($roomId, $itemId, Request $request)
    {
        // get material from MaterialService
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getTypedItem($itemId);

        if (!$item) {
            throw $this->createNotFoundException('No item found for id ' . $itemId);
        }

        // collect currently assigned files
        $assignedFiles = [];

        $fileService = $this->get('commsy_legacy.file_service');
        $currentFileIds = $item->getFileIDArray();
        foreach ($currentFileIds as $currentFileId) {
            $currentFile = $fileService->getFile($currentFileId);

            // convert legacy file object into a form usable file object
            $formFile = new File();
            $formFile->setFileId($currentFile->getFileID());
            $formFile->setFilename($currentFile->getFileName());
            $formFile->setCreationDate(new \DateTime($currentFile->getCreationDate()));
            $formFile->setChecked(true);

            $assignedFiles['files'][] = $formFile;
        }

        $form = $this->createForm(UploadType::class, $assignedFiles, [
            'uploadUrl' => $this->generateUrl('commsy_upload_upload', [
                'roomId' => $roomId,
                'itemId' => $itemId
            ]),
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $formData = $form->getData();
                $files = $formData['files'];

                $checkedFileIds = [];
                $uncheckedFileIds = [];
                foreach ($files as $file) {
                    if ($file->getChecked()) {
                        $checkedFileIds[] = $file->getFileId();
                    } else {
                        $uncheckedFileIds[] = $file->getFileId();
                    }
                }

                // update item
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
                $item->setFileIDArray($checkedFileIds);
                $item->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $item->save();

                // delete unchecked files
                foreach ($uncheckedFileIds as $uncheckedFileId) {
                    $tempFile = $fileService->getFile($uncheckedFileId);
                    $tempFile->delete();
                }
            }
            
            return $this->redirectToRoute('commsy_upload_uploadsave', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return [
            'form' => $form->createView(),
        ];
    }
    
    /**
     * @Route("/room/{roomId}/upload/{itemId}/saveupload")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function uploadSaveAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $tempManager = $legacyEnvironment->getManager($item->getItemType());
        $tempItem = $tempManager->getItem($item->getItemId());
        
        return array(
            'roomId' => $roomId,
            'item' => $tempItem
        );
    }

    /**
     * @Route("/room/{roomId}/ckupload/{itemId}/")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function ckuploadAction($roomId, $itemId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $itemService = $this->get('commsy_legacy.item_service');

        $item = $itemService->getTypedItem($itemId);
        $fileIds = $item->getFileIDArray();

        // ckeditor upload action
        if(!empty($_FILES)) {
            $post_file_ids = array();
            $tempFile = $_FILES['upload']['tmp_name'];

            $focus_element_onload = 'Filedata';

            $file_array = array();

            if( !empty($tempFile) && $_FILES['upload']['size'] > 0) {
                require_once('functions/date_functions.php');
                move_uploaded_file($tempFile, $tempFile . 'commsy3');
                $temp_array = array();
                $temp_array['name'] = $_FILES['upload']['name'];
                $temp_array['tmp_name'] = $tempFile . 'commsy3';
                $temp_array['file_id'] = $temp_array['name'] . '_' . getCurrentDateTimeInMySQL();
                $file_array[] = $temp_array;
            }
         
            $file_data = $file_array[0];
            $file_manager = $legacyEnvironment->getFileManager();
            $file_item = $file_manager->getNewItem();
            $file_item->setTempKey($file_data["file_id"]);
            $file_item->setPostFile($file_data);
            $file_item->setTempUploadFromEditorSessionID($legacyEnvironment->getSessionID());
            $file_item->save();
            unlink($file_data["tmp_name"]);

            // save file ids to item
            $fileIds = array_merge($fileIds, array($file_item->getFileID()));
            $item->setFileIDArray($fileIds);
            $item->save();

            // generate file url
            $fileUrl = $this->generateUrl('commsy_file_getfile', array(
                'fileId' => $file_item->getFileID()
            ));
         
            // Nach dem Speichern des Eintrags die Items-Tabelle anhand temp=true und der extras->SESSION_ID durchsuchen.
            // Text im Textfeld nach Dateinamen parsen und passende Dateien aus der files-Tabelle mit dem Item verlinken.
            // Extras temp und id zurücksetzen.
            // cron für das regelmäßige löschen von temp-files.
            $callback_function  = '';
            $callback_function .= '<script type="text/javascript">'.LF;
            $callback_function .= '<!--'.LF;
            $callback_function .= 'var fileTypeFunction = function () {';
            $callback_function .= 'var dialog = this.getDialog();';
            $callback_function .= 'if(dialog.getName() == "CommSyVideo"){';
            $callback_function .= 'var element = dialog.getContentElement( "videoTab", "videoType" );';
            $callback_function .= 'element.setValue("'.$file_item->getMime().'")';
            $callback_function .= '}';
            $callback_function .= '};';
            $callback_function .= 'window.parent.CKEDITOR.tools.callFunction('.$_GET['CKEditorFuncNum'].', "'.$fileUrl.'", fileTypeFunction);'.LF;
            $callback_function .= '-->'.LF;
            $callback_function .= '</script>'.LF;
            echo $callback_function;
        }
        exit;
    }
}