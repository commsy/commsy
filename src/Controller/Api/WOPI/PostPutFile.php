<?php

namespace App\Controller\Api\WOPI;

use App\Entity\Files;
use App\Lock\FileLockManager;
use App\Repository\FilesRepository;
use App\Security\Voter\WOPIVoter;
use App\Utils\FileService;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

final class PostPutFile extends AbstractController
{
    public function __construct(
        private readonly FilesRepository $filesRepository,
        private readonly ManagerRegistry $registry,
        private readonly FileService $fileService,
        private readonly RequestStack $requestStack,
        private readonly FileLockManager $lockManager
    ) {
    }

    public function __invoke(string $fileId): Response
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

        $operation = $request->headers->get('X-WOPI-Override');
        if ($operation !== 'PUT') {
            throw new Exception('Unsupported operation');
        }

        $filesystem = new Filesystem();
        $absPath = $this->fileService->makeAbsolute($file);

        if (!file_exists($absPath)) {
            return new Response(null, Response::HTTP_CONFLICT, [
                'X-WOPI-Lock' => '',
            ]);
        }

        if (!$this->lockManager->isLocked($file) && filesize($absPath) !== 0) {
            return new Response(null, Response::HTTP_CONFLICT, [
                'X-WOPI-Lock' => '',
            ]);
        }

        if ($this->lockManager->isLocked($file)) {
            if (!$request->headers->has('X-WOPI-Lock')) {
                throw new Exception('X-WOPI-Lock header missing');
            }
            $lock = $request->headers->get('X-WOPI-Lock');

            if ($file->getLockingId() !== $lock) {
                return new Response(null, Response::HTTP_CONFLICT, [
                    'X-WOPI-Lock' => $file->getLockingId(),
                ]);
            }
        }

        $filesystem->dumpFile($absPath, $request->getContent());

        $file->setSize(filesize($absPath));

        $em = $this->registry->getManager();
        $em->persist($file);
        $em->flush();

        return new Response();
    }
}
