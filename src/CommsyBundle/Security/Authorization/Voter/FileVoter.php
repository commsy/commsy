<?php

namespace CommsyBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Utils\ItemService;
use Commsy\LegacyBundle\Utils\FileService;

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
        // TODO: better get the file's related item and call maySee() on it?

         if ($fileItem->mayEdit($currentUser)) {
             return true;
         }

        return false;
    }
}
