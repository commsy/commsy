<?php

namespace App\Form\Type\Portal;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class LicenseType extends AbstractType
{
    /**
     * Builds the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array $options The options
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
            ->add('content', CKEditorType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Content',
                'required' => true,
                'config_name' => 'html_reduced',
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $license = $event->getData();
            $form = $event->getForm();

            // check if this is a "new" object
            if (!$license->getId()) {
                $form->add('new', Types\SubmitType::class, [
                    'label' => 'Create new license',
                ]);
            } else {
                $form
                    ->add('update', Types\SubmitType::class, [
                        'attr' => [
                            'class' => 'uk-button-primary',
                        ],
                        'label' => 'Update license',
                    ]);
                $form
                    ->add('cancel', Types\SubmitType::class, [
                        'attr' => [
                            'class' => 'uk-button-secondary',
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
        $resolver->setDefaults([
            'translation_domain' => 'portal',
        ]);
    }
}
