<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 16:35
 */

namespace App\Action\Delete;


use App\Services\LegacyEnvironment;
use App\Utils\ItemService;
use App\Utils\UserService;
use App\Action\ActionInterface;
use App\Http\JsonDataResponse;
use App\Http\JsonRedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class DeleteAction implements ActionInterface
{
    /**
     * @var DeleteInterface
     */
    private $deleteStrategy;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ItemService
     */
    private $itemService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;


    /**
     * @param DeleteInterface $deleteStrategy
     */
    public function setDeleteStrategy(DeleteInterface $deleteStrategy): void
    {
        $this->deleteStrategy = $deleteStrategy;
    }


    public function __construct(
        DeleteGeneric $deleteGeneric,
        TranslatorInterface $translator,
        ItemService $itemService,
        UserService $userService,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->deleteStrategy = $deleteGeneric;
        $this->translator = $translator;
        $this->itemService = $itemService;
        $this->userService = $userService;
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

                $numDeletedItems++;
            }
        }

        if ($redirectReferenceItem) {
            if ($this->deleteStrategy->getRedirectRoute($redirectReferenceItem)) {
                return new JsonRedirectResponse($this->deleteStrategy->getRedirectRoute($redirectReferenceItem));
            }
        }

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-trash-o\'></i> ' . $this->translator->trans('%count% deleted entries', [
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
        if ($item->getItemType() == 'user') {
            if (!$this->userService->contextHasModerators($room->getItemId(), [$item->getItemId()])) {
                return false;
            }
        }

        return true;
    }
}