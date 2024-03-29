<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Security\Authorization\Voter;

use App\Entity\Account;
use App\Services\LegacyEnvironment;
use App\Utils\FileService;
use cs_environment;
use cs_file_item;
use cs_user_item;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FileVoter extends Voter
{
    final public const DOWNLOAD = 'FILE_DOWNLOAD';

    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly FileService $fileService
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
        $fileId = $subject;

        $fileItem = $this->fileService->getFile($fileId);
        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

        /** @var ?Account $user */
        $user = $token->getUser();

        if ($fileItem && self::DOWNLOAD === $attribute) {
            return $this->canDownload($fileItem, $currentUser, $user);
        }

        throw new LogicException('This code should not be reached!');
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
