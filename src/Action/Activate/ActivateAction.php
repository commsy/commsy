<?php

namespace App\Action\Activate;

use App\Action\ActionInterface;
use App\Http\JsonDataResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class ActivateAction implements ActionInterface
{
    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
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
            $item->setActivationDate(null);
            $item->save();
        }

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-toggle-on\'></i> ' . $this->translator->trans('activated %count% entries', [
                    '%count%' => count($items),
                ]),
        ]);
    }
}
