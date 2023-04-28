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

namespace App\Form\Type\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AddRecurringFieldListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [FormEvents::PRE_SET_DATA => 'onPreSetData', FormEvents::POST_SUBMIT => 'onPostSubmit', FormEvents::PRE_SUBMIT => 'onPreSubmit'];
    }

    public function onPostSubmit(FormEvent $event)
    {
        $event->stopPropagation();
    }

    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (isset($data['recurring_select']) && 'none' != $data['recurring_select']) {
            $recurringNamespace = 'App\Form\Type\Recurring';
            if (!empty($data['recurring_select'])) {
                $class = $recurringNamespace.'\\'.$data['recurring_select'];
                $form->add('recurring_sub', $class);
            } else {
                $class = $recurringNamespace.'\\RecurringNoneType';
                $form->add('recurring_sub', $class);
            }
        }
    }

    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (isset($data['recurring_select']) && 'none' != $data['recurring_select']) {
            $recurringNamespace = 'App\Form\Type\Recurring';
            if (!empty($data['recurring_select']) && 'RecurringNoneType' != $data['recurring_select']) {
                $class = $recurringNamespace.'\\'.$data['recurring_select'];
                $form->add('recurring_sub', $class);
            } else {
                $class = $recurringNamespace.'\\RecurringNoneType';
                $form->add('recurring_sub', $class);
            }
        }
    }
}
