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

namespace App\Form\Extension;

use App\EventSubscriber\LockValidationSubscriber;
use App\Lock\LockManager;
use LogicException;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LockExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly LockManager $lockManager,
        private readonly LockValidationSubscriber $lockValidationSubscriber,
        private readonly RequestStack $requestStack
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$options['lock_protection']) {
            return;
        }

        $builder
            ->addEventSubscriber($this->lockValidationSubscriber);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['lock_protection'] && !$view->parent && $options['compound']) {
            $factory = $form->getConfig()->getFormFactory();

            $request = $this->requestStack->getCurrentRequest();
            if (!$request || !$request->attributes->has('itemId')) {
                throw new LogicException('Unable to extract itemId from URL');
            }

            $itemId = $request->attributes->getInt('itemId');
            if ($this->lockManager->supportsLocking($itemId)) {
                $itemIdToLock = $this->lockManager->getItemIdForLock($itemId);
                $data = $this->lockManager->getToken($itemIdToLock);
                if ($data) {
                    $lockForm = $factory->createNamed($options['lock_field_name'], HiddenType::class, $data, [
                        'block_prefix' => 'lock',
                        'mapped' => false,
                    ]);

                    $view->children[$options['lock_field_name']] = $lockForm->createView($view);
                }
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'lock_protection' => false,
            'lock_field_name' => '_lock',
            'lock_message' => 'form.validation.lock',
            'lock_message_domain' => 'form',
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
