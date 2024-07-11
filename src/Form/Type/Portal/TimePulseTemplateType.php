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

namespace App\Form\Type\Portal;

use App\Model\TimePulseTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TimePulseTemplateType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /**
             * @var TimePulseTemplate $timePulseTemplate
             */
            $timePulseTemplate = $event->getData();
            $form = $event->getForm();

            // check if this is a "new" object
            if (empty($timePulseTemplate->getTitleGerman()) && empty($timePulseTemplate->getTitleEnglish())) {
                $form
                    ->add('titleGerman', Types\TextType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                            new Assert\Regex([
                                'pattern' => '/%[1-6]/',
                                'message' => 'The time pulse name must contain a year placeholder',
                            ]),
                        ],
                        'label' => 'New time pulse',
                        'attr' => ['placeholder' => 'de'],
                    ])
                    ->add('titleEnglish', Types\TextType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                            new Assert\Regex([
                                'pattern' => '/%[1-6]/',
                                'message' => 'The time pulse name must contain a year placeholder',
                            ]),
                        ],
                        'label' => false,
                        'attr' => ['placeholder' => 'en'],
                        'help' => 'Time pulses help',
                        'help_html' => true,
                    ])
                    ->add('startDay', Types\ChoiceType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                        ],
                        'placeholder' => 'Time pulse select day',
                        'label' => 'Time pulse start',
                        'expanded' => false,
                        'choices' => [
                            '1' => 1,
                            '2' => 2,
                            '3' => 3,
                            '4' => 4,
                            '5' => 5,
                            '6' => 6,
                            '7' => 7,
                            '8' => 8,
                            '9' => 9,
                            '10' => 10,
                            '11' => 11,
                            '12' => 12,
                            '13' => 13,
                            '14' => 14,
                            '15' => 15,
                            '16' => 16,
                            '17' => 17,
                            '18' => 18,
                            '19' => 19,
                            '20' => 20,
                            '21' => 21,
                            '22' => 22,
                            '23' => 23,
                            '24' => 24,
                            '25' => 25,
                            '26' => 26,
                            '27' => 27,
                            '28' => 28,
                            '29' => 29,
                            '30' => 30,
                            '31' => 31,
                        ],
                    ])
                    ->add('startMonth', Types\ChoiceType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                        ],
                        'placeholder' => 'Time pulse select month',
                        'label' => false,
                        'choices' => [
                            '1' => 1,
                            '2' => 2,
                            '3' => 3,
                            '4' => 4,
                            '5' => 5,
                            '6' => 6,
                            '7' => 7,
                            '8' => 8,
                            '9' => 9,
                            '10' => 10,
                            '11' => 11,
                            '12' => 12,
                        ],
                    ])
                    ->add('endDay', Types\ChoiceType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                        ],
                        'placeholder' => 'Time pulse select day',
                        'label' => 'Time pulse end',
                        'choices' => [
                            '1' => 1,
                            '2' => 2,
                            '3' => 3,
                            '4' => 4,
                            '5' => 5,
                            '6' => 6,
                            '7' => 7,
                            '8' => 8,
                            '9' => 9,
                            '10' => 10,
                            '11' => 11,
                            '12' => 12,
                            '13' => 13,
                            '14' => 14,
                            '15' => 15,
                            '16' => 16,
                            '17' => 17,
                            '18' => 18,
                            '19' => 19,
                            '20' => 20,
                            '21' => 21,
                            '22' => 22,
                            '23' => 23,
                            '24' => 24,
                            '25' => 25,
                            '26' => 26,
                            '27' => 27,
                            '28' => 28,
                            '29' => 29,
                            '30' => 30,
                            '31' => 31,
                        ],
                    ])
                    ->add('endMonth', Types\ChoiceType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                        ],
                        'placeholder' => 'Time pulse select month',
                        'label' => false,
                        'choices' => [
                            '1' => 1,
                            '2' => 2,
                            '3' => 3,
                            '4' => 4,
                            '5' => 5,
                            '6' => 6,
                            '7' => 7,
                            '8' => 8,
                            '9' => 9,
                            '10' => 10,
                            '11' => 11,
                            '12' => 12,
                        ],
                    ])
                    ->add('new', Types\SubmitType::class, [
                        'label' => 'Add time pulse',
                    ]);
            } else {
                $form
                    ->add('titleGerman', Types\TextType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                            new Assert\Regex([
                                'pattern' => '/%[1-6]/',
                                'message' => 'The time pulse name must contain a year placeholder',
                            ]),
                        ],
                        'label' => 'Time pulse',
                        'attr' => ['placeholder' => 'de'],
                    ])
                    ->add('titleEnglish', Types\TextType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                            new Assert\Regex([
                                'pattern' => '/%[1-6]/',
                                'message' => 'The time pulse name must contain a year placeholder',
                            ]),
                        ],
                        'label' => false,
                        'attr' => ['placeholder' => 'en'],
                        'help' => 'Time pulses help',
                        'help_html' => true,
                    ])
                    ->add('startDay', Types\ChoiceType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                        ],
                        'placeholder' => 'Time pulse select day',
                        'label' => 'Time pulse start',
                        'expanded' => false,
                        'choices' => [
                            '1' => 1,
                            '2' => 2,
                            '3' => 3,
                            '4' => 4,
                            '5' => 5,
                            '6' => 6,
                            '7' => 7,
                            '8' => 8,
                            '9' => 9,
                            '10' => 10,
                            '11' => 11,
                            '12' => 12,
                            '13' => 13,
                            '14' => 14,
                            '15' => 15,
                            '16' => 16,
                            '17' => 17,
                            '18' => 18,
                            '19' => 19,
                            '20' => 20,
                            '21' => 21,
                            '22' => 22,
                            '23' => 23,
                            '24' => 24,
                            '25' => 25,
                            '26' => 26,
                            '27' => 27,
                            '28' => 28,
                            '29' => 29,
                            '30' => 30,
                            '31' => 31,
                        ],
                    ])
                    ->add('startMonth', Types\ChoiceType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                        ],
                        'placeholder' => 'Time pulse select month',
                        'label' => false,
                        'choices' => [
                            '1' => 1,
                            '2' => 2,
                            '3' => 3,
                            '4' => 4,
                            '5' => 5,
                            '6' => 6,
                            '7' => 7,
                            '8' => 8,
                            '9' => 9,
                            '10' => 10,
                            '11' => 11,
                            '12' => 12,
                        ],
                    ])
                    ->add('endDay', Types\ChoiceType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                        ],
                        'placeholder' => 'Time pulse select day',
                        'label' => 'Time pulse end',
                        'choices' => [
                            '1' => 1,
                            '2' => 2,
                            '3' => 3,
                            '4' => 4,
                            '5' => 5,
                            '6' => 6,
                            '7' => 7,
                            '8' => 8,
                            '9' => 9,
                            '10' => 10,
                            '11' => 11,
                            '12' => 12,
                            '13' => 13,
                            '14' => 14,
                            '15' => 15,
                            '16' => 16,
                            '17' => 17,
                            '18' => 18,
                            '19' => 19,
                            '20' => 20,
                            '21' => 21,
                            '22' => 22,
                            '23' => 23,
                            '24' => 24,
                            '25' => 25,
                            '26' => 26,
                            '27' => 27,
                            '28' => 28,
                            '29' => 29,
                            '30' => 30,
                            '31' => 31,
                        ],
                    ])
                    ->add('endMonth', Types\ChoiceType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                        ],
                        'placeholder' => 'Time pulse select month',
                        'label' => false,
                        'choices' => [
                            '1' => 1,
                            '2' => 2,
                            '3' => 3,
                            '4' => 4,
                            '5' => 5,
                            '6' => 6,
                            '7' => 7,
                            '8' => 8,
                            '9' => 9,
                            '10' => 10,
                            '11' => 11,
                            '12' => 12,
                        ],
                    ])
                    ->add('update', Types\SubmitType::class, [
                        'label' => 'Save time pulse',
                    ])
                    ->add('delete', Types\SubmitType::class, [
                        'attr' => ['class' => 'uk-button-danger uk-width-auto'],
                        'label' => 'Delete time pulse',
                        'validation_groups' => false,   // disable validation
                    ])
                    ->add('cancel', Types\SubmitType::class, [
                        'attr' => ['class' => 'uk-button-secondary'],
                        'label' => 'Cancel',
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
        $resolver->setDefaults([
            'data_class' => TimePulseTemplate::class,
            'translation_domain' => 'portal',
        ]);
    }
}
