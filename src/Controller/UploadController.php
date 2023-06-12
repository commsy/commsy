<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Event\CommsyEditEvent;
use App\Form\Model\File;
use App\Form\Type\UploadType;
use App\Services\LegacyEnvironment;
use App\Utils\DiscService;
use App\Utils\FileService;
use App\Utils\ItemService;
use App\Utils\UserService;
use cs_link_item;
use cs_link_item_file;
use cs_list;
use DateTime;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class UploadController.
 */
#[IsGranted('ITEM_ENTER', subject: 'roomId')]
class UploadController extends AbstractController
{
    /**
     * @return JsonResponse
     */
    #[Route(path: '/room/{roomId}/upload/{itemId}')]
    public function uploadAction(
        Request $request,
        DiscService $discService,
        FileService $fileService,
        ItemService $itemService,
        UserService $userService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId = null
    ): Response {
        $response = new JsonResponse();
        $item = $itemService->getItem($itemId);
        $files = $request->files->all();
        $fileIds = [];
        if ($item) {
            foreach ($files['files'] as $file) {
                /*
                    check type of item:
                    user    ->  user image
                    room    ->  room icon
                    portal  ->  portal icon
                    <other> ->  attachment to item

                    $file is an instance of Symfony\Component\HttpFoundation\File\UploadedFile
                */
                switch ($item->getItemType()) {
                    case 'user':
                        $srcfile = $file->getPathname();
                        $targetfile = $srcfile.'_converted';

                        // resize image to a maximum width of 150px and keep ratio
                        $size = getimagesize($srcfile);
                        $x_orig = $size[0];
                        $y_orig = $size[1];
                        $verhaeltnis = $y_orig / $x_orig;
                        $max_width = 150;
                        $ratio = 1;
                        if ($verhaeltnis < $ratio) {
                            // Breiter als 1:$ratio
                            $source_width = ($size[1] * $max_width) / ($max_width * $ratio);
                            $source_height = $size[1];
                            $source_x = ($size[0] - $source_width) / 2;
                            $source_y = 0;
                        } else {
                            // Höher als 1:$ratio
                            $source_width = $size[0];
                            $source_height = ($size[0] * ($max_width * $ratio)) / $max_width;
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
                        $newimg = imagecreatetruecolor($max_width, $max_width * $ratio);
                        imagecopyresampled($newimg, $im, 0, 0, $source_x, $source_y, $max_width, ceil($max_width * $ratio), $source_width, $source_height);
                        imagepng($newimg, $targetfile);
                        imagedestroy($im);
                        imagedestroy($newimg);

                        // determ new file name
                        $environment = $legacyEnvironment->getEnvironment();
                        $userItem = $userService->getUser($itemId);
                        $filename = 'cid'.$environment->getCurrentContextID().'_'.$userItem->getUserID().'.png';

                        // copy file and set picture
                        $discService->copyFile($targetfile, $filename, true);
                        $userItem->setPicture($filename);
                        $userItem->save();

                        $response->setData([
                            'userImage' => $this->generateUrl('app_user_image', [
                                'roomId' => $roomId,
                                'itemId' => $itemId,
                            ]),
                        ]);
                        break;

                    default:
                        $fileItem = $fileService->getNewFile();

                        $fileItem->setPortalId($legacyEnvironment->getEnvironment()->getCurrentPortalID());
                        $fileItem->setTempKey($file->getPathname());

                        $fileData = [];
                        $fileData['tmp_name'] = $file->getPathname();
                        $fileData['name'] = $file->getClientOriginalName();
                        $fileItem->setPostFile($fileData);

                        $fileItem->save();
                        $fileIds[] = $fileItem->getFileId();
                        break;
                }
            }
        }

        $responseData = [];
        foreach ($fileIds as $fileId) {
            $tempFile = $fileService->getFile($fileId);
            $responseData[$fileId] = htmlentities($tempFile->getFilename()).' ('.$tempFile->getCreationDate().')';
        }

        return $response->setData([
            'fileIds' => $responseData,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/room/{roomId}/upload/{itemId}/form/{versionId}', requirements: ['itemId' => '\d+', 'versionId' => '\d+'], defaults: ['versionId' => -1])]
    public function uploadFormAction(
        Request $request,
        ItemService $itemService,
        FileService $fileService,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $itemId,
        int $versionId = null
    ): Response {
        /**
         * Setting the default value of versionId to 0 does not seem to work and will always cut off the versionId from
         * routes. Instead we default to -1.
         */

        // get item
        $item = $itemService->getTypedItem($itemId, -1 === $versionId ? null : $versionId);

        if (!$item) {
            throw $this->createNotFoundException('No item found for id '.$itemId);
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
            $formFile->setCreationDate(new DateTime($currentFile->getCreationDate()));
            $formFile->setChecked(true);

            $assignedFiles['files'][] = $formFile;
        }

        $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::EDIT);

        $form = $this->createForm(UploadType::class, $assignedFiles, [
            'uploadUrl' => $this->generateUrl('app_upload_upload', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $formData = $form->getData();
                $files = $formData['files'];

                $checkedFormFiles = array_filter($files, fn (File $file) => $file->getChecked());
                $checkedFileIds = array_map(fn (File $file) => $file->getFileId(), $checkedFormFiles);

                $uncheckedFormFiles = array_filter($files, fn (File $file) => !$file->getChecked());
                $uncheckedFileIds = array_map(fn (File $file) => $file->getFileId(), $uncheckedFormFiles);

                // update item
                $legacyEnvironment = $legacyEnvironment->getEnvironment();
                $item->setFileIDArray($checkedFileIds);
                $item->setModificatorItem($legacyEnvironment->getCurrentUserItem());
                $item->save();

                if ((CS_SECTION_TYPE == $item->getItemType()) || (CS_STEP_TYPE == $item->getItemType())) {
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

                    /** @var cs_list $linkItemList */
                    $linkItemList = $linkItemManager->get();
                    $linkedItemsNotDeleted = array_filter(
                        $linkItemList->to_array(),
                        fn(cs_link_item_file $linkItem) => !$linkItem->isDeleted()
                    );

                    if (!empty($linkedItemsNotDeleted)) {
                        $tempFile->delete();
                    }
                }
            }

            return $this->redirectToRoute('app_upload_uploadsave', [
                'roomId' => $roomId,
                'itemId' => $itemId,
            ]);
        }

        return $this->render('upload/upload_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/room/{roomId}/upload/{itemId}/saveupload')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function uploadSaveAction(
        ItemService $itemService,
        EventDispatcherInterface $eventDispatcher,
        LegacyEnvironment $environment,
        int $roomId,
        int $itemId
    ): Response {
        $item = $itemService->getTypedItem($itemId);
        $legacyEnvironment = $environment->getEnvironment();
        $tempManager = $legacyEnvironment->getManager($item->getItemType());
        $tempItem = $tempManager->getItem($item->getItemId());

        $eventDispatcher->dispatch(new CommsyEditEvent($item), CommsyEditEvent::SAVE);

        return $this->render('upload/upload_save.html.twig', ['roomId' => $roomId, 'item' => $tempItem]);
    }

    /**
     * @return JsonResponse
     */
    #[Route(path: '/room/{roomId}/base64upload/')]
    public function base64UploadAction(
        Request $request
    ): Response {
        $files = $request->files->all();
        $base64Content = [];
        $fileSystem = new Filesystem();

        /** @var UploadedFile $file */
        foreach ($files['files'] as $file) {
            $tempUploadDir = $this->getParameter('kernel.project_dir').'/files/temp/';
            $fileName = md5(uniqid()).'.'.$file->guessExtension();

            $file->move($tempUploadDir, $fileName);
            $fileAsBase64 = base64_encode(file_get_contents($tempUploadDir.$fileName));

            $base64Content[] = [
                'filename' => $file->getClientOriginalName(),
                'content' => $fileAsBase64,
            ];

            $fileSystem->remove($tempUploadDir.$fileName);
        }

        $response = new JsonResponse();
        $response->setData([
            'base64' => $base64Content,
        ]);

        return $response;
    }

    #[Route(path: '/room/{roomId}/ckupload/{itemId}/')]
    #[IsGranted('ITEM_EDIT', subject: 'itemId')]
    public function ckuploadAction(
        Request $request,
        ItemService $itemService,
        LegacyEnvironment $environment,
        int $itemId
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();
        $item = $itemService->getTypedItem($itemId);
        $fileIds = $item->getFileIDArray();

        if ($request->files) {
            /** @var UploadedFile $file */
            $file = $request->files->get('upload');

            if ($file && $file->isValid() && $file->getSize()) {
                $movedFile = $file->move($file->getPathInfo()->getRealPath(), $file->getFilename() . 'commsy3');

                $fileInfo = [
                    'name' => $file->getClientOriginalName(),
                    'tmp_name' => $movedFile->getRealPath(),
                    'file_id' => $file->getClientOriginalName() . '_' . getCurrentDateTimeInMySQL(),
                ];

                $fileManager = $legacyEnvironment->getFileManager();
                $fileItem = $fileManager->getNewItem();
                $fileItem->setPortalId($legacyEnvironment->getCurrentPortalID());
                $fileItem->setTempKey($fileInfo['file_id']);
                $fileItem->setPostFile($fileInfo);
                $fileItem->save();

                // save file ids to item
                $fileIds = array_merge($fileIds, [$fileItem->getFileID()]);
                $item->setFileIDArray($fileIds);
                $item->save();

                // generate file url
                $fileUrl = $this->generateUrl('app_file_getfile', [
                    'fileId' => $fileItem->getFileID(),
                ]);

                if (null != $request->get('CKEditor')) {
                    // This is for uploading through dialog / File Browser Plugin???
                    // @see https://ckeditor.com/docs/ckeditor4/latest/guide/dev_dialog_add_file_browser.html
                    // @see https://ckeditor.com/docs/ckeditor4/latest/guide/dev_file_browser_api.html#example-4
                    // This can also be used to show an error message, the third parameter was previously used
                    // to inject a JS function call
                    $callback_function = '<script type="text/javascript">';
                    $callback_function .= 'window.parent.CKEDITOR.tools.callFunction('.$_GET['CKEditorFuncNum'].', "'.$fileUrl.'");';
                    $callback_function .= '</script>';

                    return new Response($callback_function);
                } else {
                    // This is for uploading pasted and draggted images introduced in CKE 4.5???
                    // @see https://ckeditor.com/docs/ckeditor4/latest/guide/dev_file_upload.html#server-side-configuration
                    return $this->json([
                        'uploaded' => 1,
                        'filename' => $file->getClientOriginalName(),
                        'url' => $fileUrl,
                    ]);
                }
            }
        }

        return $this->json([
            'uploaded' => 0,
            'error' => [
                'message' => 'There was an error while uploading files.',
            ],
        ]);
    }

    #[Route(path: '/room/{roomId}/upload/mailattachments/')]
    public function mailAttachments($roomId, Request $request): Response
    {
        $files = $request->files->all();

        $response = new JsonResponse();
        $responseData = [];

        /** @var UploadedFile $file */
        foreach ($files['files'] as $file) {
            $tempUploadDir = $this->getParameter('kernel.project_dir').'/files/temp/';
            $fileId = md5(uniqid());
            $fileName = $fileId.'.'.$file->guessExtension();
            $filePath = $tempUploadDir.$fileName;
            $fileDisplayName = $file->getClientOriginalName();

            $file->move($tempUploadDir, $fileName);

            $fileInfo = [
                'fileId' => $fileId,
                'filePath' => $filePath,
                'filename' => htmlentities($fileDisplayName),
            ];
            $responseData[$fileId] = $fileInfo;
        }

        $response->setData([
            'attachmentInfo' => $responseData,
        ]);

        return $response;
    }
}
