<?php


namespace App\EventSubscriber;


use App\Model\SearchData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ChosenRubricSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'onPreSetData',
        );
    }

    public function onPreSetData(FormEvent $event) {
        /** @var SearchData $data **/
        $data = $event->getData();
        $form = $event->getForm();
        $formOptions = $form->getConfig()->getOptions();

        $formOptions['label'] = false;
        if ($data->getSelectedRubric() === 'todo') {
            $form->add('selectedStatusWidget', 'App\Form\Type\TodoStatusFilterType', $formOptions);
        }
    }
}
