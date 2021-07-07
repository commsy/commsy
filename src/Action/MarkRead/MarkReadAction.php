<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 15:18
 */

namespace App\Action\MarkRead;


use App\Action\ActionInterface;
use App\Http\JsonDataResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class MarkReadAction implements ActionInterface
{
    /**
     * @var MarkReadInterface
     */
    private $markReadStrategy;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(MarkReadInterface $markReadStrategy, TranslatorInterface $translator)
    {
        $this->markReadStrategy = $markReadStrategy;
        $this->translator = $translator;
    }

    /**
     * @param \cs_room_item $roomItem
     * @param \cs_item[] $items
     * @return Response
     */
    public function execute(\cs_room_item $roomItem, array $items): Response
    {
        foreach ($items as $item) {
            $this->markReadStrategy->markRead($item);
        }

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> ' . $this->translator->trans('marked %count% entries as read', [
                    '%count%' => count($items),
                ]),
        ]);
    }

    /**
     * @param MarkReadInterface $markReadStrategy
     */
    public function setMarkReadStrategy($markReadStrategy): void
    {
        $this->markReadStrategy = $markReadStrategy;
    }


}