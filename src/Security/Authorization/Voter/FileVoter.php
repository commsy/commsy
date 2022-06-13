<?php

namespace App\Security\Authorization\Voter;

use App\Entity\Account;
use cs_environment;
use cs_file_item;
use cs_user_item;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\FileService;

class FileVoter extends Voter
{
    const DOWNLOAD = 'FILE_DOWNLOAD';

    private cs_environment $legacyEnvironment;

    private ItemService $itemService;

    private FileService $fileService;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        ItemService $itemService,
        FileService $fileService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->itemService = $itemService;
        $this->fileService = $fileService;
    }

    protected function supports($attribute, $object)
    {
        $supported = in_array($attribute, [
            self::DOWNLOAD,
        ]);
        return $supported;
    }

    protected function voteOnAttribute($attribute, $object, TokenInterface $token)
    {
        $fileId = $object;

        $fileItem = $this->fileService->getFile($fileId);
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        /** @var ?Account $user */
        $user = $token->getUser();

        if ($fileItem && $attribute === self::DOWNLOAD) {
            return $this->canDownload($fileItem, $currentUser, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canDownload(
        cs_file_item $fileItem,
        cs_user_item $currentUser,
        ?Account $user)
    {
        if ($fileItem->maySee($currentUser)) {
            return true;
        }

        if ($fileItem->mayPortfolioSeeLinkedItem($currentUser)) {
            return true;
        }

        if ($user instanceof Account && $fileItem->mayExternalViewerSeeLinkedItem($user->getUsername())) {
            return true;
        }

        return false;
    }
}
