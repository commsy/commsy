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

namespace App\Form\Type\Room;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CancellableDeleteType extends AbstractType
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
            ->add('confirm', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\IdenticalTo([
                        'value' => mb_strtoupper((string) $options['confirm_string']),
                        'message' => 'The input does not match {{ compared_value }}',
                    ]),
                ],
                'required' => true,
                'mapped' => false,
            ])
            ->add('delete', SubmitType::class, [
                'label' => 'Confirm delete',
                'attr' => [
                    'class' => 'uk-button-danger',
                ],
            ])
            ->add('cancel', SubmitType::class, [
                'label' => 'Cancel cancellable delete',
                'attr' => [
                    'class' => 'uk-button-danger',
                    'formnovalidate' => '',
                ],
                'validation_groups' => false,
            ])
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['room', 'confirm_string'])
            ->setAllowedTypes('room', 'cs_room_item')
            ->setAllowedTypes('confirm_string', 'string')
            ->setDefaults([
                'room' => null,
                'translation_domain' => 'settings',
            ])
        ;
    }
}
