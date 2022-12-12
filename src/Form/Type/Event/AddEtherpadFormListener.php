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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AddEtherpadFormListener implements EventSubscriberInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public static function getSubscribedEvents()
    {
        return [FormEvents::PRE_SET_DATA => 'onPreSetData'];
    }

    public function onPreSetData(FormEvent $event)
    {
        $enabled = $this->container->getParameter('commsy.etherpad.enabled');

        $data = $event->getData();
        $form = $event->getForm();

        if ($data['draft'] && $enabled) {
            $form->add('editor_switch', CheckboxType::class, ['label' => 'use etherpad', 'required' => false, 'translation_domain' => 'form']);
        } else {
            $form->add('editor_switch', CheckboxType::class, ['label' => 'use etherpad', 'required' => false, 'translation_domain' => 'form', 'disabled' => 'true', 'label_attr' => ['class' => 'uk-text-muted']]);
        }
    }
}
