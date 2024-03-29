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

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class CalendarEditType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', Types\TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Title',
                'translation_domain' => 'calendar',
                'required' => true,
            ])
            ->add('color', Types\TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Color',
                'translation_domain' => 'calendar',
                'required' => true,
                'attr' => ['class' => 'jscolor {hash:true}'],
            ]);

        if ($options['editExternalUrl']) {
            $builder->add('external_url', Types\TextType::class, [
                'label' => 'External url',
                'translation_domain' => 'calendar',
                'required' => false,
            ]);
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $calendar = $event->getData();
            $form = $event->getForm();
            $options = $event->getForm()->getConfig()->getOptions();

            // check if this is a "new" object
            if (!$calendar->getId()) {
                $form->add('new', Types\SubmitType::class, [
                    'attr' => ['class' => 'uk-button-primary'],
                    'label' => 'Create new calendar',
                    'translation_domain' => 'calendar',
                ]);
            } else {
                $form
                    ->add('update', Types\SubmitType::class, [
                        'attr' => ['class' => 'uk-button-primary'],
                        'label' => 'Update calendar',
                        'translation_domain' => 'calendar',
                    ]);
                if (!$calendar->getDefaultCalendar()) {
                    $form
                        ->add('delete', Types\SubmitType::class, [
                            'attr' => ['class' => 'uk-button-danger', 'data-confirm-delete' => $options['confirm-delete'], 'data-confirm-delete-cancel' => $options['confirm-delete-cancel'], 'data-confirm-delete-confirm' => $options['confirm-delete-confirm']],
                            'label' => 'Delete calendar',
                            'translation_domain' => 'calendar',
                            'validation_groups' => false,   // disable validation
                        ]);
                }
                $form
                    ->add('cancel', Types\SubmitType::class, [
                        'attr' => ['class' => 'uk-button-secondary'],
                        'label' => 'Cancel',
                        'translation_domain' => 'calendar',
                    ]);
            }
        });
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['editExternalUrl', 'confirm-delete', 'confirm-delete-cancel', 'confirm-delete-confirm'])
        ;
    }
}
