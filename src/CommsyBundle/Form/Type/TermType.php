<?php
namespace CommsyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class TermType extends AbstractType
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
            ->add('contentDe', CKEditorType::class, [
                'inline' => false,
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'content_de',
                'required' => false,
                'translation_domain' => 'portal',
                'attr' => array(
                    'class' => 'uk-form-width-large',
                    'style' => 'width: 100%;',
                ),
            ])
            ->add('contentEn', CKEditorType::class, [
                'inline' => false,
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => 'content_en',
                'required' => false,
                'translation_domain' => 'portal',
                'attr' => array(
                    'class' => 'uk-form-width-large',
                    'style' => 'width: 100%;',
                ),
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
            $terms = $event->getData();
            $form = $event->getForm();

            // check if this is a "new" object
            if (!$terms->getId()) {
                $form->add('new', Types\SubmitType::class, [
                    'attr' => array(
                        'class' => 'uk-button-primary',
                    ),
                    'label' => 'Create new term',
                    'translation_domain' => 'portal',
                ]);
            } else {
                $form
                    ->add('update', Types\SubmitType::class, [
                        'attr' => array(
                            'class' => 'uk-button-primary',
                        ),
                        'label' => 'Update term',
                        'translation_domain' => 'portal',
                    ]);
                $form
                    ->add('delete', Types\SubmitType::class, [
                        'attr' => array(
                            'class' => 'uk-button-danger',
                        ),
                        'label' => 'Delete term',
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
        return 'room_terms_templates';
    }
}