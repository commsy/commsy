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

namespace App\Services;

use App\Utils\FileService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class FileUploader
{
    public function __construct(
        private FileService $fileService
    ) {
    }

    public function upload(UploadedFile $file, int $portalId, int $roomId): int
    {
        $fileItem = $this->fileService->getNewFile();
        $fileItem->setPortalId($portalId);
        $fileItem->setContextID($roomId);

        $fileItem->setTempKey($file->getPathname());

        $fileData = [];
        $fileData['tmp_name'] = $file->getPathname();
        $fileData['name'] = $file->getClientOriginalName();
        $fileItem->setPostFile($fileData);

        // Saving the file in the legacy code will also move it from temp to the final location
        $fileItem->save();

        return $fileItem->getFileId();
    }
}
