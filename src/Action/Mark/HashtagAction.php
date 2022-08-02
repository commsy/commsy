<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.07.18
 * Time: 15:28
 */

namespace App\Action\Mark;


use App\Action\ActionInterface;
use App\Http\JsonDataResponse;
use App\Utils\LabelService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class HashtagAction implements ActionInterface
{
    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var LabelService
     */
    private LabelService $labelService;

    /**
     * @var integer[]
     */
    private $hashtagIds;

    public function __construct(
        TranslatorInterface $translator,
        LabelService $labelService
    ) {
        $this->translator = $translator;
        $this->labelService = $labelService;
    }

    public function setHashtagIds(array $hashtagIds): void
    {
        $this->hashtagIds = $hashtagIds;
    }

    public function execute(\cs_room_item $roomItem, array $items): Response
    {
        if (empty($this->hashtagIds)) {
            throw new \Exception('no hashtag IDs given');
        }

        if (empty($items)) {
            throw new \Exception('no items given');
        }

        $itemIds = array_map(function (\cs_item $item) {
            return $item->getItemID();
        }, $items);

        $this->labelService->addHashtagsById($this->hashtagIds, $itemIds, $roomItem->getItemID());

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-hashtag\'></i> ' . $this->translator->trans('hashtagged %count% entries in list', [
                    '%count%' => count($items),
                ]),
        ]);
    }
}