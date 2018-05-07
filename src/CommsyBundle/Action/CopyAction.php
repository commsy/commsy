<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 30.04.18
 * Time: 19:18
 */

namespace CommsyBundle\Action;


use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Utils\ItemService;
use CommsyBundle\Http\JsonDataResponse;
use Symfony\Component\Translation\TranslatorInterface;

class CopyAction implements ActionInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    public function __construct(TranslatorInterface $translator, LegacyEnvironment $legacyEnvironment)
    {
        $this->translator = $translator;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function execute($items)
    {
        $sessionItem = $this->legacyEnvironment->getSessionItem();

        $currentClipboardIds = [];
        if ($sessionItem->issetValue('clipboard_ids')) {
            $currentClipboardIds = $sessionItem->getValue('clipboard_ids');
        }

        foreach ($items as $item) {
            if (!in_array($item->getItemID(), $currentClipboardIds)) {
                $currentClipboardIds[] = $item->getItemID();
                $sessionItem->setValue('clipboard_ids', $currentClipboardIds);
            }
        }

        $sessionManager = $this->legacyEnvironment->getSessionManager();
        $sessionManager->save($sessionItem);

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-copy\'></i> ' . $this->translator->transChoice('%count% copied entries', count($items), [
                    '%count%' => count($items),
                ]),
            'count' => count($currentClipboardIds),
        ]);
    }
}