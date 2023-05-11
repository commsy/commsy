<?php

namespace App\Controller\Api\WOPI;

use App\Repository\FilesRepository;
use App\Security\Voter\WOPIVoter;
use App\Utils\FileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class GetGetFile extends AbstractController
{
    public function __construct(
        private readonly FilesRepository $filesRepository,
        private readonly FileService $fileService
    ) {
    }

    public function __invoke(string $fileId): BinaryFileResponse
    {
        $file = $this->filesRepository->find($fileId);
        if (!$file) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(WOPIVoter::VIEW, $file);

        return new BinaryFileResponse($this->fileService->makeAbsolute($file));
    }
}
