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
use cs_room_item;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MarkAction implements ActionInterface
{
    public function __construct(private TranslatorInterface $translator, private SessionInterface $session)
    {
    }

    public function execute(cs_room_item $roomItem, array $items): Response
    {
        $currentClipboardIds = $this->session->get('clipboard_ids', []);
        foreach ($items as $item) {
            if (!in_array($item->getItemID(), $currentClipboardIds)) {
                $currentClipboardIds[] = $item->getItemID();
                $this->session->set('clipboard_ids', $currentClipboardIds);
            }
        }

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bookmark-o\'></i> '.$this->translator->trans('%count% marked entries', [
                    '%count%' => count($items),
                ]),
            'count' => is_countable($currentClipboardIds) ? count($currentClipboardIds) : 0,
        ]);
    }
}
