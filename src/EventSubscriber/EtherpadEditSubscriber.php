<?php

namespace App\EventSubscriber;

use App\Event\ItemDeletedEvent;
use App\Services\EtherpadService;
use App\Utils\MaterialService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class EtherpadEditSubscriber implements EventSubscriberInterface
{
    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * @var MaterialService
     */
    private $materialService;

    /**
     * @var EtherpadService
     */
    private $etherpadService;

    public function __construct(
        ParameterBagInterface $params,
        MaterialService $materialService,
        EtherpadService $etherpadService)
    {
        $this->params = $params;
        $this->materialService = $materialService;
        $this->etherpadService = $etherpadService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.view' => [
                ['materialEdit', 0],
                ['materialSave', 10],
            ],
        ];
    }

    public function materialEdit(GetResponseForControllerResultEvent $event)
    {
        // get etherpad configuration
        $enabled = $this->params->get('commsy.etherpad.enabled');

        if ($enabled) {
            $result = $event->getControllerResult();
            if ($result && array_key_exists('isMaterial', $result)) {
                if ($result['isMaterial'] && array_key_exists('itemId', $result)) {
                    /** @var \cs_material_item $materialItem */
                    $materialItem = $this->materialService->getMaterial($result['itemId']);
                    if ($materialItem && $materialItem->getEtherpadEditor()) {
                        $result['useEtherpad'] = true;
                        $event->setControllerResult($result);
                    }
                }
            }
        }
    }

    public function materialSave(GetResponseForControllerResultEvent $event)
    {
        $enabled = $this->params->get('commsy.etherpad.enabled');

        if ($enabled) {
            $result = $event->getControllerResult();
            if ($result) {
                if (array_key_exists('item', $result) && $result['item']->getItemID()) {
                    /** @var \cs_material_item $materialItem */
                    $materialItem = $this->materialService->getMaterial($result['item']->getItemID());
                    if ($materialItem instanceof \cs_material_item) {
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
}