<?php

namespace App\WOPI\REST\Request;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Controller\Api\WOPI\GetGetFile;
use App\Controller\Api\WOPI\PostPutFile;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: 'wopi/files/{fileId}/contents',
            controller: GetGetFile::class,
            read: false,
        ),
        new Post(
            uriTemplate: 'wopi/files/{fileId}/contents',
            status: 200,
            controller: PostPutFile::class,
            output: false,
            read: false,
            deserialize: false,
            validate: false
        ),
        new Post(),
        new GetCollection(),
    ]
)]
final readonly class FileContentEndpoint
{
    #[ApiProperty(identifier: true)]
    private string $fileId;
}
