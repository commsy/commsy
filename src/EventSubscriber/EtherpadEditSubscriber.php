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
use App\Utils\MaterialService;
use cs_material_item;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;

class EtherpadEditSubscriber implements EventSubscriberInterface
{
    public function __construct(private ParameterBagInterface $params, private MaterialService $materialService, private EtherpadService $etherpadService)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.view' => [
                ['materialEdit', 0],
                ['materialSave', 10],
            ],
            ItemDeletedEvent::NAME => 'onItemDeleted',
        ];
    }

    public function materialEdit(ViewEvent $event)
    {
        // get etherpad configuration
        $enabled = $this->params->get('commsy.etherpad.enabled');

        if ($enabled) {
            $result = $event->getControllerResult();
            if ($result && array_key_exists('isMaterial', $result)) {
                if ($result['isMaterial'] && array_key_exists('itemId', $result)) {
                    /** @var cs_material_item $materialItem */
                    $materialItem = $this->materialService->getMaterial($result['itemId']);
                    if ($materialItem && $materialItem->getEtherpadEditor()) {
                        $result['useEtherpad'] = true;
                        $event->setControllerResult($result);
                    }
                }
            }
        }
    }

    public function materialSave(ViewEvent $event)
    {
        $enabled = $this->params->get('commsy.etherpad.enabled');

        if ($enabled) {
            $result = $event->getControllerResult();
            if ($result) {
                if (array_key_exists('item', $result) && $result['item']->getItemID()) {
                    /** @var cs_material_item $materialItem */
                    $materialItem = $this->materialService->getMaterial($result['item']->getItemID());
                    if ($materialItem instanceof cs_material_item) {
                        if ($materialItem->getEtherpadEditor() && $materialItem->getEtherpadEditorID()) {
                            // get description text from etherpad
                            $client = $this->etherpadService->getClient();

                            // get pad and get text from pad
                            $textObject = $client->getHTML($materialItem->getEtherpadEditorID());

                            // save etherpad text to material description
                            $materialItem->setDescription(nl2br($textObject->html));
                            $materialItem->save();
                        }
                    }
                }
            }
        }
    }

    public function onItemDeleted(ItemDeletedEvent $event)
    {
        $enabled = $this->params->get('commsy.etherpad.enabled');

        if ($enabled) {
            $item = $event->getItem();
            if ($item instanceof cs_material_item) {
                /** @var cs_material_item $material */
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
}
