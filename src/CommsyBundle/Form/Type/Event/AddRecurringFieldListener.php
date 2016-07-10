<?php
namespace CommsyBundle\Form\Type\Event;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CommsyBundle\Form\Type\Recurring\RecurringDailyType;
use CommsyBundle\Form\Type\Recurring\RecurringWeeklyType;


/**
* 
*/
class AddRecurringFieldListener implements EventSubscriberInterface
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

        if (isset($data['recurring_select']) && $data['recurring_select'] != 'none') {
            $recurringNamespace = 'CommsyBundle\Form\Type\Recurring';
            if (!empty($data['recurring_select'])) {
                $class = $recurringNamespace.'\\'.$data['recurring_select'];
                $form->add('recurring_sub', $class);
            } else {
                $class = $recurringNamespace.'\\'.'RecurringNoneType';
                $form->add('recurring_sub', $class);
            }
        }
    }

    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (isset($data['recurring_select']) && $data['recurring_select'] != 'none') {
            $recurringNamespace = 'CommsyBundle\Form\Type\Recurring';
            if (!empty($data['recurring_select']) && $data['recurring_select'] != 'RecurringNoneType') {
                $class = $recurringNamespace.'\\'.$data['recurring_select'];
                $form->add('recurring_sub', $class);
            } else {
                $class = $recurringNamespace.'\\'.'RecurringNoneType';
                $form->add('recurring_sub', $class);
            }
        }
    }
}
