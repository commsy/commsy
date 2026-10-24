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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Confirmation for leaving a group as its last moderator, which closes the
 * group room. The confirmation word is passed in as `confirm_string`.
 */
class GroupRoomExitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('confirm_field', TextType::class, [
                'label' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\IdenticalTo(
                        value: mb_strtoupper((string) $options['data']['confirm_string']),
                        message: 'The input does not match {{ compared_value }}',
                    ),
                ],
                'required' => true,
            ])
            ->add('confirm_button', SubmitType::class, [
                'label' => 'Confirm',
                'attr' => [
                    'class' => 'uk-button-danger',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'settings',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'group_room_exit';
    }
}
