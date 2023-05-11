<?php

namespace App\WOPI\REST\Request;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\Api\WOPI\GetGetFile;
use App\Controller\Api\WOPI\PostPutFile;

/**
 * @ApiResource(
 *     itemOperations={
 *         "get_getfile"={
 *             "method"="GET",
 *             "path"="wopi/files/{fileId}/contents",
 *             "controller"=GetGetFile::class,
 *             "read"=false
 *         },
 *         "post_putfile"={
 *             "method"="POST",
 *             "path"="wopi/files/{fileId}/contents",
 *             "controller"=PostPutFile::class,
 *             "read"=false,
 *             "deserialize"=false
 *         }
 *     }
 * )
 */
final class FileContentEndpoint
{
    /**
     * @ApiProperty(identifier=true)
     */
    private string $fileId;
}
