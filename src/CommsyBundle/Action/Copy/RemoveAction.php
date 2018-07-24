<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.07.18
 * Time: 15:28
 */

namespace CommsyBundle\Action\Copy;


use Commsy\LegacyBundle\Services\LegacyEnvironment;
use CommsyBundle\Action\ActionInterface;
use CommsyBundle\Http\JsonDataResponse;
use CommsyBundle\Services\CopyService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class RemoveAction implements ActionInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    /**
     * @var CopyService
     */
    private $copyService;

    public function __construct(TranslatorInterface $translator, LegacyEnvironment $legacyEnvironment, CopyService $copyService)
    {
        $this->translator = $translator;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
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
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-trash-o\'></i> ' . $this->translator->transChoice('removed %count% entries from list', count($items), [
                '%count%' => count($items),
            ]),
        ]);
    }
}