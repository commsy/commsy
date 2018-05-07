<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 30.04.18
 * Time: 19:18
 */

namespace CommsyBundle\Action;


use Commsy\LegacyBundle\Utils\ItemService;
use Symfony\Component\Translation\TranslatorInterface;

class MarkReadAction implements ActionInterface
{
    private $translator;
    private $itemService;

    public function __construct(TranslatorInterface $translator, ItemService $itemService)
    {
        $this->translator = $translator;
        $this->itemService = $itemService;
    }

    public function execute($items)
    {
        $this->itemService->markRead($items);

        return [
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-check-square-o\'></i> ' . $this->translator->transChoice('marked %count% entries as read', count($items), ['%count%' => count($items)]),
        ];
    }
}