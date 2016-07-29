<?php
namespace CommsyBundle\Form\Type\Event;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;


/**
* 
*/
class AddEtherpadFormListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            // FormEvents::POST_SUBMIT => 'onPostSubmit',
            // FormEvents::PRE_SUBMIT => 'onPreSubmit'
        );
    }

    // public function onPostSubmit(FormEvent $event)
    // {
    //     $event->stopPropagation();
    // }

    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if ($data['draft']) {
            $form->add('editor_switch', CheckboxType::class, array(
                'label' => 'use etherpad',
                'required' => false,
                'translation_domain' => 'form',
            ));
        }
    }
}
