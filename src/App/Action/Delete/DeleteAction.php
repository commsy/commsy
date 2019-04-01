<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 16:35
 */

namespace App\Action\Delete;


use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Utils\ItemService;
use Commsy\LegacyBundle\Utils\UserService;
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

    public function __construct(
        DeleteInterface $deleteStrategy,
        TranslatorInterface $translator,
        ItemService $itemService,
        UserService $userService,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->deleteStrategy = $deleteStrategy;
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
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-trash-o\'></i> ' . $this->translator->transChoice('%count% deleted entries', $numDeletedItems, [
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

        // it is not allow to delete the last moderator of a room
        if ($item->getItemType() == 'user') {
            if (!$this->contextHasModerators($room, [$item->getItemId()])) {
                return false;
            }
        }

        return true;
    }

    private function contextHasModerators(\cs_room_item $room, $selectedIds)
    {
        $this->userService->resetLimits();
        $moderators = $this->userService->getModeratorsForContext($room->getItemId());

        $moderatorIds = [];
        foreach ($moderators as $moderator) {
            $moderatorIds[] = $moderator->getItemId();
        }

        foreach ($selectedIds as $selectedId) {
            if (in_array($selectedId, $moderatorIds)) {
                if(($key = array_search($selectedId, $moderatorIds)) !== false) {
                    unset($moderatorIds[$key]);
                }
            }
        }

        return !empty($moderatorIds);
    }
}