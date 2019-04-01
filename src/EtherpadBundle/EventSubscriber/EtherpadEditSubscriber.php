<?php
namespace EtherpadBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

use Symfony\Component\DependencyInjection\ContainerInterface;

use App\Utils\MaterialService;

class EtherpadEditSubscriber implements EventSubscriberInterface
{
    private $container;

    private $materialService;

    public function __construct(ContainerInterface $container, MaterialService $materialService)
    {
        $this->container = $container;
        $this->materialService = $materialService;
    }

    public static function getSubscribedEvents()
    {
        // return the subscribed events, their methods and priorities
        return array(
           'kernel.view' => array(
               array('materialEdit', 0),
               array('materialSave', 10),
           )
        );
    }

    public function materialEdit(GetResponseForControllerResultEvent $event)
    {
        // get etherpad configuration
        $enabled = $this->container->getParameter('commsy.etherpad.enabled');

        if ($enabled) {
            $result = $event->getControllerResult();
            if ($result) {
                if (array_key_exists('isMaterial', $result)) {
                    if ($result['isMaterial']) {
                        if (array_key_exists('itemId', $result)) {
                            $materialItem = $this->materialService->getMaterial($result['itemId']);
                            if ($materialItem->getEtherpadEditor()) {
                                $result['useEtherpad'] = true;
                                $event->setControllerResult($result);
                            }
                        }
                    }
                }
            }
        }
        
    }

    public function materialSave(GetResponseForControllerResultEvent $event)
    {
        $enabled = $this->container->getParameter('commsy.etherpad.enabled');

        if ($enabled) {
            $result = $event->getControllerResult();
            if ($result) {
                if (array_key_exists('item', $result) && $result['item']->getItemID()) {
                    $materialItem = $this->materialService->getMaterial($result['item']->getItemID());
                    if (get_class($materialItem) == 'cs_material_item') {
                        if ($materialItem->getEtherpadEditor() && $materialItem->getEtherpadEditorID()) {
                            // get description text from etherpad
                            $etherpadService = $this->container->get('commsy.etherpad_service');
                            $client = $etherpadService->getClient();

                            $materialItem = $result['item'];

                            // get pad and get text from pad
                            $textObject = $client->getHTML($materialItem->getEtherpadEditorId());

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