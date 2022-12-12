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

use App\Form\Type\Context\CommunityType;
use App\Form\Type\Context\ProjectType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AddContextFieldListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
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
        $formOptions = $form->getConfig()->getOptions();

        if (isset($data['type_select']) && 'project' == $data['type_select']) {
            $form->add('type_sub', ProjectType::class, $formOptions);
        } elseif (isset($data['type_select']) && 'community' == $data['type_select']) {
            $form->add('type_sub', CommunityType::class, [
                'templates' => $formOptions['templates'],
                'preferredChoices' => $formOptions['preferredChoices'],
            ]);
        }
    }

    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $formOptions = $form->getConfig()->getOptions();

        if (isset($data['type_select']) && 'project' == $data['type_select']) {
            $form->add('type_sub', ProjectType::class, $formOptions);
        } elseif (isset($data['type_select']) && 'community' == $data['type_select']) {
            $form->add('type_sub', CommunityType::class, [
                'templates' => $formOptions['templates'],
                'preferredChoices' => $formOptions['preferredChoices'],
            ]);
        }
    }
}
