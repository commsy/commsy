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
use App\Services\CurrentContextResolver;
use App\Services\CurrentUserResolver;
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
        private readonly MarkedService $markService,
        private readonly CurrentUserResolver $currentUserResolver,
        private readonly CurrentContextResolver $currentContextResolver
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

        if ($this->currentUserResolver->getUser()?->isReadOnlyUser() ?? false) {
            return new JsonErrorResponse('<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-bolt\'></i>'.$this->translator->trans('copy items as read only user is not allowed'));
        }

        $inserted = 0;
        if ('userroom' == $roomItem->getType()) {
            $imports = $this->markService->getListEntries(0);

            foreach ($items as $user) {
                foreach ($imports as $import) {
                    /** @var cs_item $import */
                    $import = $this->itemService->getTypedItem($import->getItemId());
                    if (!self::canBeCopied($import)) {
                        continue;
                    }

                    $oldContextId = $this->currentContextResolver->getContextId() ?? 0;
                    $this->legacyEnvironment->setCurrentContextID($roomItem->getItemID());
                    $copy = $import->copy();
                    $this->legacyEnvironment->setCurrentContextID($oldContextId);

                    if (empty($copy->getErrorArray())) {
                        ++$inserted;
                        $this->readerService->markItemWithVersionAsRead($copy, $copy->getVersionID());
                    }
                }
            }
        } else {
            foreach ($items as $item) {
                $importItem = $this->itemService->getTypedItem($item->getItemId());
                if (!self::canBeCopied($importItem)) {
                    continue;
                }

                $importItem->setExternalViewerAccounts([]);
                $copy = $importItem->copy();

                if (empty($copy->getErrorArray())) {
                    ++$inserted;
                    $this->readerService->markItemWithVersionAsRead($copy, $copy->getVersionID());
                }
            }
        }

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-paste\'></i> '.$this->translator->trans('inserted %count% entries in this room', [
                '%count%' => $inserted,
            ]),
        ]);
    }

    /**
     * Only announcements, dates, discussions, materials and tasks can be
     * copied; any other marked entry (a group, topic, person or room from
     * the search) is skipped.
     */
    private static function canBeCopied(?cs_item $item): bool
    {
        return null !== $item && method_exists($item, 'copy');
    }
}
