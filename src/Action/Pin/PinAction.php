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

namespace App\Action\Pin;

use App\Action\ActionInterface;
use App\Http\JsonDataResponse;
use cs_item;
use cs_room_item;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class PinAction implements ActionInterface
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @param cs_item[] $items
     */
    public function execute(cs_room_item $roomItem, array $items): Response
    {
        if (empty($items)) {
            throw new Exception('no items given');
        }

        foreach ($items as $item) {
            $item->setPinned(true);
            $item->save();
        }

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-thumb-tack\'></i> '.$this->translator->trans('Entry pinned|%count% entries pinned', [
                        '%count%' => count($items),
                    ],
                    'item'),
        ]);
    }
}
