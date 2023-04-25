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
use App\Utils\LabelService;
use cs_item;
use cs_room_item;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class HashtagAction implements ActionInterface
{
    /**
     * @var int[]
     */
    private ?array $hashtagIds = null;

    public function __construct(private readonly TranslatorInterface $translator, private readonly LabelService $labelService)
    {
    }

    public function setHashtagIds(array $hashtagIds): void
    {
        $this->hashtagIds = $hashtagIds;
    }

    public function execute(cs_room_item $roomItem, array $items): Response
    {
        if (empty($this->hashtagIds)) {
            throw new Exception('no hashtag IDs given');
        }

        if (empty($items)) {
            throw new Exception('no items given');
        }

        $itemIds = array_map(fn (cs_item $item) => $item->getItemID(), $items);

        $this->labelService->addHashtagsById($this->hashtagIds, $itemIds, $roomItem->getItemID());

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-hashtag\'></i> '.$this->translator->trans('hashtagged %count% entries in list', [
                    '%count%' => count($items),
                ]),
        ]);
    }
}
