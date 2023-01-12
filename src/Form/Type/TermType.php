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

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class TermType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', Types\TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Title',
                'required' => true,
            ])
            ->add('contentDe', CKEditorType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'content_de',
                'config_name' => 'html_reduced',
            ])
            ->add('contentEn', CKEditorType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'content_en',
                'config_name' => 'html_reduced',
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $terms = $event->getData();
            $form = $event->getForm();

            // check if this is a "new" object
            if (!$terms->getId()) {
                $form->add('new', Types\SubmitType::class, [
                    'label' => 'Create new term',
                ]);
            } else {
                $form
                    ->add('update', Types\SubmitType::class, [
                        'label' => 'Update term',
                    ]);
                $form
                    ->add('delete', Types\SubmitType::class, [
                        'attr' => [
                            'class' => 'uk-button uk-button-danger uk-width-auto',
                        ],
                        'label' => 'Delete term',
                        'validation_groups' => false,   // disable validation
                    ]);
                $form
                    ->add('cancel', Types\SubmitType::class, [
                        'attr' => [
                            'class' => 'uk-button uk-button-default',
                        ],
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'translation_domain' => 'portal',
            ])
        ;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with the "Type" suffix removed
     * (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'room_terms_templates';
    }
}
