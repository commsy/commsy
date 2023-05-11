<?php

namespace App\Controller\Api\WOPI;

use App\Repository\FilesRepository;
use App\Security\Voter\WOPIVoter;
use App\WOPI\REST\CheckFileInfoResponse;
use App\WOPI\REST\WOPICheckFileInfo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class GetCheckFileInfo extends AbstractController
{
    public function __construct(
        private readonly FilesRepository $filesRepository,
        private readonly WOPICheckFileInfo $checkFileInfo
    ) {
    }

    public function __invoke(string $fileId): CheckFileInfoResponse
    {
        $file = $this->filesRepository->find($fileId);
        if (!$file) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(WOPIVoter::VIEW, $file);

        return $this->checkFileInfo->generateResponse($file);
    }
}
