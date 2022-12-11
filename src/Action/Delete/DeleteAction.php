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

namespace App\Action\Delete;

use App\Action\ActionInterface;
use App\Http\JsonDataResponse;
use App\Http\JsonRedirectResponse;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteAction implements ActionInterface
{
    private \cs_environment $legacyEnvironment;

    public function setDeleteStrategy(DeleteInterface $deleteStrategy): void
    {
        $this->deleteStrategy = $deleteStrategy;
    }

    public function __construct(
        private DeleteGeneric $deleteStrategy,
        private TranslatorInterface $translator,
        private UserService $userService,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function execute(\cs_room_item $roomItem, array $items): Response
    {
        $numDeletedItems = 0;

        $redirectReferenceItem = null;
        foreach ($items as $item) {
            if (!$redirectReferenceItem) {
                $redirectReferenceItem = $item;
            }

            if ($this->isDeletionAllowed($roomItem, $item)) {
                $this->deleteStrategy->delete($item);

                ++$numDeletedItems;
            }
        }

        if ($redirectReferenceItem) {
            if ($this->deleteStrategy->getRedirectRoute($redirectReferenceItem)) {
                return new JsonRedirectResponse($this->deleteStrategy->getRedirectRoute($redirectReferenceItem));
            }
        }

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-trash-o\'></i> '.$this->translator->trans('%count% deleted entries', [
                '%count%' => $numDeletedItems,
            ]),
        ]);
    }

    public function getStrategy(): DeleteInterface
    {
        return $this->deleteStrategy;
    }

    private function isDeletionAllowed(\cs_room_item $room, \cs_item $item): bool
    {
        $currentUser = $this->legacyEnvironment->getCurrentUser();
        if (!$item->mayEdit($currentUser)) {
            return false;
        }

        // it is not allowed to delete the last moderator of a room
        if ('user' == $item->getItemType()) {
            if (!$this->userService->contextHasModerators($room->getItemId(), [$item->getItemId()])) {
                return false;
            }
        }

        return true;
    }
}
