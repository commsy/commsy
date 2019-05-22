<?php
namespace App\Form\Type\Event;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Form\Type\Recurring\RecurringDailyType;
use App\Form\Type\Recurring\RecurringWeeklyType;
use App\Form\Type\Recurring\RecurringMonthlyType;
use App\Form\Type\Recurring\RecurringYearlyType;

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
            $recurringNamespace = 'App\Form\Type\Recurring';
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
            $recurringNamespace = 'App\Form\Type\Recurring';
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
