<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

namespace App\Security\Authorization\Voter;

use App\Entity\Account;
use App\Files\FilePermissionChecker;
use App\Repository\FilesRepository;
use App\Security\Permission\Legacy\LegacyPermissionBridge;
use App\Services\LegacyEnvironment;
use App\Utils\FileService;
use cs_environment;
use cs_file_item;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FileVoter extends Voter
{
    final public const DOWNLOAD = 'FILE_DOWNLOAD';

    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly FileService $fileService,
        private readonly FilePermissionChecker $filePermissionChecker,
        private readonly FilesRepository $filesRepository,
        private readonly LegacyPermissionBridge $legacyBridge,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, [
            self::DOWNLOAD,
        ]);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $fileId = (int) $subject;
        $fileItem = $this->fileService->getFile($fileId);
        /** @var ?Account $user */
        $user = $token->getUser();

        if ($fileItem instanceof cs_file_item && self::DOWNLOAD === $attribute) {
            return $this->canDownload($fileItem, $user);
        }

        // Preserves the pre-refactor behavior: a non-existent fileId
        // (FileService returns null) or an unsupported attribute throws.
        // Pinned by `FileVoterTest::testNonExistentFileIdRaisesLogicException`.
        throw new LogicException('This code should not be reached!');
    }

    /**
     * SEE check goes through the Doctrine-side
     * {@see FilePermissionChecker::canSee()}; the external-viewer
     * fallback uses {@see FilePermissionChecker::canExternalViewerSee()}.
     * Mirrors the legacy two-branch logic in
     * `cs_file_item::maySee` + `mayExternalViewerSeeLinkedItem` 1:1.
     */
    private function canDownload(cs_file_item $fileItem, ?Account $user): bool
    {
        $file = $this->filesRepository->find((int) $fileItem->getFileID());
        if ($file === null) {
            return false;
        }

        $actor = $this->legacyBridge->userFromLegacy(
            $this->legacyEnvironment->getCurrentUserItem(),
        );

        if ($actor !== null && $this->filePermissionChecker->canSee($actor, $file, $this->legacyBridge->currentRoom())) {
            return true;
        }

        if ($user instanceof Account
            && $this->filePermissionChecker->canExternalViewerSee((int) $fileItem->getFileID(), $user->getUsername())
        ) {
            return true;
        }

        return false;
    }
}
