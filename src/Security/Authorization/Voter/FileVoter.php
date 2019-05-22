<?php

namespace App\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\FileService;

class FileVoter extends Voter
{
    const DOWNLOAD = 'FILE_DOWNLOAD';

    private $legacyEnvironment;
    private $itemService;
    private $fileService;

    public function __construct(LegacyEnvironment $legacyEnvironment, ItemService $itemService, FileService $fileService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->itemService = $itemService;
        $this->fileService = $fileService;
    }

    protected function supports($attribute, $object)
    {
        $supported = in_array($attribute, array(
            self::DOWNLOAD,
        ));
        return $supported;
    }

    protected function voteOnAttribute($attribute, $object, TokenInterface $token)
    {
        $fileId = $object;

        $fileItem = $this->fileService->getFile($fileId);
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        if ($fileItem && $attribute === self::DOWNLOAD) {
            return $this->canDownload($fileItem, $currentUser);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canDownload(\cs_file_item $fileItem, \cs_user_item $currentUser)
    {
        if ($fileItem->maySee($currentUser)) {
            return true;
        }

        if ($fileItem->mayPortfolioSeeLinkedItem($currentUser)) {
            return true;
        }

        return false;
    }
}
