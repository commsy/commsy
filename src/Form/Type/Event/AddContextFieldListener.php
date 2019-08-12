<?php
namespace App\Form\Type\Event;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
* 
*/
class AddContextFieldListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::POST_SUBMIT => 'onPostSubmit',
            FormEvents::PRE_SUBMIT => 'onPreSubmit'
        );
    }

    public function onPostSubmit(FormEvent $event)
    {
        $event->stopPropagation();
    }

    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $formOptions = $form->getConfig()->getOptions();

        if (isset($data['type_select']) && $data['type_select'] == 'project') {
            $form->add('type_sub', 'App\Form\Type\Context\ProjectType', $formOptions);
        } else if (isset($data['type_select']) && $data['type_select'] == 'community') {
            $form->add('type_sub', 'App\Form\Type\Context\CommunityType', $formOptions);
        }
    }

    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $formOptions = $form->getConfig()->getOptions();

        if (isset($data['type_select']) && $data['type_select'] == 'project') {
            $form->add('type_sub', 'App\Form\Type\Context\ProjectType', $formOptions);
        } else if (isset($data['type_select']) && $data['type_select'] == 'community') {
            $form->add('type_sub', 'App\Form\Type\Context\CommunityType', $formOptions);
        }
    }
}
