<?php
namespace App\Controller;

use App\Form\Model\File;
use App\Services\LegacyEnvironment;
use App\Utils\FileService;
use App\Utils\ItemService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\EventDispatcher\EventDispatcher;
use App\Event\CommsyEditEvent;

use App\Form\Type\UploadType;

/**
 * Class UploadController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
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
                            'userImage' => $this->generateUrl('app_user_image', [
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
            $responseData[$fileId] = htmlentities($tempFile->getFilename()) . ' ('.$tempFile->getCreationDate().')';
        }
        
        return $response->setData([
            'fileIds' => $responseData,
        ]);
    }
    
    /**
     * @Route("/room/{roomId}/upload/{itemId}/form/{versionId}", requirements={
     *     "itemId": "\d+",
     *     "versionId": "\d+"
     * }, defaults={
     *     "versionId" = -1
     * }))
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function uploadFormAction(
        $roomId,
        $itemId,
        $versionId = null,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        ItemService $itemService,
        FileService $fileService)
    {
        /**
         * Setting the default value of versionId to 0 does not seem to work and will always cut off the versionId from
         * routes. Instead we default to -1.
         */

        // get item
        $item = $itemService->getTypedItem($itemId, $versionId === -1 ? null : $versionId);

        if (!$item) {
            throw $this->createNotFoundException('No item found for id ' . $itemId);
        }

        // collect currently assigned files
        $assignedFiles = [];

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

        if (in_array($item->getItemType(), [CS_SECTION_TYPE, CS_STEP_TYPE, CS_DISCARTICLE_TYPE])) {
            $this->get('event_dispatcher')->dispatch(CommsyEditEvent::EDIT, new CommsyEditEvent($item->getLinkedItem()));
        } else {
            $this->get('event_dispatcher')->dispatch(CommsyEditEvent::EDIT, new CommsyEditEvent($item));
        }

        $form = $this->createForm(UploadType::class, $assignedFiles, [
            'uploadUrl' => $this->generateUrl('app_upload_upload', [
                'roomId' => $roomId,
                'itemId' => $itemId
            ]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
                $legacyEnvironment = $legacyEnvironment->getEnvironment();
                $item->setFileIDArray($checkedFileIds);
                $item->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $item->save();

                if (($item->getItemType() == CS_SECTION_TYPE) || ($item->getItemType() == CS_STEP_TYPE)) {
                    $linkedItem = $itemService->getTypedItem($item->getlinkedItemID());
                    $linkedItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                    $linkedItem->save();
                }

                // delete unchecked files
                $linkItemManager = $legacyEnvironment->getLinkItemFileManager();
                foreach ($uncheckedFileIds as $uncheckedFileId) {
                    $tempFile = $fileService->getFile($uncheckedFileId);

                    // Check if the unchecked file is linked to any other item and only delete it, if this is
                    // not the case.
                    $linkItemManager->resetLimits();
                    $linkItemManager->setFileIDLimit($tempFile->getFileID());
                    $linkItemManager->select();

                    /** @var \cs_list $linkItemList */
                    $linkItemList = $linkItemManager->get();

                    $delete = true;
                    if ($linkItemList->isNotEmpty()) {
                        /** @var \cs_link_item $linkItem */
                        $linkItem = $linkItemList->getFirst();
                        while ($linkItem) {
                            if (!$linkItem->isDeleted()) {
                                $delete = false;
                                break;
                            }

                            $linkItem = $linkItemList->getNext();
                        }
                    }

                    if ($delete) {
                        $tempFile->delete();
                    }
                }
            }
            
            return $this->redirectToRoute('app_upload_uploadsave', array('roomId' => $roomId, 'itemId' => $itemId));
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
        $item = $itemService->getTypedItem($itemId);
        
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $tempManager = $legacyEnvironment->getManager($item->getItemType());
        $tempItem = $tempManager->getItem($item->getItemId());
        
        if (in_array($item->getItemType(), [CS_SECTION_TYPE, CS_STEP_TYPE, CS_DISCARTICLE_TYPE])) {
            $this->get('event_dispatcher')->dispatch(CommsyEditEvent::SAVE, new CommsyEditEvent($item->getLinkedItem()));
        } else {
            $this->get('event_dispatcher')->dispatch(CommsyEditEvent::SAVE, new CommsyEditEvent($item));
        }

        return array(
            'roomId' => $roomId,
            'item' => $tempItem
        );
    }

    /**
     * @Route("/room/{roomId}/base64upload/")
     */
    public function base64UploadAction($roomId, Request $request)
    {
        $files = $request->files->all();

        $base64Content = [];

        $fileSystem = new Filesystem();

        /** @var UploadedFile $file */
        foreach ($files['files'] as $file) {
            $tempUploadDir = $this->getParameter('kernel.project_dir') . '/files/temp/';
            $fileName = md5(uniqid()) . '.' . $file->guessExtension();

            $file->move($tempUploadDir, $fileName);
            $fileAsBase64 = base64_encode(file_get_contents($tempUploadDir . $fileName));

            $base64Content[] = [
                'filename' => $file->getClientOriginalName(),
                'content' => $fileAsBase64,
            ];

            $fileSystem->remove($tempUploadDir . $fileName);
        }

        $response = new JsonResponse();
        $response->setData([
            'base64' => $base64Content,
        ]);

        return $response;
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

        if ($request->files) {

            $file = $request->files->get('upload');

            if ($file && $file->isValid() && $file->getClientSize()) {
                $movedFile = $file->move($file->getPathInfo()->getRealPath(), $file->getFilename() . 'commsy3');

                require_once('functions/date_functions.php');

                $fileInfo = [
                    'name' => $file->getClientOriginalName(),
                    'tmp_name' => $movedFile->getRealPath(),
                    'file_id' => $file->getClientOriginalName() . '_' . getCurrentDateTimeInMySQL(),
                ];

                $fileManager = $legacyEnvironment->getFileManager();
                $fileItem = $fileManager->getNewItem();
                $fileItem->setTempKey($fileInfo['file_id']);
                $fileItem->setPostFile($fileInfo);
                $fileItem->setTempUploadFromEditorSessionID($legacyEnvironment->getSessionID());
                $fileItem->save();

                // save file ids to item
                $fileIds = array_merge($fileIds, [$fileItem->getFileID()]);
                $item->setFileIDArray($fileIds);
                $item->save();

                // generate file url
                $fileUrl = $this->generateUrl('app_file_getfile', [
                    'fileId' => $fileItem->getFileID()
                ]);

                // Nach dem Speichern des Eintrags die Items-Tabelle anhand temp=true und der extras->SESSION_ID durchsuchen.
                // Text im Textfeld nach Dateinamen parsen und passende Dateien aus der files-Tabelle mit dem Item verlinken.
                // Extras temp und id zurücksetzen.
                // cron für das regelmäßige löschen von temp-files.
                $callback_function  = '';
                $callback_function .= '<script type="text/javascript">'.LF;
                $callback_function .= '<!--'.LF;
                $callback_function .= 'var fileTypeFunction = function () {';
                $callback_function .= 'var dialog = this.getDialog();';
                $callback_function .= 'if(dialog.getName() == "CommSyVideoDialog"){';
                $callback_function .= 'var element = dialog.getContentElement( "videoTab", "videoType" );';
                $callback_function .= 'element.setValue("'.$fileItem->getMime().'")';
                $callback_function .= '}';
                $callback_function .= '};';
                $callback_function .= 'window.parent.CKEDITOR.tools.callFunction('.$_GET['CKEditorFuncNum'].', "'.$fileUrl.'", fileTypeFunction);'.LF;
                $callback_function .= '-->'.LF;
                $callback_function .= '</script>'.LF;
                echo $callback_function;
            }
        }

        exit;
    }
}
