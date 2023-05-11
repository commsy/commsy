<?php

namespace App\Controller\Api\WOPI;

use App\Entity\Files;
use App\Repository\FilesRepository;
use App\Security\Voter\WOPIVoter;
use App\Utils\FileService;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;

final class PostPutFile extends AbstractController
{
    public function __construct(
        private readonly FilesRepository $filesRepository,
        private readonly ManagerRegistry $registry,
        private readonly FileService $fileService,
        private readonly RequestStack $requestStack
    ) {
    }

    public function __invoke(string $fileId): array
    {
        /** @var Files $file */
        $file = $this->filesRepository->find($fileId);
        if (!$file) {
            throw $this->createNotFoundException();
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new Exception();
        }

        $this->denyAccessUnlessGranted(WOPIVoter::EDIT, $file);

        $filesystem = new Filesystem();
        $absPath = $this->fileService->makeAbsolute($file);
        $filesystem->dumpFile($absPath, $request->getContent());

        $file->setSize(filesize($absPath));

        $em = $this->registry->getManager();
        $em->persist($file);
        $em->flush();

        return [];
    }
}
