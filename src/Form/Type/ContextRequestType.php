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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ContextRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('request', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                ],
                'label' => 'become member',
                'translation_domain' => 'room',
            ])
            ->add('cancel', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-secondary',
                    'formnovalidate' => '',
                ],
                'label' => 'cancel',
                'translation_domain' => 'form',
                'validation_groups' => false,
            ])
        ;

        if ($options['checkNewMembersWithCode']) {
            $builder
                ->add('code', TextType::class, [
                    'constraints' => [
                        new Constraints\NotBlank([
                            'groups' => 'code' // avoid NotBlank() validation for code when performing 'Default' form validation
                        ]),
                        new Constraints\EqualTo([
                            'value' => $options['checkNewMemberCode'],
                            'message' => 'Your access code is invalid.',
                            'groups' => 'code',
                        ]),
                        new Constraints\EqualTo([
                            'value' => $options['checkNewMemberCode'],
                            'message' => 'Your access code is invalid, please remove',
                            'groups' => 'Default', // also validate non-empty code when performing 'Default' form validation
                        ]),
                    ],
                    'label' => 'Code',
                    'translation_domain' => 'room',
                    'required' => false,
                ])
                ->add('coderequest', SubmitType::class, [
                    'attr' => [
                        'class' => 'uk-button-primary',
                    ],
                    'label' => 'become member code',
                    'translation_domain' => 'room',
                    'validation_groups' => ['code'],
                ])
            ;
        }

        if ($options['withAGB']) {
            $builder
                ->add('agb', CheckboxType::class, [
                    'constraints' => [
                        new Constraints\IsTrue([
                            'message' => 'You must accept room agb.',
                            'validation_groups' => ['Default', 'code'],
                        ]),
                    ],
                    'label' => 'AGB',
                    'attr' => [
                    ],
                    'translation_domain' => 'room',
                    'required' => true,
                ])
            ;
        }

        if (!$options['CheckNewMembersNever']) {
            $builder
                ->add('description', TextareaType::class, [
                    'label' => 'description',
                    'attr' => [
                        'rows' => 5,
                        'cols' => 80,
                    ],
                    'translation_domain' => 'room',
                    'required' => false,
                ])
            ;
        }
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'validation_groups' => ['Default'],
            ])
            ->setRequired(['checkNewMembersWithCode', 'checkNewMemberCode', 'withAGB', 'CheckNewMembersNever'])
        ;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix(): string
    {
        return 'request';
    }
}
