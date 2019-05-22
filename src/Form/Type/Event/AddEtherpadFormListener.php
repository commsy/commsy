<?php
namespace App\Form\Type\Event;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AddEtherpadFormListener implements EventSubscriberInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'onPreSetData',
        );
    }

    public function onPreSetData(FormEvent $event)
    {
        $enabled = $this->container->getParameter('commsy.etherpad.enabled');

        $data = $event->getData();
        $form = $event->getForm();

        if ($data['draft'] && $enabled) {
            $form->add('editor_switch', CheckboxType::class, array(
                'label' => 'use etherpad',
                'required' => false,
                'translation_domain' => 'form',
            ));
        } else {
            $form->add('editor_switch', CheckboxType::class, array(
                'label' => 'use etherpad',
                'required' => false,
                'translation_domain' => 'form',
                'disabled' => 'true',
                'label_attr' => ['class' => 'uk-text-muted'],
            ));
        }
    }
}
