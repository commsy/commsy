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

namespace App\Action\Mark;

use App\Http\JsonDataResponse;
use App\Http\JsonErrorResponse;
use App\Services\LegacyEnvironment;
use App\Services\MarkedService;
use App\Utils\ItemService;
use App\Utils\ReaderService;
use cs_environment;
use cs_item;
use cs_room_item;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class InsertAction
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        private readonly TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment,
        private readonly ItemService $itemService,
        private readonly ReaderService $readerService,
        private readonly MarkedService $markService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function execute(cs_room_item $roomItem, array $items): Response
    {
        if (method_exists($roomItem, 'getArchived') && $roomItem->getArchived()) {
            return new JsonErrorResponse('<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i>'.$this->translator->trans('copy items in archived workspaces is not allowed'));
        }

        if ($this->legacyEnvironment->inPortal()) {
            return new JsonErrorResponse('<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i>'.$this->translator->trans('copy items in portal is not allowed'));
        }

        if ($this->legacyEnvironment->getCurrentUserItem()->isOnlyReadUser()) {
            return new JsonErrorResponse('<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i>'.$this->translator->trans('copy items as read only user is not allowed'));
        }

        if ('userroom' == $roomItem->getType()) {
            $imports = $this->markService->getListEntries(0);

            foreach ($items as $user) {
                foreach ($imports as $import) {
                    /** @var cs_item $import */
                    $import = $this->itemService->getTypedItem($import->getItemId());

                    $oldContextId = $this->legacyEnvironment->getCurrentContextID();
                    $this->legacyEnvironment->setCurrentContextID($roomItem->getItemID());
                    $copy = $import->copy();
                    $this->legacyEnvironment->setCurrentContextID($oldContextId);

                    if (empty($copy->getErrorArray())) {
                        $this->readerService->markRead($copy->getItemID(), $copy->getVersionID());
                    }
                }
            }
        } else {
            foreach ($items as $item) {
                $importItem = $this->itemService->getTypedItem($item->getItemId());
                $importItem->setExternalViewerAccounts([]);
                $copy = $importItem->copy();

                if (empty($copy->getErrorArray())) {
                    $this->readerService->markRead($copy->getItemID(), $copy->getVersionID());
                }
            }
        }

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-paste\'></i> '.$this->translator->trans('inserted %count% entries in this room', [
                '%count%' => count($items),
            ]),
        ]);
    }
}
