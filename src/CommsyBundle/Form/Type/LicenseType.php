<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class LicenseType extends AbstractType
{
    /**
     * Builds the form.
     * This method is called for each type in the hierarchy starting from the top most type.
     * Type extensions can further modify the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
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
                'translation_domain' => 'portal',
            ])
            ->add('content', Types\TextareaType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'Content',
                'required' => true,
                'translation_domain' => 'portal',
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
            $roomCategory = $event->getData();
            $form = $event->getForm();

            // check if this is a "new" object
            if (!$roomCategory->getId()) {
                $form->add('new', Types\SubmitType::class, [
                    'attr' => array(
                        'class' => 'uk-button-primary',
                    ),
                    'label' => 'Create new license',
                    'translation_domain' => 'portal',
                ]);
            } else {
                $form
                    ->add('update', Types\SubmitType::class, [
                        'attr' => array(
                            'class' => 'uk-button-primary',
                        ),
                        'label' => 'Update license',
                        'translation_domain' => 'portal',
                    ]);
                $form
                    ->add('delete', Types\SubmitType::class, [
                        'attr' => array(
                            'class' => 'uk-button-danger',
                        ),
                        'label' => 'Delete license',
                        'translation_domain' => 'portal',
                        'validation_groups' => false,   // disable validation
                    ]);
                $form
                    ->add('cancel', Types\SubmitType::class, [
                        'attr' => array(
                            'class' => 'uk-button-secondary',
                        ),
                        'label' => 'Cancel',
                        'translation_domain' => 'portal',
                    ]);
            }
        });
        ;
    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired([])
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
        return 'license_edit';
    }
}
