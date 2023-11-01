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

use App\Event\ItemDeletedEvent;
use App\Services\EtherpadService;
use cs_material_item;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EtherpadEditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ParameterBagInterface $params,
        private EtherpadService $etherpadService
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
        $enabled = $this->params->get('commsy.etherpad.enabled');

        if (!$enabled) {
            return;
        }

        $item = $event->getItem();
        if ($item instanceof cs_material_item) {
            $material = $item;

            if ($material->getEtherpadEditor() && $material->getEtherpadEditorID()) {
                $client = $this->etherpadService->getClient();

                $client->deletePad($material->getEtherpadEditorID());

                $material->unsetEtherpadEditorID();
                $material->save();
            }
        }
    }
}
