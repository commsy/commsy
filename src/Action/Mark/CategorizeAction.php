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

class CategorizeAction implements ActionInterface
{
    /**
     * @var int[]
     */
    private ?array $categoryIds = null;

    public function __construct(private TranslatorInterface $translator, private LabelService $labelService)
    {
    }

    public function setCategoryIds(array $categoryIds): void
    {
        $this->categoryIds = $categoryIds;
    }

    public function execute(cs_room_item $roomItem, array $items): Response
    {
        if (empty($this->categoryIds)) {
            throw new Exception('no category IDs given');
        }

        if (empty($items)) {
            throw new Exception('no items given');
        }

        $itemIds = array_map(fn (cs_item $item) => $item->getItemID(), $items);

        $this->labelService->addCategoriesById($this->categoryIds, $itemIds, $roomItem->getItemID());

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-sitemap\'></i> '.$this->translator->trans('categorized %count% entries in list', [
                    '%count%' => count($items),
                ]),
        ]);
    }
}
