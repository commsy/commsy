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

use App\Entity\RoomCategories;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RoomCategoriesType extends AbstractType
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
            $roomCategory = $event->getData();
            $form = $event->getForm();

            // check if this is a "new" object
            if (!$roomCategory->getId()) {
                $form
                    ->add('title', Types\TextType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                        ],
                        'label' => 'New category',
                        'required' => true,
                    ])
                    ->add('new', Types\SubmitType::class, [
                        'label' => 'Create new category',
                    ]);
            } else {
                $form
                    ->add('title', Types\TextType::class, [
                        'constraints' => [
                            new Assert\NotBlank(),
                        ],
                        'label' => 'Edit category',
                        'required' => true,
                    ])
                    ->add('update', Types\SubmitType::class, [
                        'label' => 'Update category',
                    ])
                    ->add('delete', Types\SubmitType::class, [
                        'attr' => ['class' => 'uk-button-danger uk-width-auto'],
                        'label' => 'Delete category',
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
            'data_class' => RoomCategories::class,
            'translation_domain' => 'portal',
        ]);
    }
}
