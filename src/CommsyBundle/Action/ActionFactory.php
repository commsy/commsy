<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 30.04.18
 * Time: 19:13
 */

namespace CommsyBundle\Action;


use Commsy\LegacyBundle\Utils\ItemService;
use Symfony\Component\Translation\TranslatorInterface;

class ActionFactory
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ItemService
     */
    private $itemService;

    public function __construct(TranslatorInterface $translator, ItemService $itemService)
    {
        $this->translator = $translator;
        $this->itemService = $itemService;
    }

    /**
     * @param string $action Name of the action
     *
     * @return ActionInterface The concrete action
     */
    public function make($action) {
        switch ($action) {
            case 'markread':
                return new MarkReadAction($this->translator, $this->itemService);

            default:
                return new UnknownAction($this->translator);
        }
    }
}