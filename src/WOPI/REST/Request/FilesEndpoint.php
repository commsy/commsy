<?php

namespace App\WOPI\REST\Request;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Controller\Api\WOPI\GetCheckFileInfo;
use App\Controller\Api\WOPI\PostLock;
use App\WOPI\REST\CheckFileInfoResponse;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: 'wopi/files/{fileId}',
            controller: GetCheckFileInfo::class,
            output: CheckFileInfoResponse::class,
            read: false
        ),
        new Post(
            uriTemplate: 'wopi/files/{fileId}',
            controller: PostLock::class,
            read: false,
            deserialize: false,
        ),
        new Post(),
        new GetCollection(),
    ]
)]
final class FilesEndpoint
{
    #[ApiProperty(identifier: true)]
    private string $fileId;
}
