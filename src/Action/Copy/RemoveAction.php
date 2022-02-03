<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.07.18
 * Time: 15:28
 */

namespace App\Action\Copy;


use App\Action\ActionInterface;
use App\Http\JsonDataResponse;
use App\Services\CopyService;
use App\Services\LegacyEnvironment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class RemoveAction implements ActionInterface
{
    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var CopyService
     */
    private CopyService $copyService;

    public function __construct(
        TranslatorInterface $translator,
        LegacyEnvironment $legacyEnvironment,
        CopyService $copyService
    ) {
        $this->translator = $translator;
        $this->copyService = $copyService;
    }

    public function execute(\cs_room_item $roomItem, array $items): Response
    {
        $ids = [];
        foreach ($items as $item) {
            $ids[] = $item->getItemId();
        }

        $this->copyService->removeEntries($roomItem->getItemID(), $ids);

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-trash-o\'></i> ' . $this->translator->trans('removed %count% entries from list', [
                    '%count%' => count($items),
                ]),
        ]);
    }
}