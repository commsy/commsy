<?php

namespace App\Controller\Api\WOPI;

use _PHPStan_532094bc1\Nette\Neon\Exception;
use App\Entity\Files;
use App\Lock\FileLockManager;
use App\Repository\FilesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

final class PostLock extends AbstractController
{
    public function __construct(
        private readonly FilesRepository $filesRepository,
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

        $operation = $request->headers->get('X-WOPI-Override');
        if (!in_array($operation, ['LOCK', 'REFRESH_LOCK', 'UNLOCK'])) {
            throw new Exception('Unsupported operation');
        }

        if (!$request->headers->has('X-WOPI-Lock')) {
            throw new Exception('X-WOPI-Lock header missing');
        }
        $lock = $request->headers->get('X-WOPI-Lock');

        if ($operation === 'LOCK') {
            if (!$this->lockManager->isLocked($file)) {
                $this->lockManager->lock($file, $lock);
                return new Response();
            } else {
                if ($file->getLockingId() === $lock) {
                    $this->lockManager->renew($file);
                    return new Response();
                }
            }

            return new Response(null, Response::HTTP_CONFLICT, [
                'X-WOPI-Lock' => $file->getLockingId(),
            ]);
        }

        if ($operation === 'REFRESH_LOCK') {
            if (!$this->lockManager->isLocked($file)) {
                return new Response(null, Response::HTTP_CONFLICT, [
                    'X-WOPI-Lock' => '',
                ]);
            } else {
                if ($file->getLockingId() === $lock) {
                    $this->lockManager->renew($file);
                    return new Response();
                } else {
                    return new Response(null, Response::HTTP_CONFLICT, [
                        'X-WOPI-Lock' => $file->getLockingId(),
                    ]);
                }
            }
        }

        if ($operation === 'UNLOCK') {
            if (!$this->lockManager->isLocked($file)) {
                return new Response(null, Response::HTTP_CONFLICT, [
                    'X-WOPI-Lock' => '',
                ]);
            } else {
                if ($file->getLockingId() === $lock) {
                    $this->lockManager->unlock($file);
                    return new Response();
                } else {
                    return new Response(null, Response::HTTP_CONFLICT, [
                        'X-WOPI-Lock' => $file->getLockingId(),
                    ]);
                }
            }
        }

        throw new Exception();
    }
}
