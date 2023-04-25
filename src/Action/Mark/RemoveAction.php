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

use App\Action\ActionInterface;
use App\Http\JsonDataResponse;
use App\Services\MarkedService;
use cs_room_item;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class RemoveAction implements ActionInterface
{
    public function __construct(private readonly TranslatorInterface $translator, private readonly MarkedService $markedService)
    {
    }

    public function execute(cs_room_item $roomItem, array $items): Response
    {
        $ids = [];
        foreach ($items as $item) {
            $ids[] = $item->getItemId();
        }

        $this->markedService->removeEntries($roomItem->getItemID(), $ids);

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-remove\'></i> '.$this->translator->trans('removed %count% entries from list', [
                    '%count%' => count($items),
                ]),
        ]);
    }
}
