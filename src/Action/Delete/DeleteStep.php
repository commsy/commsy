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

use App\Rubric\Todo\TodoDeleter;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_item;
use cs_step_item;
use Symfony\Component\Routing\RouterInterface;

/**
 * Thin wrapper around {@see TodoDeleter::deleteStep()}. Steps are flat, so
 * the deleter performs a regular soft-delete and re-indexes the parent
 * todo via `ItemReindexEvent` (the embedded `steps` field in the
 * `commsy_todo` index needs refreshing).
 */
class DeleteStep implements DeleteInterface
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly TodoDeleter $todoDeleter,
        LegacyEnvironment $legacyEnvironment,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function delete(cs_item $item): void
    {
        $deleterId = (int) $this->legacyEnvironment->getCurrentUserItem()?->getItemID();
        $this->todoDeleter->deleteStep((int) $item->getItemId(), $deleterId);
    }

    public function getRedirectRoute(cs_item $item): ?string
    {
        /** @var cs_step_item $step */
        $step = $item;

        return $this->router->generate('app_todo_detail', [
            'roomId' => $step->getContextID(),
            'itemId' => $step->getTodoID(),
        ]);
    }
}
