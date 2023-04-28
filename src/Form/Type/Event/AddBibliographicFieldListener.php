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

class AddBibliographicFieldListener implements EventSubscriberInterface
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

        if (isset($data['biblio_select']) && 'none' != $data['biblio_select']) {
            // $form->add('biblio_sub', new BiblioPlainType());

            $bibNamespace = 'App\Form\Type\Bibliographic';
            // $class = $bibNamespace.'\Biblio'.ucfirst($data['biblio_select']).'Type';
            if (!empty($data['biblio_select'])) {
                $class = $bibNamespace.'\\'.$data['biblio_select'];
                $form->add('biblio_sub', $class);
            } else {
                $class = $bibNamespace.'\\BiblioNoneType';
                $form->add('biblio_sub', $class);
            }
        }
    }

    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (isset($data['biblio_select']) && 'none' != $data['biblio_select']) {
            $bibNamespace = 'App\Form\Type\Bibliographic';

            if (!empty($data['biblio_select']) && 'BiblioType' != $data['biblio_select']) {
                $class = $bibNamespace.'\\'.$data['biblio_select'];
                $form->add('biblio_sub', $class);
            } else {
                $class = $bibNamespace.'\\BiblioNoneType';
                $form->add('biblio_sub', $class);
            }
        }
    }
}
