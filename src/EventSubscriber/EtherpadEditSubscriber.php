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

namespace App\EventSubscriber;

use App\Etherpad\EtherpadException;
use App\Etherpad\MaterialPad;
use App\Event\ItemDeletedEvent;
use App\Services\EtherpadService;
use cs_material_item;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EtherpadEditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%commsy.etherpad.enabled%')]
        private readonly bool $enabled,
        private readonly EtherpadService $etherpadService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ItemDeletedEvent::NAME => 'onItemDeleted',
        ];
    }

    public function onItemDeleted(ItemDeletedEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $item = $event->getItem();
        if ($item instanceof cs_material_item) {
            $material = $item;

            if ($material->getEtherpadEditor()) {
                try {
                    $client = $this->etherpadService->getClient();
                    $client->deletePad(MaterialPad::locate($client, (int) $material->getItemID())->padId);
                } catch (EtherpadException) {
                    // The pad may never have been opened, or is already gone.
                    // The item is being deleted either way, so this must not
                    // stop the deletion.
                }
            }
        }
    }
}
