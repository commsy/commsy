<?php

namespace App\WOPI\REST\Request;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\Api\WOPI\GetCheckFileInfo;
use App\Controller\Api\WOPI\PostLock;
use App\WOPI\REST\CheckFileInfoResponse;

/**
 * @ApiResource(
 *     itemOperations={
 *         "get_checkfileinfo"={
 *             "method"="GET",
 *             "path"="wopi/files/{fileId}",
 *             "controller"=GetCheckFileInfo::class,
 *             "read"=false,
 *             "output"=CheckFileInfoResponse::class
 *         },
 *         "post_lock"={
 *             "method"="POST",
 *             "path"="wopi/files/{fileId}",
 *             "controller"=PostLock::class,
 *             "read"=false,
 *             "deserialize"=false
 *         }
 *     }
 * )
 */
final class FilesEndpoint
{
    /**
     * @ApiProperty(identifier=true)
     */
    private string $fileId;
}
