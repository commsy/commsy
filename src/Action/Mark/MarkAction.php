<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 03.07.18
 * Time: 16:01
 */

namespace App\Action\Mark;


use App\Services\LegacyEnvironment;
use App\Action\ActionInterface;
use App\Http\JsonDataResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MarkAction implements ActionInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    private $session;

    public function __construct(TranslatorInterface $translator, SessionInterface $session)
    {
        $this->translator = $translator;
        $this->session = $session;
    }

    public function execute(\cs_room_item $roomItem, array $items): Response
    {
        $currentClipboardIds = $this->session->get('clipboard_ids', []);
        foreach ($items as $item) {
            if (!in_array($item->getItemID(), $currentClipboardIds)) {
                $currentClipboardIds[] = $item->getItemID();
                $this->session->set('clipboard_ids', $currentClipboardIds);
            }
        }

        return new JsonDataResponse([
            'message' => '<i class=\'uk-icon-justify uk-icon-medium uk-icon-bookmark-o\'></i> ' . $this->translator->trans('%count% marked entries', [
                    '%count%' => count($items),
                ]),
            'count' => count($currentClipboardIds),
        ]);
    }
}