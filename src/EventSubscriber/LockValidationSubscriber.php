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

namespace App\EventSubscriber;

use App\Lock\LockManager;
use LogicException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class LockValidationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LockManager $lockManager,
        private TranslatorInterface $translator,
        private RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'onFormPreSubmit',
        ];
    }

    public function onFormPreSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $config = $form->getConfig();

        $fieldName = $config->getOption('lock_field_name');

        if ($form->isRoot() && $config->getOption('compound')) {
            $data = $event->getData();

            $lock = $data[$fieldName] ?? null;

            $request = $this->requestStack->getCurrentRequest();
            if (!$request || !$request->attributes->has('itemId')) {
                throw new LogicException('Unable to extract itemId from URL');
            }

            $itemId = $request->attributes->getInt('itemId');
            if ($this->lockManager->supportsLocking($itemId)) {
                $itemId = $this->lockManager->getItemIdForLock($itemId);

                if ($lock === null || !$this->lockManager->isLockValid($itemId, $lock)) {
                    $errorMessage = $this->translator->trans(
                        $config->getOption('lock_message'),
                        [],
                        $config->getOption('lock_message_domain')
                    );
                    $form->addError(new FormError($errorMessage));
                }
            }

            if (is_array($data)) {
                unset($data[$fieldName]);
                $event->setData($data);
            }
        }
    }
}
